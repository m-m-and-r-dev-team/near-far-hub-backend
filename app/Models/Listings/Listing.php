<?php

declare(strict_types=1);

namespace App\Models\Listings;

use App\Enums\Listings\ListingCategoryEnum;
use App\Enums\Listings\ListingConditionEnum;
use App\Enums\Listings\ListingStatusEnum;
use App\Models\SellerProfiles\SellerProfile;
use App\Models\SellerAppointments\SellerAppointment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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
    ];

    protected $casts = [
        self::IMAGES => 'array',
        self::LOCATION => 'array',
        self::CAN_DELIVER_GLOBALLY => 'boolean',
        self::REQUIRES_APPOINTMENT => 'boolean',
        self::PRICE => 'decimal:2',
        self::PUBLISHED_AT => 'datetime',
        self::EXPIRES_AT => 'datetime',
        self::VIEWS_COUNT => 'integer',
        self::FAVORITES_COUNT => 'integer',
    ];

    /** @see Listing::sellerProfileRelation() */
    const SELLER_PROFILE_RELATION = 'sellerProfileRelation';
    /** @see Listing::appointmentsRelation() */
    const APPOINTMENTS_RELATION = 'appointmentsRelation';

    public function sellerProfileRelation(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class, self::SELLER_PROFILE_ID);
    }

    public function appointmentsRelation(): HasMany
    {
        return $this->hasMany(SellerAppointment::class, SellerAppointment::LISTING_ID, self::ID);
    }

    /** Scopes */
    public function scopeActive($query)
    {
        return $query->where(self::STATUS, ListingStatusEnum::STATUS_ACTIVE->value);
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull(self::PUBLISHED_AT)
            ->where(self::PUBLISHED_AT, '<=', now());
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull(self::EXPIRES_AT)
                ->orWhere(self::EXPIRES_AT, '>', now());
        });
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where(self::CATEGORY, $category);
    }

    public function scopeByCondition($query, string $condition)
    {
        return $query->where(self::CONDITION, $condition);
    }

    public function scopePriceRange($query, ?float $min = null, ?float $max = null)
    {
        if ($min !== null) {
            $query->where(self::PRICE, '>=', $min);
        }
        if ($max !== null) {
            $query->where(self::PRICE, '<=', $max);
        }
        return $query;
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where(self::TITLE, 'LIKE', "%{$search}%")
                ->orWhere(self::DESCRIPTION, 'LIKE', "%{$search}%");
        });
    }

    /** Relationships */
    public function relatedSellerProfile(): SellerProfile
    {
        return $this->{self::SELLER_PROFILE_RELATION};
    }

    /**
     * @return Collection<SellerAppointment>
     */
    public function relatedAppointments(): Collection
    {
        return $this->{self::APPOINTMENTS_RELATION};
    }

    /** Getters */
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

    public function getDescription(): ?string
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getPrice(): float
    {
        return (float) $this->getAttribute(self::PRICE);
    }

    public function getCategory(): string
    {
        return $this->getAttribute(self::CATEGORY);
    }

    public function getCondition(): ?string
    {
        return $this->getAttribute(self::CONDITION);
    }

    public function getImages(): array
    {
        return $this->getAttribute(self::IMAGES) ?? [];
    }

    public function getLocation(): array
    {
        return $this->getAttribute(self::LOCATION) ?? [];
    }

    public function getCanDeliverGlobally(): bool
    {
        return $this->getAttribute(self::CAN_DELIVER_GLOBALLY);
    }

    public function getRequiresAppointment(): bool
    {
        return $this->getAttribute(self::REQUIRES_APPOINTMENT);
    }

    public function getStatus(): string
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getViewsCount(): int
    {
        return $this->getAttribute(self::VIEWS_COUNT);
    }

    public function getFavoritesCount(): int
    {
        return $this->getAttribute(self::FAVORITES_COUNT);
    }

    public function getPublishedAt(): ?Carbon
    {
        return $this->getAttribute(self::PUBLISHED_AT);
    }

    public function getExpiresAt(): ?Carbon
    {
        return $this->getAttribute(self::EXPIRES_AT);
    }

    public function getCreatedAt(): Carbon
    {
        return $this->getAttribute(self::CREATED_AT);
    }

    public function getUpdatedAt(): Carbon
    {
        return $this->getAttribute(self::UPDATED_AT);
    }

    public function isActive(): bool
    {
        return $this->getStatus() === ListingStatusEnum::STATUS_ACTIVE->value;
    }

    public function isPublished(): bool
    {
        return $this->getPublishedAt() !== null && $this->getPublishedAt()->isPast();
    }

    public function isExpired(): bool
    {
        $expiresAt = $this->getExpiresAt();
        return $expiresAt !== null && $expiresAt->isPast();
    }

    public function canBeViewed(): bool
    {
        return $this->isActive() && $this->isPublished() && !$this->isExpired();
    }

    public function isPublishable(): bool
    {
        $status = ListingStatusEnum::from($this->getStatus());
        return $status->isPublishable();
    }

    public function isEditable(): bool
    {
        $status = ListingStatusEnum::from($this->getStatus());
        return $status->isEditable();
    }

    public function getMainImage(): ?string
    {
        $images = $this->getImages();
        return !empty($images) ? $images[0] : null;
    }

    public function getFormattedPrice(): string
    {
        return '€' . number_format($this->getPrice(), 2);
    }

    public function getLocationString(): string
    {
        $location = $this->getLocation();
        if (empty($location)) {
            return '';
        }

        $parts = [];
        if (!empty($location['city'])) {
            $parts[] = $location['city'];
        }
        if (!empty($location['country'])) {
            $parts[] = $location['country'];
        }

        return implode(', ', $parts);
    }

    public function incrementViews(): void
    {
        $this->increment(self::VIEWS_COUNT);
    }

    public function incrementFavorites(): void
    {
        $this->increment(self::FAVORITES_COUNT);
    }

    public function decrementFavorites(): void
    {
        $this->decrement(self::FAVORITES_COUNT);
    }

    public static function getAvailableConditions(): array
    {
        return ListingConditionEnum::getValues();
    }

    public static function getAvailableStatuses(): array
    {
        return ListingStatusEnum::getValues();
    }

    public static function getAvailableCategories(): array
    {
        return ListingCategoryEnum::getValues();
    }

    public static function getConditionLabels(): array
    {
        return ListingConditionEnum::getLabels();
    }

    public static function getStatusLabels(): array
    {
        return ListingStatusEnum::getLabels();
    }

    public static function getCategoryLabels(): array
    {
        return ListingCategoryEnum::getLabels();
    }

    /**
     * Get available sort options
     */
    public static function getAvailableSortOptions(): array
    {
        return [
            'newest' => 'Newest First',
            'oldest' => 'Oldest First',
            'price_low' => 'Price: Low to High',
            'price_high' => 'Price: High to Low',
            'most_viewed' => 'Most Viewed',
            'most_favorited' => 'Most Favorited',
            'relevance' => 'Most Relevant',
        ];
    }

    /**
     * Get condition quality score for sorting
     */
    public function getConditionQualityScore(): int
    {
        if (!$this->getCondition()) {
            return 0;
        }

        $condition = ListingConditionEnum::from($this->getCondition());
        return $condition->getQualityScore();
    }

    /**
     * Check if listing is in good condition
     */
    public function isInGoodCondition(): bool
    {
        if (!$this->getCondition()) {
            return false;
        }

        $condition = ListingConditionEnum::from($this->getCondition());
        return $condition->isGoodCondition();
    }

    /**
     * Check if listing requires condition field
     */
    public function requiresCondition(): bool
    {
        $category = ListingCategoryEnum::from($this->getCategory());
        return $category->requiresCondition();
    }

    /**
     * Get category icon
     */
    public function getCategoryIcon(): string
    {
        $category = ListingCategoryEnum::from($this->getCategory());
        return $category->getIcon();
    }

    /**
     * Get category label
     */
    public function getCategoryLabel(): string
    {
        $category = ListingCategoryEnum::from($this->getCategory());
        return $category->getLabel();
    }

    /**
     * Get condition label
     */
    public function getConditionLabel(): ?string
    {
        if (!$this->getCondition()) {
            return null;
        }

        $condition = ListingConditionEnum::from($this->getCondition());
        return $condition->getLabel();
    }

    /**
     * Get status label
     */
    public function getStatusLabel(): string
    {
        $status = ListingStatusEnum::from($this->getStatus());
        return $status->getLabel();
    }

    /**
     * Check if listing is new
     */
    public function isNew(): bool
    {
        return $this->getCondition() === ListingConditionEnum::CONDITION_NEW->value;
    }

    /**
     * Get time ago string
     */
    public function getTimeAgo(): string
    {
        return $this->getCreatedAt()->diffForHumans();
    }

    /**
     * Get days since published
     */
    public function getDaysSincePublished(): float
    {
        if (!$this->isPublished()) {
            return 0;
        }

        return $this->getPublishedAt()->diffInDays(now());
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->getExpiresAt()) {
            return null;
        }

        return max(0, now()->diffInDays($this->getExpiresAt()));
    }

    /**
     * Check if listing is expiring soon (within 7 days)
     */
    public function isExpiringSoon(): bool
    {
        $days = $this->getDaysUntilExpiry();
        return $days !== null && $days <= 7;
    }
}