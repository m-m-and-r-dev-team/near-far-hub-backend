<?php

declare(strict_types=1);

namespace App\Services\Repositories\Categories;

use App\Http\DataTransferObjects\Categories\CreateCategoryData;
use App\Http\DataTransferObjects\Categories\UpdateCategoryData;
use App\Models\Categories\Category;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

readonly class CategoryRepository
{
    public function __construct(
        private Category $category
    ) {
    }

    /**
     * Find category by ID
     */
    public function findById(int $categoryId): ?Category
    {
        return $this->category->find($categoryId);
    }

    /**
     * Find category by ID with children
     */
    public function findByIdWithChildren(int $categoryId): ?Category
    {
        return $this->category
            ->with([Category::CHILDREN_RELATION])
            ->find($categoryId);
    }

    /**
     * Find category by slug with children
     */
    public function findBySlugWithChildren(string $slug): ?Category
    {
        return $this->category
            ->with([Category::CHILDREN_RELATION])
            ->where(Category::SLUG, $slug)
            ->active()
            ->first();
    }

    /**
     * Get all categories with hierarchy
     */
    public function getAllWithHierarchy(): Collection
    {
        return Cache::remember('categories_all_hierarchy', 1800, function () {
            return $this->category
                ->withChildren()
                ->withParent()
                ->ordered()
                ->get();
        });
    }

    /**
     * Get active categories tree
     */
    public function getActiveTree(): Collection
    {
        return Cache::remember('categories_active_tree', 1800, function () {
            return $this->category->getActiveTree();
        });
    }

    /**
     * Get featured categories
     */
    public function getFeatured(): Collection
    {
        return Cache::remember('categories_featured', 900, function () {
            return $this->category->getFeaturedCategories();
        });
    }

    /**
     * Get root categories
     */
    public function getRootCategories(): Collection
    {
        return Cache::remember('categories_root', 1800, function () {
            return $this->category
                ->active()
                ->rootCategories()
                ->ordered()
                ->get();
        });
    }

    /**
     * Get category children
     */
    public function getChildren(int $categoryId): Collection
    {
        return $this->category
            ->where(Category::PARENT_ID, $categoryId)
            ->active()
            ->ordered()
            ->get();
    }

    /**
     * Create category
     * @throws Exception
     */
    public function create(CreateCategoryData $data): Category
    {
        // Validate parent category if provided
        if ($data->parentId) {
            $parent = $this->category->find($data->parentId);
            if (!$parent) {
                throw new Exception('Parent category not found');
            }
        }

        $payload = [
            Category::NAME => $data->name,
            Category::DESCRIPTION => $data->description,
            Category::PARENT_ID => $data->parentId,
            Category::ICON => $data->icon,
            Category::COLOR => $data->color,
            Category::SORT_ORDER => $data->sortOrder,
            Category::IS_ACTIVE => $data->isActive,
            Category::IS_FEATURED => $data->isFeatured,
            Category::META_TITLE => $data->metaTitle,
            Category::META_DESCRIPTION => $data->metaDescription,
            Category::ATTRIBUTES => $data->attributes,
            Category::VALIDATION_RULES => $data->validationRules,
        ];

        $category = $this->category->create($payload);
        $this->clearCache();

        return $category->load([Category::CHILDREN_RELATION, Category::PARENT_RELATION]);
    }

    /**
     * Update category
     * @throws Exception
     */
    public function update(int $categoryId, UpdateCategoryData $data): Category
    {
        $category = $this->category->findOrFail($categoryId);

        // Validate parent category if provided
        if ($data->parentId !== null) {
            if ($data->parentId === $categoryId) {
                throw new Exception('Category cannot be its own parent');
            }

            if ($data->parentId > 0) {
                $parent = $this->category->find($data->parentId);
                if (!$parent) {
                    throw new Exception('Parent category not found');
                }

                // Check for circular dependency
                if ($this->wouldCreateCircularDependency($categoryId, $data->parentId)) {
                    throw new Exception('This would create a circular dependency');
                }
            }
        }

        // Build update payload with only provided fields
        $payload = array_filter([
            Category::NAME => $data->name,
            Category::DESCRIPTION => $data->description,
            Category::PARENT_ID => $data->parentId,
            Category::ICON => $data->icon,
            Category::COLOR => $data->color,
            Category::SORT_ORDER => $data->sortOrder,
            Category::IS_ACTIVE => $data->isActive,
            Category::IS_FEATURED => $data->isFeatured,
            Category::META_TITLE => $data->metaTitle,
            Category::META_DESCRIPTION => $data->metaDescription,
            Category::ATTRIBUTES => $data->attributes,
            Category::VALIDATION_RULES => $data->validationRules,
        ], fn($value) => $value !== null);

        $category->update($payload);
        $this->clearCache();

        return $category->load([Category::CHILDREN_RELATION, Category::PARENT_RELATION]);
    }

    /**
     * Delete category
     * @throws Exception
     */
    public function delete(int $categoryId): void
    {
        $category = $this->category->findOrFail($categoryId);

        // Check if category has children
        if ($category->relatedChildren()->isNotEmpty()) {
            throw new Exception('Cannot delete category with subcategories');
        }

        // Check if category has listings
        if ($category->relatedListings()->exists()) {
            throw new Exception('Cannot delete category with existing listings');
        }

        $category->delete();
        $this->clearCache();
    }

    /**
     * Search categories
     */
    public function search(string $query, int $limit = 20): Collection
    {
        return $this->category
            ->where(Category::NAME, 'LIKE', "%{$query}%")
            ->orWhere(Category::DESCRIPTION, 'LIKE', "%{$query}%")
            ->active()
            ->ordered()
            ->limit($limit)
            ->get();
    }

    /**
     * Get category statistics
     */
    public function getStats(int $categoryId): array
    {
        $category = $this->category->findOrFail($categoryId);

        return Cache::remember("category_stats_{$categoryId}", 900, function () use ($category) {
            return [
                'total_listings' => $category->relatedListings()->count(),
                'active_listings' => $category->relatedListings()->where('status', 'active')->count(),
                'draft_listings' => $category->relatedListings()->where('status', 'draft')->count(),
                'sold_listings' => $category->relatedListings()->where('status', 'sold')->count(),
                'total_children' => $category->relatedChildren()->count(),
                'active_children' => $category->relatedChildren()->where('is_active', true)->count(),
                'total_views' => $category->relatedListings()->sum('views_count'),
                'avg_price' => $category->relatedListings()
                    ->where('status', 'active')
                    ->avg('price'),
                'price_range' => [
                    'min' => $category->relatedListings()->where('status', 'active')->min('price'),
                    'max' => $category->relatedListings()->where('status', 'active')->max('price'),
                ],
                'recent_listings' => $category->relatedListings()
                    ->where('created_at', '>=', now()->subDays(30))
                    ->count(),
            ];
        });
    }

    /**
     * Reorder categories
     */
    public function reorder(array $categories): void
    {
        DB::transaction(function () use ($categories) {
            foreach ($categories as $categoryData) {
                $this->category
                    ->where(Category::ID, $categoryData['id'])
                    ->update([Category::SORT_ORDER => $categoryData['sort_order']]);
            }
        });

        $this->clearCache();
    }

    /**
     * Toggle category status
     */
    public function toggleStatus(int $categoryId): Category
    {
        $category = $this->category->findOrFail($categoryId);

        $category->update([
            Category::IS_ACTIVE => !$category->getIsActive()
        ]);

        $this->clearCache();

        return $category->fresh();
    }

    /**
     * Get category path/breadcrumb
     */
    public function getPath(int $categoryId): Collection
    {
        $category = $this->category->with([Category::PARENT_RELATION])->findOrFail($categoryId);

        return collect($category->getPath());
    }

    /**
     * Get category suggestions based on listing content
     */
    public function getSuggestions(string $title, string $description = ''): Collection
    {
        $content = strtolower($title . ' ' . $description);
        $words = array_unique(str_word_count($content, 1));

        if (empty($words)) {
            return new Collection();
        }

        // Create search query for category names and attributes
        $query = $this->category->active();

        foreach ($words as $word) {
            if (strlen($word) > 3) { // Only consider words longer than 3 characters
                $query->orWhere(Category::NAME, 'LIKE', "%{$word}%")
                    ->orWhere(Category::DESCRIPTION, 'LIKE', "%{$word}%")
                    ->orWhereJsonContains(Category::ATTRIBUTES, $word);
            }
        }

        return $query->ordered()->limit(5)->get();
    }

    /**
     * Get form fields for category
     */
    public function getFormFields(int $categoryId): array
    {
        $category = $this->category->find($categoryId);

        if (!$category) {
            return [];
        }

        $attributes = $category->getAttributes();
        $formFields = [];

        foreach ($attributes as $key => $config) {
            $field = [
                'name' => $key,
                'label' => $config['label'] ?? ucfirst(str_replace('_', ' ', $key)),
                'type' => $config['type'] ?? 'text',
                'required' => $config['required'] ?? false,
                'placeholder' => $config['placeholder'] ?? '',
                'help_text' => $config['help_text'] ?? '',
            ];

            // Add type-specific properties
            switch ($config['type']) {
                case 'select':
                    $field['options'] = $config['options'] ?? [];
                    break;
                case 'number':
                    $field['min'] = $config['min'] ?? null;
                    $field['max'] = $config['max'] ?? null;
                    $field['step'] = $config['step'] ?? 1;
                    break;
                case 'text':
                case 'textarea':
                    $field['min_length'] = $config['min'] ?? null;
                    $field['max_length'] = $config['max'] ?? null;
                    break;
            }

            $formFields[] = $field;
        }

        return $formFields;
    }

    // Private helper methods

    /**
     * Check if changing parent would create circular dependency
     */
    private function wouldCreateCircularDependency(int $categoryId, int $parentId): bool
    {
        $parent = $this->category->find($parentId);

        while ($parent) {
            if ($parent->getId() === $categoryId) {
                return true;
            }
            $parent = $parent->relatedParent();
        }

        return false;
    }

    /**
     * Clear category-related cache
     */
    private function clearCache(): void
    {
        Cache::forget('categories_all_hierarchy');
        Cache::forget('categories_active_tree');
        Cache::forget('categories_featured');
        Cache::forget('categories_root');

        // Clear stats cache for all categories (this is aggressive but safe)
        for ($i = 1; $i <= 1000; $i++) {
            Cache::forget("category_stats_{$i}");
        }
    }
}