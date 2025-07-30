<?php

declare(strict_types=1);

namespace App\Models\Categories;

use App\Models\Listings\Listing;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @mixin Builder
 */
class Category extends Model
{
    use HasFactory;

    public const ID = 'id';
    public const NAME = 'name';
    public const SLUG = 'slug';
    public const DESCRIPTION = 'description';
    public const PARENT_ID = 'parent_id';
    public const ICON = 'icon';
    public const COLOR = 'color';
    public const SORT_ORDER = 'sort_order';
    public const IS_ACTIVE = 'is_active';
    public const IS_FEATURED = 'is_featured';
    public const META_TITLE = 'meta_title';
    public const META_DESCRIPTION = 'meta_description';
    public const ATTRIBUTES = 'attributes';
    public const VALIDATION_RULES = 'validation_rules';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::NAME,
        self::SLUG,
        self::DESCRIPTION,
        self::PARENT_ID,
        self::ICON,
        self::COLOR,
        self::SORT_ORDER,
        self::IS_ACTIVE,
        self::IS_FEATURED,
        self::META_TITLE,
        self::META_DESCRIPTION,
        self::ATTRIBUTES,
        self::VALIDATION_RULES,
    ];

    protected $casts = [
        self::IS_ACTIVE => 'boolean',
        self::IS_FEATURED => 'boolean',
        self::SORT_ORDER => 'integer',
        self::ATTRIBUTES => 'array',
        self::VALIDATION_RULES => 'array',
    ];

    /** @see Category::parentRelation() */
    const PARENT_RELATION = 'parentRelation';
    /** @see Category::childrenRelation() */
    const CHILDREN_RELATION = 'childrenRelation';
    /** @see Category::listingsRelation() */
    const LISTINGS_RELATION = 'listingsRelation';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Category $category) {
            if (empty($category->getSlug())) {
                $category[Category::SLUG] = Str::slug($category->getName());
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty(Category::NAME) && empty($category->getSlug())) {
                $category[Category::SLUG] = Str::slug($category->getName());
            }
        });
    }

    // Relationships
    public function parentRelation(): BelongsTo
    {
        return $this->belongsTo(self::class, self::PARENT_ID);
    }

    public function childrenRelation(): HasMany
    {
        return $this->hasMany(self::class, self::PARENT_ID)
            ->where(self::IS_ACTIVE, true)
            ->orderBy(self::SORT_ORDER);
    }

    public function listingsRelation(): HasMany
    {
        return $this->hasMany(Listing::class, Listing::CATEGORY_ID);
    }

    // Accessors
    public function getId(): int
    {
        return $this->getAttribute(self::ID);
    }

    public function getName(): string
    {
        return $this->getAttribute(self::NAME);
    }

    public function getSlug(): string
    {
        return $this->getAttribute(self::SLUG);
    }

    public function getDescription(): ?string
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getParentId(): ?int
    {
        return $this->getAttribute(self::PARENT_ID);
    }

    public function getIcon(): ?string
    {
        return $this->getAttribute(self::ICON);
    }

    public function getColor(): ?string
    {
        return $this->getAttribute(self::COLOR);
    }

    public function getSortOrder(): int
    {
        return $this->getAttribute(self::SORT_ORDER) ?? 0;
    }

    public function getIsActive(): bool
    {
        return $this->getAttribute(self::IS_ACTIVE) ?? false;
    }

    public function getIsFeatured(): bool
    {
        return $this->getAttribute(self::IS_FEATURED) ?? false;
    }

    public function getAttributes(): array
    {
        return $this->getAttribute(self::ATTRIBUTES) ?? [];
    }

    public function getValidationRules(): array
    {
        return $this->getAttribute(self::VALIDATION_RULES) ?? [];
    }

    public function getCreatedAt(): Carbon
    {
        return $this->getAttribute(self::CREATED_AT);
    }

    public function getUpdatedAt(): Carbon
    {
        return $this->getAttribute(self::UPDATED_AT);
    }

    // Helper methods
    public function relatedParent(): ?self
    {
        return $this->{self::PARENT_RELATION};
    }

    /**
     * @return Collection<self>
     */
    public function relatedChildren(): Collection
    {
        return $this->{self::CHILDREN_RELATION};
    }

    /**
     * @return Collection<Listing>
     */
    public function relatedListings(): Collection
    {
        return $this->{self::LISTINGS_RELATION};
    }

    public function isParent(): bool
    {
        return $this->relatedChildren()->isNotEmpty();
    }

    public function isChild(): bool
    {
        return !is_null($this->getParentId());
    }

    public function isRoot(): bool
    {
        return is_null($this->getParentId());
    }

    public function getDepth(): int
    {
        $depth = 0;
        $parent = $this->relatedParent();

        while ($parent) {
            $depth++;
            $parent = $parent->relatedParent();
        }

        return $depth;
    }

    public function getPath(): array
    {
        $path = [$this];
        $parent = $this->relatedParent();

        while ($parent) {
            array_unshift($path, $parent);
            $parent = $parent->relatedParent();
        }

        return $path;
    }

    public function getPathNames(): array
    {
        return array_map(fn($category) => $category->getName(), $this->getPath());
    }

    public function getBreadcrumb(): string
    {
        return implode(' > ', $this->getPathNames());
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(self::IS_ACTIVE, true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where(self::IS_FEATURED, true);
    }

    public function scopeRootCategories(Builder $query): Builder
    {
        return $query->whereNull(self::PARENT_ID);
    }

    public function scopeWithChildren(Builder $query): Builder
    {
        return $query->with([self::CHILDREN_RELATION]);
    }

    public function scopeWithParent(Builder $query): Builder
    {
        return $query->with([self::PARENT_RELATION]);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy(self::SORT_ORDER)->orderBy(self::NAME);
    }

    // Static methods
    public static function findBySlug(string $slug): ?self
    {
        return static::where(self::SLUG, $slug)->active()->first();
    }

    public static function getActiveTree(): Collection
    {
        return static::active()
            ->rootCategories()
            ->withChildren()
            ->ordered()
            ->get();
    }

    public static function getFeaturedCategories(): Collection
    {
        return static::active()
            ->featured()
            ->ordered()
            ->get();
    }

    // Category-specific attribute methods
    public function hasAttribute(string $key): bool
    {
        return array_key_exists($key, $this->getAttributes());
    }

    public function getAttribute(string $key, $default = null)
    {
        $attributes = $this->getAttributes();
        return $attributes[$key] ?? $default;
    }

    public function getRequiredAttributes(): array
    {
        return array_filter($this->getAttributes(), fn($attr) => $attr['required'] ?? false);
    }

    public function validateListingData(array $data): array
    {
        $errors = [];
        $rules = $this->getValidationRules();
        $attributes = $this->getAttributes();

        foreach ($attributes as $key => $config) {
            $value = $data[$key] ?? null;
            $isRequired = $config['required'] ?? false;
            $type = $config['type'] ?? 'text';

            // Check required fields
            if ($isRequired && empty($value)) {
                $errors[$key] = "The {$key} field is required for this category.";
                continue;
            }

            // Skip validation if field is empty and not required
            if (empty($value)) {
                continue;
            }

            // Type-specific validation
            switch ($type) {
                case 'number':
                    if (!is_numeric($value)) {
                        $errors[$key] = "The {$key} must be a number.";
                    }
                    break;
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$key] = "The {$key} must be a valid email address.";
                    }
                    break;
                case 'url':
                    if (!filter_var($value, FILTER_VALIDATE_URL)) {
                        $errors[$key] = "The {$key} must be a valid URL.";
                    }
                    break;
                case 'select':
                    $options = $config['options'] ?? [];
                    if (!in_array($value, $options)) {
                        $errors[$key] = "The selected {$key} is invalid.";
                    }
                    break;
            }

            // Min/Max validation
            if (isset($config['min']) && strlen($value) < $config['min']) {
                $errors[$key] = "The {$key} must be at least {$config['min']} characters.";
            }
            if (isset($config['max']) && strlen($value) > $config['max']) {
                $errors[$key] = "The {$key} must not exceed {$config['max']} characters.";
            }
        }

        return $errors;
    }
}