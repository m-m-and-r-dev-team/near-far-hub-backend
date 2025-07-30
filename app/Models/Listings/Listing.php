<?php

declare(strict_types=1);

namespace App\Models\Listings;

use App\Models\Categories\Category;
use App\Models\Images\Image;
use App\Models\SellerProfiles\SellerProfile;
use App\Models\SellerAppointments\SellerAppointment;
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
class Listing extends Model
{
    use HasFactory;

    public const ID = 'id';
    public const SELLER_PROFILE_ID = 'seller_profile_id';
    public const CATEGORY_ID = 'category_id';
    public const TITLE = 'title';
    public const SLUG = 'slug';
    public const DESCRIPTION = 'description';
    public const PRICE = 'price';
    public const ORIGINAL_PRICE = 'original_price';
    public const CONDITION = 'condition';
    public const BRAND = 'brand';
    public const MODEL = 'model';
    public const YEAR = 'year';
    public const LOCATION_DATA = 'location_data';
    public const LOCATION_DISPLAY = 'location_display';
    public const LATITUDE = 'latitude';
    public const LONGITUDE = 'longitude';
    public const CAN_DELIVER_GLOBALLY = 'can_deliver_globally';
    public const DELIVERY_OPTIONS = 'delivery_options';
    public const REQUIRES_APPOINTMENT = 'requires_appointment';
    public const CATEGORY_ATTRIBUTES = 'category_attributes';
    public const TAGS = 'tags';
    public const STATUS = 'status';
    public const FEATURED_UNTIL = 'featured_until';
    public const PUBLISHED_AT = 'published_at';
    public const EXPIRES_AT = 'expires_at';
    public const VIEWS_COUNT = 'views_count';
    public const FAVORITES_COUNT = 'favorites_count';
    public const CONTACT_COUNT = 'contact_count';
    public const META_TITLE = 'meta_title';
    public const META_DESCRIPTION = 'meta_description';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SOLD = 'sold';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_DELETED = 'deleted';

    // Condition constants
    public const CONDITION_NEW = 'new';
    public const CONDITION_LIKE_NEW = 'like_new';
    public const CONDITION_EXCELLENT = 'excellent';
    public const CONDITION_GOOD = 'good';
    public const CONDITION_FAIR = 'fair';
    public const CONDITION_POOR = 'poor';

    protected $fillable = [
        self::SELLER_PROFILE_ID,
        self::CATEGORY_ID,
        self::TITLE,
        self::SLUG,
        self::DESCRIPTION,
        self::PRICE,
        self::ORIGINAL_PRICE,
        self::CONDITION,
        self::BRAND,
        self::MODEL,
        self::YEAR,
        self::LOCATION_DATA,
        self::LOCATION_DISPLAY,
        self::LATITUDE,
        self::LONGITUDE,
        self::CAN_DELIVER_GLOBALLY,
        self::DELIVERY_OPTIONS,
        self::REQUIRES_APPOINTMENT,
        self::CATEGORY_ATTRIBUTES,
        self::TAGS,
        self::STATUS,
        self::FEATURED_UNTIL,
        self::PUBLISHED_AT,
        self::EXPIRES_AT,
        self::VIEWS_COUNT,
        self::FAVORITES_COUNT,
        self::CONTACT_COUNT,
        self::META_TITLE,
        self::META_DESCRIPTION,
    ];

    protected $casts = [
        self::PRICE => 'decimal:2',
        self::ORIGINAL_PRICE => 'decimal:2',
        self::YEAR => 'integer',
        self::LATITUDE => 'decimal:8',
        self::LONGITUDE => 'decimal:8',
        self::CAN_DELIVER_GLOBALLY => 'boolean',
        self::REQUIRES_APPOINTMENT => 'boolean',
        self::LOCATION_DATA => 'array',
        self::DELIVERY_OPTIONS => 'array',
        self::CATEGORY_ATTRIBUTES => 'array',
        self::TAGS => 'array',
        self::VIEWS_COUNT => 'integer',
        self::FAVORITES_COUNT => 'integer',
        self::CONTACT_COUNT => 'integer',
        self::FEATURED_UNTIL => 'datetime',
        self::PUBLISHED_AT => 'datetime',
        self::EXPIRES_AT => 'datetime',
    ];

