<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\SellerAppointments\SellerAppointment;
use App\Models\SellerProfiles\SellerProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ID = 'id';
    public const NAME = 'name';
    public const EMAIL = 'email';
    public const PASSWORD = 'password';
    public const EMAIL_VERIFIED_AT = 'email_verified_at';
    public const REMEMBER_TOKEN = 'remember_token';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const TABLE = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        self::NAME,
        self::EMAIL,
        self::PASSWORD,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        self::PASSWORD,
        self::REMEMBER_TOKEN,
    ];

    /** @see User::sellerProfileRelation() */
    const SELLER_PROFILE_RELATION = 'sellerProfileRelation';
    /** @see User::buyerAppointmentsRelation() */
    const BUYER_APPOINTMENTS_RELATION = 'buyerAppointmentsRelation';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            self::EMAIL_VERIFIED_AT => 'datetime',
            self::PASSWORD => 'hashed',
        ];
    }

    public function getId(): int
    {
        return $this->getAttribute(self::ID);
    }

    public function getName(): string
    {
        return $this->getAttribute(self::NAME);
    }

    public function getEmail(): string
    {
        return $this->getAttribute(self::EMAIL);
    }

    public function getTable(): string
    {
        return 'users';
    }

    public function sellerProfileRelation(): HasOne
    {
        return $this->hasOne(SellerProfile::class);
    }

    /**
     * Alias for sellerProfileRelation() to work with Laravel factories
     */
    public function sellerProfile(): HasOne
    {
        return $this->sellerProfileRelation();
    }

    public function buyerAppointmentsRelation(): HasMany
    {
        return $this->hasMany(SellerAppointment::class, 'buyer_id');
    }

    public function isSeller(): bool
    {
        return $this->relatedSellerProfile() !== null;
    }

    public function isVerifiedSeller(): bool
    {
        return $this->relatedSellerProfile() && $this->relatedSellerProfile()->getIsVerified();
    }

    public function hasActiveSellerAccount(): bool
    {
        return $this->relatedSellerProfile() && $this->relatedSellerProfile()->getIsActive();
    }

    public function relatedSellerProfile(): ?SellerProfile
    {
        return $this->{self::SELLER_PROFILE_RELATION};
    }

    /**
     * @return Collection<SellerAppointment>
     */
    public function relatedAppointments(): Collection
    {
        return $this->{self::BUYER_APPOINTMENTS_RELATION};
    }
}