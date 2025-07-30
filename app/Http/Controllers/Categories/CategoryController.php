<?php

declare(strict_types=1);

namespace App\Http\Controllers\Categories;

use App\Http\Controllers\Controller;
use App\Http\Requests\Categories\CreateCategoryRequest;
use App\Http\Requests\Categories\UpdateCategoryRequest;
use App\Http\Resources\Categories\CategoryResource;
use App\Http\Resources\Categories\CategoryTreeResource;
use App\Services\Repositories\Categories\CategoryRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository
    ) {
    }

    /**
     * Get all categories with hierarchical structure
     */
    public function getAllCategories(): AnonymousResourceCollection
    {
        $categories = $this->categoryRepository->getAllWithHierarchy();
        return CategoryTreeResource::collection($categories);
    }

    /**
     * Get active categories tree for public use
     */
    public function getActiveCategoriesTree(): AnonymousResourceCollection
    {
        $categories = $this->categoryRepository->getActiveTree();
        return CategoryTreeResource::collection($categories);
    }

    /**
     * Get featured categories
     */
    public function getFeaturedCategories(): AnonymousResourceCollection
    {
        $categories = $this->categoryRepository->getFeatured();
        return CategoryResource::collection($categories);
    }

    /**
     * Get root categories (no parent)
     */
    public function getRootCategories(): AnonymousResourceCollection
    {
        $categories = $this->categoryRepository->getRootCategories();
        return CategoryResource::collection($categories);
    }

    /**
     * Get category by ID with children
     */
    public function getCategoryById(int $categoryId): CategoryResource
    {
        $category = $this->categoryRepository->findByIdWithChildren($categoryId);

        if (!$category) {
            abort(404, 'Category not found');
        }

        return new CategoryResource($category);
    }

    /**
     * Get category by slug (public)
     */
    public function getCategoryBySlug(string $slug): CategoryResource
    {
        $category = $this->categoryRepository->findBySlugWithChildren($slug);

        if (!$category) {
            abort(404, 'Category not found');
        }

        return new CategoryResource($category);
    }

    /**
     * Create a new category
     */
    public function createCategory(CreateCategoryRequest $request): CategoryResource
    {
        try {
            $category = $this->categoryRepository->create($request->dto());
            return new CategoryResource($category);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update category
     */
    public function updateCategory(int $categoryId, UpdateCategoryRequest $request): CategoryResource|JsonResponse
    {
        try {
            $category = $this->categoryRepository->update($categoryId, $request->dto());
            return new CategoryResource($category);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete category
     */
    public function deleteCategory(int $categoryId): JsonResponse
    {
        try {
            $this->categoryRepository->delete($categoryId);
            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get category children
     */
    public function getCategoryChildren(int $categoryId): AnonymousResourceCollection
    {
        $children = $this->categoryRepository->getChildren($categoryId);
        return CategoryResource::collection($children);
    }

    /**
     * Search categories
     */
    public function searchCategories(Request $request): AnonymousResourceCollection
    {
        $query = $request->get('q', '');
        $limit = min((int) $request->get('limit', 20), 50);

        $categories = $this->categoryRepository->search($query, $limit);
        return CategoryResource::collection($categories);
    }

    /**
     * Get category statistics
     */
    public function getCategoryStats(int $categoryId): JsonResponse
    {
        $stats = $this->categoryRepository->getStats($categoryId);
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Reorder categories
     */
    public function reorderCategories(Request $request): JsonResponse
    {
        $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|integer|exists:categories,id',
            'categories.*.sort_order' => 'required|integer|min:0',
        ]);

        try {
            $this->categoryRepository->reorder($request->get('categories'));
            return response()->json([
                'success' => true,
                'message' => 'Categories reordered successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle category status (active/inactive)
     */
    public function toggleStatus(int $categoryId): JsonResponse
    {
        try {
            $category = $this->categoryRepository->toggleStatus($categoryId);
            return response()->json([
                'success' => true,
                'message' => $category->getIsActive() ? 'Category activated' : 'Category deactivated',
                'is_active' => $category->getIsActive()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle category status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get category breadcrumb path
     */
    public function getCategoryPath(int $categoryId): JsonResponse
    {
        $path = $this->categoryRepository->getPath($categoryId);
        return response()->json([
            'success' => true,
            'data' => [
                'path' => CategoryResource::collection($path),
                'breadcrumb' => implode(' > ', $path->pluck('name')->toArray())
            ]
        ]);
    }

    /**
     * Get category suggestions for listing creation
     */
    public function getCategorySuggestions(Request $request): JsonResponse
    {
        $title = $request->get('title', '');
        $description = $request->get('description', '');

        if (empty($title) && empty($description)) {
            return response()->json([
                'success' => false,
                'message' => 'Title or description is required for suggestions'
            ], 400);
        }

        $suggestions = $this->categoryRepository->getSuggestions($title, $description);

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($suggestions),
            'message' => count($suggestions) > 0 ? 'Category suggestions found' : 'No category suggestions found'
        ]);
    }

    /**
     * Validate category attributes for listing
     */
    public function validateCategoryAttributes(int $categoryId, Request $request): JsonResponse
    {
        $category = $this->categoryRepository->findById($categoryId);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        $attributes = $request->get('attributes', []);
        $errors = $category->validateListingData($attributes);

        return response()->json([
            'success' => empty($errors),
            'errors' => $errors,
            'message' => empty($errors) ? 'Attributes are valid' : 'Validation failed'
        ]);
    }

    /**
     * Get category form fields for listing creation
     */
    public function getCategoryFormFields(int $categoryId): JsonResponse
    {
        $category = $this->categoryRepository->findById($categoryId);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        $formFields = $this->categoryRepository->getFormFields($categoryId);

        return response()->json([
            'success' => true,
            'data' => [
                'category' => new CategoryResource($category),
                'form_fields' => $formFields,
                'validation_rules' => $category->getValidationRules(),
                'path' => CategoryResource::collection($category->getPath())
            ]
        ]);
    }
}