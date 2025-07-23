<?php

declare(strict_types=1);

namespace App\Models\SellerProfiles;

use App\Models\ListingFees\ListingFee;
use App\Models\SellerAppointments\SellerAppointment;
use App\Models\SellerAvailabilities\SellerAvailability;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class SellerProfile extends Model
{
    use HasFactory;

    public const ID = 'id';
    public const USER_ID = 'user_id';
    public const BUSINESS_NAME = 'business_name';
    public const BUSINESS_DESCRIPTION = 'business_description';
    public const BUSINESS_TYPE = 'business_type';
    public const PHONE = 'phone';
    public const ADDRESS = 'address';
    public const CITY = 'city';
    public const POSTAL_CODE = 'postal_code';
    public const COUNTRY = 'country';
    public const LISTING_FEE_BALANCE = 'listing_fee_balance';
    public const IS_ACTIVE = 'is_active';
    public const IS_VERIFIED = 'is_verified';
    public const VERIFICATION_DOCUMENTS = 'verification_documents';
    public const VERIFIED_AT = 'verified_at';
    public const DAY_OF_WEEK = 'day_of_week';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::USER_ID,
        self::BUSINESS_NAME,
        self::BUSINESS_DESCRIPTION,
        self::BUSINESS_TYPE,
        self::PHONE,
        self::ADDRESS,
        self::CITY,
        self::POSTAL_CODE,
        self::COUNTRY,
        self::LISTING_FEE_BALANCE,
        self::IS_ACTIVE,
        self::IS_VERIFIED,
        self::VERIFICATION_DOCUMENTS,
        self::VERIFIED_AT,
    ];

    protected $casts = [
        self::VERIFICATION_DOCUMENTS => 'array',
        self::VERIFIED_AT => 'datetime',
        self::IS_ACTIVE => 'boolean',
        self::IS_VERIFIED => 'boolean',
        self::LISTING_FEE_BALANCE => 'decimal:2',
    ];

    /** @see SellerProfile::userRelation() */
    const USER_RELATION = 'userRelation';
    /** @see SellerProfile::availabilityRelation() */
    const AVAILABILITY_RELATION = 'availabilityRelation';
    /** @see SellerProfile::appointmentsRelation() */
    const APPOINTMENTS_RELATION = 'appointmentsRelation';
    /** @see SellerProfile::listingFeesRelation() */
    const LISTING_FEES_RELATION = 'listingFeesRelation';
    /** @see SellerProfile::imagesRelation() */
    const IMAGES_RELATION = 'imagesRelation';
    /** @see SellerProfile::listingsRelation() */
    const LISTINGS_RELATION = 'listingsRelation';

    public function userRelation(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function availabilityRelation(): HasMany
    {
        return $this->hasMany(SellerAvailability::class);
    }

    public function appointmentsRelation(): HasMany
    {
        return $this->hasMany(SellerAppointment::class);
    }

    public function listingFeesRelation(): HasMany
    {
        return $this->hasMany(ListingFee::class);
    }

    public function getFullAddressAttribute(): string
    {
        return collect([$this->getAddress(), $this->getCity(), $this->getPostalCode(), $this->getCountry()])
            ->filter()
            ->implode(', ');
    }

    public function isAvailableOn(string $dayOfWeek): bool
    {
        return $this->availabilityRelation()
            ->where(self::DAY_OF_WEEK, strtolower($dayOfWeek))
            ->where(self::IS_ACTIVE, true)
            ->exists();
    }

    public function getAvailabilityFor(string $dayOfWeek): ?SellerAvailability
    {
        return $this->relatedAvailability()
            ->where(self::DAY_OF_WEEK, strtolower($dayOfWeek))
            ->where(self::IS_ACTIVE, true)
            ->first();
    }

    public function relatedUser(): ?User
    {
        return $this->{self::USER_RELATION};
    }

    /**
     * @return Collection<SellerAvailability>
     */
    public function relatedAvailability(): Collection
    {
        return $this->{self::AVAILABILITY_RELATION};
    }

    /**
     * @return Collection<SellerAppointment>
     */
    public function relatedAppointments(): Collection
    {
        return $this->{self::APPOINTMENTS_RELATION};
    }

    /**
     * @return Collection<ListingFee>
     */
    public function relatedListingFees(): Collection
    {
        return $this->{self::LISTING_FEES_RELATION};
    }

    public function getIsVerified(): bool
    {
        return $this->getAttribute(self::IS_VERIFIED);
    }

    public function getIsActive(): bool
    {
        return $this->getAttribute(self::IS_ACTIVE);
    }

    public function getAddress(): string
    {
        return $this->getAttribute(self::ADDRESS);
    }

    public function getCity(): string
    {
        return $this->getAttribute(self::CITY);
    }

    public function getPostalCode(): string
    {
        return $this->getAttribute(self::POSTAL_CODE);
    }

    public function getCountry(): string
    {
        return $this->getAttribute(self::COUNTRY);
    }

    public function getId(): int
    {
        return $this->getAttribute(self::ID);
    }

    public function getListingFeeBalance(): float
    {
        return $this->getAttribute(self::LISTING_FEE_BALANCE);
    }

    public function imagesRelation(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('created_at');
    }

    public function listingsRelation(): HasMany
    {
        return $this->hasMany(Listing::class, 'seller_profile_id');
    }

    /**
     * @return Collection<Image>
     */
    public function relatedImages(): Collection
    {
        return $this->{self::IMAGES_RELATION};
    }

    /**
     * @return Collection<Listing>
     */
    public function relatedListings(): Collection
    {
        return $this->{self::LISTINGS_RELATION};
    }

    public function getVerificationImages(): Collection
    {
        return $this->relatedImages()
            ->where('type', \App\Enums\Images\ImageTypeEnum::SELLER_VERIFICATION);
    }

    public function getActiveListingsCount(): int
    {
        return $this->relatedListings()
            ->where('status', \App\Enums\Listings\ListingStatusEnum::ACTIVE)
            ->count();
    }

    public function getTotalListingsCount(): int
    {
        return $this->relatedListings()->count();
    }

    public function getSoldListingsCount(): int
    {
        return $this->relatedListings()
            ->where('status', \App\Enums\Listings\ListingStatusEnum::SOLD)
            ->count();
    }

    public function getTotalViews(): int
    {
        return $this->relatedListings()->sum('views_count');
    }

    public function getTotalFavorites(): int
    {
        return $this->relatedListings()->sum('favorites_count');
    }
}