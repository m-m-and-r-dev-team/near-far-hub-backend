<?php

declare(strict_types=1);

namespace App\Models\Listings;

use App\Enums\Listings\ListingCategoryEnum;
use App\Enums\Listings\ListingConditionEnum;
use App\Enums\Listings\ListingStatusEnum;
use App\Models\Images\Image;
use App\Models\SellerAppointments\SellerAppointment;
use App\Models\SellerProfiles\SellerProfile;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * @mixin Builder
 */
class Listing extends Model
{
    use HasFactory;

    public const ID = 'id';
    public const SELLER_PROFILE_ID = 'seller_profile_id';
    public const TITLE = 'title';
    public const DESCRIPTION = 'description';
    public const PRICE = 'price';
    public const CATEGORY = 'category';
    public const CONDITION = 'condition';
    public const IMAGES = 'images';
    public const LOCATION = 'location';
    public const CAN_DELIVER_GLOBALLY = 'can_deliver_globally';
    public const REQUIRES_APPOINTMENT = 'requires_appointment';
    public const STATUS = 'status';
    public const PUBLISHED_AT = 'published_at';
    public const EXPIRES_AT = 'expires_at';
    public const VIEWS_COUNT = 'views_count';
    public const FAVORITES_COUNT = 'favorites_count';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    // Additional fields not in migration but useful
    public const SLUG = 'slug';
    public const TAGS = 'tags';
    public const DELIVERY_OPTIONS = 'delivery_options';
    public const DIMENSIONS = 'dimensions';
    public const WEIGHT = 'weight';
    public const BRAND = 'brand';
    public const MODEL = 'model';
    public const YEAR = 'year';
    public const COLOR = 'color';
    public const MATERIAL = 'material';

    protected $fillable = [
        self::SELLER_PROFILE_ID,
        self::TITLE,
        self::DESCRIPTION,
        self::PRICE,
        self::CATEGORY,
        self::CONDITION,
        self::IMAGES,
        self::LOCATION,
        self::CAN_DELIVER_GLOBALLY,
        self::REQUIRES_APPOINTMENT,
        self::STATUS,
        self::PUBLISHED_AT,
        self::EXPIRES_AT,
        self::VIEWS_COUNT,
        self::FAVORITES_COUNT,
        self::SLUG,
        self::TAGS,
        self::DELIVERY_OPTIONS,
        self::DIMENSIONS,
        self::WEIGHT,
        self::BRAND,
        self::MODEL,
        self::YEAR,
        self::COLOR,
        self::MATERIAL,
    ];

    protected $casts = [
        self::PRICE => 'decimal:2',
        self::CATEGORY => ListingCategoryEnum::class,
        self::CONDITION => ListingConditionEnum::class,
        self::STATUS => ListingStatusEnum::class,
        self::IMAGES => 'array',
        self::LOCATION => 'array',
        self::CAN_DELIVER_GLOBALLY => 'boolean',
        self::REQUIRES_APPOINTMENT => 'boolean',
        self::PUBLISHED_AT => 'datetime',
        self::EXPIRES_AT => 'datetime',
        self::VIEWS_COUNT => 'integer',
        self::FAVORITES_COUNT => 'integer',
        self::TAGS => 'array',
        self::DELIVERY_OPTIONS => 'array',
        self::DIMENSIONS => 'array',
        self::WEIGHT => 'decimal:2',
    ];

    /** @see Listing::sellerProfileRelation() */
    const SELLER_PROFILE_RELATION = 'sellerProfileRelation';
    /** @see Listing::imagesRelation() */
    const IMAGES_RELATION = 'imagesRelation';
    /** @see Listing::appointmentsRelation() */
    const APPOINTMENTS_RELATION = 'appointmentsRelation';