    /** @see Listing::sellerProfileRelation() */
    const SELLER_PROFILE_RELATION = 'sellerProfileRelation';
    /** @see Listing::categoryRelation() */
    const CATEGORY_RELATION = 'categoryRelation';
    /** @see Listing::imagesRelation() */
    const IMAGES_RELATION = 'imagesRelation';
    /** @see Listing::appointmentsRelation() */
    const APPOINTMENTS_RELATION = 'appointmentsRelation';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($listing) {
            if (empty($listing->slug)) {
                $listing->slug = $listing->generateUniqueSlug($listing->title);
            }

            // Set default expiration (30 days from now)
            if (empty($listing->expires_at)) {
                $listing->expires_at = now()->addDays(30);
            }

            // Auto-generate meta fields if not provided
            if (empty($listing->meta_title)) {
                $listing->meta_title = Str::limit($listing->title, 60);
            }
            if (empty($listing->meta_description)) {
                $listing->meta_description = Str::limit(strip_tags($listing->description), 160);
            }
        });

        static::updating(function ($listing) {
            if ($listing->isDirty('title') && empty($listing->slug)) {
                $listing->slug = $listing->generateUniqueSlug($listing->title);
            }

            // Update published_at when status changes to active
            if ($listing->isDirty('status') && $listing->status === self::STATUS_ACTIVE && !$listing->published_at) {
                $listing->published_at = now();
            }
        });
    }

    // Relationships
    public function sellerProfileRelation(): BelongsTo
    {
        return $this->belongsTo(SellerProfile::class, self::SELLER_PROFILE_ID);
    }

    public function categoryRelation(): BelongsTo
    {
        return $this->belongsTo(Category::class, self::CATEGORY_ID);
    }

    public function imagesRelation(): HasMany
    {
        return $this->hasMany(Image::class, 'related_id')
            ->where('type', 'listing')
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'asc');
    }

    public function appointmentsRelation(): HasMany
    {
        return $this->hasMany(SellerAppointment::class, 'listing_id');
    }

    // Accessors
    public function getId(): int
    {
        return $this->getAttribute(self::ID);
    }

    public function getSellerProfileId(): int
    {
        return $this->getAttribute(self::SELLER_PROFILE_ID);
    }

    public function getCategoryId(): int
    {
        return $this->getAttribute(self::CATEGORY_ID);
    }

    public function getTitle(): string
    {
        return $this->getAttribute(self::TITLE);
    }

    public function getSlug(): string
    {
        return $this->getAttribute(self::SLUG);
    }

    public function getDescription(): ?string
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getPrice(): float
    {
        return (float) $this->getAttribute(self::PRICE);
    }

    public function getOriginalPrice(): ?float
    {
        $price = $this->getAttribute(self::ORIGINAL_PRICE);
        return $price ? (float) $price : null;
    }

    public function getCondition(): ?string
    {
        return $this->getAttribute(self::CONDITION);
    }

    public function getBrand(): ?string
    {
        return $this->getAttribute(self::BRAND);
    }

    public function getModel(): ?string
    {
        return $this->getAttribute(self::MODEL);
    }

    public function getYear(): ?int
    {
        return $this->getAttribute(self::YEAR);
    }

    public function getLocationData(): array
    {
        return $this->getAttribute(self::LOCATION_DATA) ?? [];
    }

    public function getLocationDisplay(): ?string
    {
        return $this->getAttribute(self::LOCATION_DISPLAY);
    }

    public function getLatitude(): ?float
    {
        $lat = $this->getAttribute(self::LATITUDE);
        return $lat ? (float) $lat : null;
    }

    public function getLongitude(): ?float
    {
        $lng = $this->getAttribute(self::LONGITUDE);
        return $lng ? (float) $lng : null;
    }

    public function getCanDeliverGlobally(): bool
    {
        return $this->getAttribute(self::CAN_DELIVER_GLOBALLY) ?? false;
    }

    public function getDeliveryOptions(): array
    {
        return $this->getAttribute(self::DELIVERY_OPTIONS) ?? [];
    }

    public function getRequiresAppointment(): bool
    {
        return $this->getAttribute(self::REQUIRES_APPOINTMENT) ?? false;
    }

    public function getCategoryAttributes(): array
    {
        return $this->getAttribute(self::CATEGORY_ATTRIBUTES) ?? [];
    }

    public function getTags(): array
    {
        return $this->getAttribute(self::TAGS) ?? [];
    }

    public function getStatus(): string
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getViewsCount(): int
    {
        return $this->getAttribute(self::VIEWS_COUNT) ?? 0;
    }

    public function getFavoritesCount(): int
    {
        return $this->getAttribute(self::FAVORITES_COUNT) ?? 0;
    }

    public function getContactCount(): int
    {
        return $this->getAttribute(self::CONTACT_COUNT) ?? 0;
    }

    public function getFeaturedUntil(): ?Carbon
    {
        return $this->getAttribute(self::FEATURED_UNTIL);
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

    // Helper methods
    public function relatedSellerProfile(): SellerProfile
    {
        return $this->{self::SELLER_PROFILE_RELATION};
    }

    public function relatedCategory(): Category
    {
        return $this->{self::CATEGORY_RELATION};
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

    public function getPrimaryImage(): ?Image
    {
        return $this->relatedImages()->where('is_primary', true)->first();
    }

    public function getImageUrls(): array
    {
        return $this->relatedImages()->map(function ($image) {
            return \Storage::disk('s3')->url('listing/' . $image->getImageLink());
        })->toArray();
    }

    public function getPrimaryImageUrl(): ?string
    {
        $primaryImage = $this->getPrimaryImage();
        return $primaryImage ? \Storage::disk('s3')->url('listing/' . $primaryImage->getImageLink()) : null;
    }

    public function getCoordinates(): ?array
    {
        if ($this->getLatitude() && $this->getLongitude()) {
            return [
                'latitude' => $this->getLatitude(),
                'longitude' => $this->getLongitude()
            ];
        }
        return null;
    }

    public function hasDiscount(): bool
    {
        return $this->getOriginalPrice() && $this->getOriginalPrice() > $this->getPrice();
    }

    public function getDiscountPercentage(): ?int
    {
        if (!$this->hasDiscount()) {
            return null;
        }

        $originalPrice = $this->getOriginalPrice();
        $currentPrice = $this->getPrice();

        return (int) round((($originalPrice - $currentPrice) / $originalPrice) * 100);
    }

    public function isActive(): bool
    {
        return $this->getStatus() === self::STATUS_ACTIVE;
    }

    public function isDraft(): bool
    {
        return $this->getStatus() === self::STATUS_DRAFT;
    }

    public function isSold(): bool
    {
        return $this->getStatus() === self::STATUS_SOLD;
    }

    public function isExpired(): bool
    {
        return $this->getStatus() === self::STATUS_EXPIRED ||
            ($this->getExpiresAt() && $this->getExpiresAt()->isPast());
    }

    public function isFeatured(): bool
    {
        return $this->getFeaturedUntil() && $this->getFeaturedUntil()->isFuture();
    }

    public function canEdit(): bool
    {
        return in_array($this->getStatus(), [self::STATUS_DRAFT, self::STATUS_ACTIVE, self::STATUS_EXPIRED]);
    }

    public function canDelete(): bool
    {
        return !in_array($this->getStatus(), [self::STATUS_SOLD]);
    }

    public function incrementViews(): void
    {
        $this->increment(self::VIEWS_COUNT);
    }

    public function incrementContacts(): void
    {
        $this->increment(self::CONTACT_COUNT);
    }

    public function markAsSold(): void
    {
        $this->update([
            self::STATUS => self::STATUS_SOLD,
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update([
            self::STATUS => self::STATUS_EXPIRED,
        ]);
    }

    public function renewListing(int $days = 30): void
    {
        $this->update([
            self::STATUS => self::STATUS_ACTIVE,
            self::EXPIRES_AT => now()->addDays($days),
            self::PUBLISHED_AT => now(),
        ]);
    }

    public function makeFeatureForsed(int $days = 7): void
    {
        $this->update([
            self::FEATURED_UNTIL => now()->addDays($days),
        ]);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(self::STATUS, self::STATUS_ACTIVE);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull(self::PUBLISHED_AT)
            ->where(self::PUBLISHED_AT, '<=', now());
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull(self::EXPIRES_AT)
                ->orWhere(self::EXPIRES_AT, '>', now());
        });
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->whereNotNull(self::FEATURED_UNTIL)
            ->where(self::FEATURED_UNTIL, '>', now());
    }

    public function scopeInCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where(self::CATEGORY_ID, $categoryId);
    }

    public function scopeByCondition(Builder $query, string $condition): Builder
    {
        return $query->where(self::CONDITION, $condition);
    }

    public function scopePriceRange(Builder $query, ?float $minPrice = null, ?float $maxPrice = null): Builder
    {
        if ($minPrice !== null) {
            $query->where(self::PRICE, '>=', $minPrice);
        }
        if ($maxPrice !== null) {
            $query->where(self::PRICE, '<=', $maxPrice);
        }
        return $query;
    }

    public function scopeWithLocation(Builder $query, float $latitude, float $longitude, float $radiusKm = 50): Builder
    {
        return $query->whereNotNull(self::LATITUDE)
            ->whereNotNull(self::LONGITUDE)
            ->selectRaw("
                *,
                (6371 * acos(
                    cos(radians(?)) * 
                    cos(radians(" . self::LATITUDE . ")) * 
                    cos(radians(" . self::LONGITUDE . ") - radians(?)) + 
                    sin(radians(?)) * 
                    sin(radians(" . self::LATITUDE . "))
                )) AS distance
            ", [$latitude, $longitude, $latitude])
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance');
    }

    public function scopePopular(Builder $query, int $days = 30): Builder
    {
        return $query->where(self::CREATED_AT, '>=', now()->subDays($days))
            ->orderByDesc(self::VIEWS_COUNT)
            ->orderByDesc(self::FAVORITES_COUNT);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc(self::PUBLISHED_AT);
    }

    public function scopeWithImages(Builder $query): Builder
    {
        return $query->with([self::IMAGES_RELATION]);
    }

    public function scopeWithSellerAndCategory(Builder $query): Builder
    {
        return $query->with([
            self::SELLER_PROFILE_RELATION . '.userRelation',
            self::CATEGORY_RELATION
        ]);
    }

    public function scopeSearchByText(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where(self::TITLE, 'LIKE', "%{$search}%")
                ->orWhere(self::DESCRIPTION, 'LIKE', "%{$search}%")
                ->orWhere(self::BRAND, 'LIKE', "%{$search}%")
                ->orWhere(self::MODEL, 'LIKE', "%{$search}%")
                ->orWhereJsonContains(self::TAGS, $search);
        });
    }

    // Static methods
    public static function findBySlug(string $slug): ?self
    {
        return static::where(self::SLUG, $slug)->first();
    }

    public static function getConditions(): array
    {
        return [
            self::CONDITION_NEW => 'New',
            self::CONDITION_LIKE_NEW => 'Like New',
            self::CONDITION_EXCELLENT => 'Excellent',
            self::CONDITION_GOOD => 'Good',
            self::CONDITION_FAIR => 'Fair',
            self::CONDITION_POOR => 'Poor',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING => 'Pending Review',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_SOLD => 'Sold',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_DELETED => 'Deleted',
        ];
    }

    // Private methods
    private function generateUniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        while (static::where(self::SLUG, $slug)->where(self::ID, '!=', $this->getId() ?? 0)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        return $slug;
    }
}