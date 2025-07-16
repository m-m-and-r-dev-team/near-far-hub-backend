<?php

declare(strict_types=1);

namespace App\Models\Roles;

use App\Enums\Roles\RoleEnum;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    public const ID = 'id';
    public const NAME = 'name';
    public const DISPLAY_NAME = 'display_name';
    public const DESCRIPTION = 'description';
    public const PERMISSIONS = 'permissions';
    public const IS_ACTIVE = 'is_active';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const TABLE = 'roles';

    protected $fillable = [
        self::NAME,
        self::DISPLAY_NAME,
        self::DESCRIPTION,
        self::PERMISSIONS,
        self::IS_ACTIVE,
    ];

    protected $casts = [
        self::PERMISSIONS => 'array',
        self::IS_ACTIVE => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getId(): int
    {
        return $this->getAttribute(self::ID);
    }

    public function getName(): string
    {
        return $this->getAttribute(self::NAME);
    }

    public function getDisplayName(): string
    {
        return $this->getAttribute(self::DISPLAY_NAME);
    }

    public function getDescription(): ?string
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getPermissions(): ?array
    {
        return $this->getAttribute(self::PERMISSIONS);
    }

    public function getCreatedAt(): Carbon
    {
        return $this->getAttribute(self::CREATED_AT);
    }

    public function getUpdatedAt(): Carbon
    {
        return $this->getAttribute(self::UPDATED_AT);
    }

    public function getIsActive(): bool
    {
        return $this->getAttribute(self::IS_ACTIVE);
    }

    public function isBuyer(): bool
    {
        return $this->getName() === RoleEnum::BUYER->value;
    }

    public function isSeller(): bool
    {
        return $this->getName() === RoleEnum::SELLER->value;
    }

    public function isModerator(): bool
    {
        return $this->getName() === RoleEnum::MODERATOR->value;
    }

    public function isAdmin(): bool
    {
        return $this->getName() === RoleEnum::ADMIN->value;
    }

    public function canSell(): bool
    {
        return in_array($this->getName(), [RoleEnum::SELLER->value, RoleEnum::ADMIN->value]);
    }

    public function canModerate(): bool
    {
        return in_array($this->getName(), [RoleEnum::MODERATOR->value, RoleEnum::ADMIN->value]);
    }

    public function canAccessAdmin(): bool
    {
        return $this->getName() === RoleEnum::ADMIN->value;
    }

    public function getTable(): string
    {
        return self::TABLE;
    }
}