    public function sellerProfileRelation(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class, self::SELLER_PROFILE_ID);
    }

    public function imagesRelation(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('created_at');
    }

    public function appointmentsRelation(): HasMany
    {
        return $this->hasMany(SellerAppointment::class, 'listing_id');
    }

    // Accessor methods
    public function getId(): int
    {
        return $this->getAttribute(self::ID);
    }

    public function getSellerProfileId(): int
    {
        return $this->getAttribute(self::SELLER_PROFILE_ID);
    }

    public function getTitle(): string
    {
        return $this->getAttribute(self::TITLE);
    }

    public function getDescription(): string
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getPrice(): float
    {
        return (float) $this->getAttribute(self::PRICE);
    }

    public function getCategory(): ListingCategoryEnum
    {
        return $this->getAttribute(self::CATEGORY);
    }

    public function getCondition(): ?ListingConditionEnum
    {
        return $this->getAttribute(self::CONDITION);
    }

    public function getStatus(): ListingStatusEnum
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getImages(): ?array
    {
        return $this->getAttribute(self::IMAGES);
    }

    public function getLocation(): ?array
    {
        return $this->getAttribute(self::LOCATION);
    }

    public function getCanDeliverGlobally(): bool
    {
        return $this->getAttribute(self::CAN_DELIVER_GLOBALLY);
    }

    public function getRequiresAppointment(): bool
    {
        return $this->getAttribute(self::REQUIRES_APPOINTMENT);
    }

    public function getPublishedAt(): ?Carbon
    {
        return $this->getAttribute(self::PUBLISHED_AT);
    }

    public function getExpiresAt(): ?Carbon
    {
        return $this->getAttribute(self::EXPIRES_AT);
    }

    public function getViewsCount(): int
    {
        return $this->getAttribute(self::VIEWS_COUNT);
    }

    public function getFavoritesCount(): int
    {
        return $this->getAttribute(self::FAVORITES_COUNT);
    }

    public function getCreatedAt(): Carbon
    {
        return $this->getAttribute(self::CREATED_AT);
    }

    public function getUpdatedAt(): Carbon
    {
        return $this->getAttribute(self::UPDATED_AT);
    }

    public function getSlug(): ?string
    {
        return $this->getAttribute(self::SLUG);
    }

    public function getTags(): ?array
    {
        return $this->getAttribute(self::TAGS);
    }

    // Relationship accessors
    public function relatedSellerProfile(): SellerProfile
    {
        return $this->{self::SELLER_PROFILE_RELATION};
    }

    /**
     * @return Collection<Image>
     */
    public function relatedImages(): Collection
    {
        return $this->{self::IMAGES_RELATION};
    }

    /**
     * @return Collection<SellerAppointment>
     */
    public function relatedAppointments(): Collection
    {
        return $this->{self::APPOINTMENTS_RELATION};
    }

    // Business logic methods
    public function isActive(): bool
    {
        return $this->getStatus() === ListingStatusEnum::ACTIVE;
    }

    public function isDraft(): bool
    {
        return $this->getStatus() === ListingStatusEnum::DRAFT;
    }

    public function isSold(): bool
    {
        return $this->getStatus() === ListingStatusEnum::SOLD;
    }

    public function isExpired(): bool
    {
        return $this->getStatus() === ListingStatusEnum::EXPIRED ||
            ($this->getExpiresAt() && $this->getExpiresAt()->isPast());
    }

    public function canBeViewed(): bool
    {
        return $this->isActive() && !$this->isExpired();
    }

    public function canBeEdited(): bool
    {
        return in_array($this->getStatus(), [
            ListingStatusEnum::DRAFT,
            ListingStatusEnum::ACTIVE,
            ListingStatusEnum::PENDING_APPROVAL
        ]);
    }

    public function getPrimaryImage(): ?Image
    {
        return $this->relatedImages()->where('is_primary', true)->first() ??
            $this->relatedImages()->first();
    }

    public function getFormattedPrice(): string
    {
        return '$' . number_format($this->getPrice(), 2);
    }

    public function getCategoryDisplayName(): string
    {
        return match($this->getCategory()) {
            ListingCategoryEnum::ELECTRONICS => 'Electronics',
            ListingCategoryEnum::FASHION => 'Fashion',
            ListingCategoryEnum::HOME_GARDEN => 'Home & Garden',
            ListingCategoryEnum::AUTOMOTIVE => 'Automotive',
            ListingCategoryEnum::SPORTS_OUTDOORS => 'Sports & Outdoors',
            ListingCategoryEnum::BOOKS_MEDIA => 'Books & Media',
            ListingCategoryEnum::TOYS_GAMES => 'Toys & Games',
            ListingCategoryEnum::HEALTH_BEAUTY => 'Health & Beauty',
            ListingCategoryEnum::BUSINESS_INDUSTRIAL => 'Business & Industrial',
            ListingCategoryEnum::COLLECTIBLES => 'Collectibles',
            ListingCategoryEnum::REAL_ESTATE => 'Real Estate',
            ListingCategoryEnum::SERVICES => 'Services',
            ListingCategoryEnum::OTHER => 'Other',
        };
    }

    public function getConditionDisplayName(): ?string
    {
        if (!$this->getCondition()) return null;

        return match($this->getCondition()) {
            ListingConditionEnum::NEW => 'New',
            ListingConditionEnum::LIKE_NEW => 'Like New',
            ListingConditionEnum::GOOD => 'Good',
            ListingConditionEnum::FAIR => 'Fair',
            ListingConditionEnum::POOR => 'Poor',
            ListingConditionEnum::REFURBISHED => 'Refurbished',
            ListingConditionEnum::FOR_PARTS => 'For Parts',
        };
    }

    public function incrementViewsCount(): void
    {
        $this->increment(self::VIEWS_COUNT);
    }

    public function incrementFavoritesCount(): void
    {
        $this->increment(self::FAVORITES_COUNT);
    }

    public function decrementFavoritesCount(): void
    {
        $this->decrement(self::FAVORITES_COUNT);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where(self::STATUS, ListingStatusEnum::ACTIVE);
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull(self::PUBLISHED_AT)
            ->where(self::PUBLISHED_AT, '<=', now());
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function($q) {
            $q->whereNull(self::EXPIRES_AT)
                ->orWhere(self::EXPIRES_AT, '>', now());
        });
    }

    public function scopeViewable($query)
    {
        return $query->active()->published()->notExpired();
    }

    public function scopeByCategory($query, ListingCategoryEnum $category)
    {
        return $query->where(self::CATEGORY, $category);
    }

    public function scopeByCondition($query, ListingConditionEnum $condition)
    {
        return $query->where(self::CONDITION, $condition);
    }

    public function scopePriceRange($query, ?float $minPrice = null, ?float $maxPrice = null)
    {
        if ($minPrice !== null) {
            $query->where(self::PRICE, '>=', $minPrice);
        }
        if ($maxPrice !== null) {
            $query->where(self::PRICE, '<=', $maxPrice);
        }
        return $query;
    }

    public function scopeBySellerProfile($query, int $sellerProfileId)
    {
        return $query->where(self::SELLER_PROFILE_ID, $sellerProfileId);
    }

    public function scopeRequiresAppointment($query, bool $requiresAppointment = true)
    {
        return $query->where(self::REQUIRES_APPOINTMENT, $requiresAppointment);
    }

    public function scopeCanDeliverGlobally($query, bool $canDeliver = true)
    {
        return $query->where(self::CAN_DELIVER_GLOBALLY, $canDeliver);
    }

    public function scopeSearch($query, string $searchTerm)
    {
        return $query->where(function($q) use ($searchTerm) {
            $q->where(self::TITLE, 'LIKE', "%{$searchTerm}%")
                ->orWhere(self::DESCRIPTION, 'LIKE', "%{$searchTerm}%")
                ->orWhereJsonContains(self::TAGS, $searchTerm);
        });
    }

    public function scopeOrderByRelevance($query, string $searchTerm = null)
    {
        if ($searchTerm) {
            return $query->orderByRaw("
                CASE 
                    WHEN title LIKE ? THEN 1
                    WHEN title LIKE ? THEN 2
                    WHEN description LIKE ? THEN 3
                    ELSE 4
                END,
                views_count DESC,
                created_at DESC
            ", [
                $searchTerm . '%',
                '%' . $searchTerm . '%',
                '%' . $searchTerm . '%'
            ]);
        }

        return $query->orderBy(self::VIEWS_COUNT, 'desc')
            ->orderBy(self::CREATED_AT, 'desc');
    }

    public function scopeOrderByPrice($query, string $direction = 'asc')
    {
        return $query->orderBy(self::PRICE, $direction);
    }

    public function scopeOrderByDate($query, string $direction = 'desc')
    {
        return $query->orderBy(self::CREATED_AT, $direction);
    }

    public function scopeOrderByViews($query, string $direction = 'desc')
    {
        return $query->orderBy(self::VIEWS_COUNT, $direction);
    }
}