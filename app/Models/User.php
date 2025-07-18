<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\SellerAppointments\SellerAppointment;
use App\Models\SellerProfiles\SellerProfile;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use App\Enums\Roles\RoleEnum;
use App\Models\Roles\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin Builder
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ID = 'id';
    public const NAME = 'name';
    public const EMAIL = 'email';
    public const PASSWORD = 'password';
    public const EMAIL_VERIFIED_AT = 'email_verified_at';
    public const REMEMBER_TOKEN = 'remember_token';
    public const ROLE_ID = 'role_id';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        self::NAME,
        self::EMAIL,
        self::PASSWORD,
        self::ROLE_ID,
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
    /** @see User::roleRelation() */
    const ROLE_RELATION = 'roleRelation';

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

    public function roleRelation(): BelongsTo
    {
        return $this->belongsTo(Role::class,User::ROLE_ID, Role::ID);
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

    public function getEmailVerifiedAt(): Carbon
    {
        return $this->getAttribute(self::EMAIL_VERIFIED_AT);
    }

    public function getCreatedAt(): Carbon
    {
        return $this->getAttribute(self::CREATED_AT);
    }

    public function getUpdatedAt(): Carbon
    {
        return $this->getAttribute(self::UPDATED_AT);
    }

    public function getRoleId(): int
    {
        return $this->getAttribute(self::ROLE_ID);
    }

    public function getRoleName(): string
    {
        return $this->relatedRole()->getName();
    }

    public function getRoleDisplayName(): string
    {
        return $this->relatedRole()->getDisplayName();
    }

    // Role checking methods
    public function hasRole(string $roleName): bool
    {
        return $this->getRoleName() === $roleName;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->getRoleName(), $roles);
    }

    public function isBuyer(): bool
    {
        return $this->hasRole(RoleEnum::BUYER->value);
    }

    public function isSeller(): bool
    {
        return $this->hasRole(RoleEnum::SELLER->value);
    }

    public function isModerator(): bool
    {
        return $this->hasRole(RoleEnum::MODERATOR->value);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(RoleEnum::ADMIN->value);
    }

    // Permission checking methods
    public function canSell(): bool
    {
        return $this->relatedRole()->canSell();
    }

    public function canModerate(): bool
    {
        return $this->relatedRole()->canModerate();
    }

    public function canAccessAdmin(): bool
    {
        return $this->relatedRole()->canAccessAdmin();
    }

    public function canUpgradeToSeller(): bool
    {
        return $this->isBuyer();
    }

    public function upgradeToSeller(): void
    {
        if ($this->canUpgradeToSeller()) {
            $sellerRole = Role::where(Role::NAME, RoleEnum::SELLER->value)->first();
            if ($sellerRole) {
                $this->update([self::ROLE_ID => $sellerRole->getId()]);
            }
        }
    }

    public function relatedRole(): Role
    {
        return $this->{self::ROLE_RELATION};
    }

    public function getPassword(): string
    {
        return $this->getAttribute(self::PASSWORD);
    }

    public function getTable(): string
    {
        return 'users';
    }

    public function sellerProfileRelation(): HasOne
    {
        return $this->hasOne(SellerProfile::class);
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

    public static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}