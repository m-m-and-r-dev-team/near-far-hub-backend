<?php

declare(strict_types=1);

namespace App\Models\Images;

use App\Enums\Images\ImageTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;

class Image extends Model
{
    use HasFactory;

    public const ID = 'id';
    public const IMAGEABLE_TYPE = 'imageable_type';
    public const IMAGEABLE_ID = 'imageable_id';
    public const TYPE = 'type';
    public const FILENAME = 'filename';
    public const ORIGINAL_NAME = 'original_name';
    public const PATH = 'path';
    public const URL = 'url';
    public const SIZE = 'size';
    public const MIME_TYPE = 'mime_type';
    public const WIDTH = 'width';
    public const HEIGHT = 'height';
    public const ALT_TEXT = 'alt_text';
    public const SORT_ORDER = 'sort_order';
    public const IS_PRIMARY = 'is_primary';
    public const IS_ACTIVE = 'is_active';
    public const METADATA = 'metadata';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::IMAGEABLE_TYPE,
        self::IMAGEABLE_ID,
        self::TYPE,
        self::FILENAME,
        self::ORIGINAL_NAME,
        self::PATH,
        self::URL,
        self::SIZE,
        self::MIME_TYPE,
        self::WIDTH,
        self::HEIGHT,
        self::ALT_TEXT,
        self::SORT_ORDER,
        self::IS_PRIMARY,
        self::IS_ACTIVE,
        self::METADATA,
    ];

    protected $casts = [
        self::TYPE => ImageTypeEnum::class,
        self::SIZE => 'integer',
        self::WIDTH => 'integer',
        self::HEIGHT => 'integer',
        self::SORT_ORDER => 'integer',
        self::IS_PRIMARY => 'boolean',
        self::IS_ACTIVE => 'boolean',
        self::METADATA => 'array',
    ];

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getId(): int
    {
        return $this->getAttribute(self::ID);
    }

    public function getType(): ImageTypeEnum
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getFilename(): string
    {
        return $this->getAttribute(self::FILENAME);
    }

    public function getOriginalName(): string
    {
        return $this->getAttribute(self::ORIGINAL_NAME);
    }

    public function getPath(): string
    {
        return $this->getAttribute(self::PATH);
    }

    public function getUrl(): string
    {
        return $this->getAttribute(self::URL);
    }

    public function getSize(): int
    {
        return $this->getAttribute(self::SIZE);
    }

    public function getMimeType(): string
    {
        return $this->getAttribute(self::MIME_TYPE);
    }

    public function getWidth(): ?int
    {
        return $this->getAttribute(self::WIDTH);
    }

    public function getHeight(): ?int
    {
        return $this->getAttribute(self::HEIGHT);
    }

    public function getAltText(): ?string
    {
        return $this->getAttribute(self::ALT_TEXT);
    }

    public function getSortOrder(): int
    {
        return $this->getAttribute(self::SORT_ORDER);
    }

    public function getIsPrimary(): bool
    {
        return $this->getAttribute(self::IS_PRIMARY);
    }

    public function getIsActive(): bool
    {
        return $this->getAttribute(self::IS_ACTIVE);
    }

    public function getMetadata(): ?array
    {
        return $this->getAttribute(self::METADATA);
    }

    public function getCreatedAt(): Carbon
    {
        return $this->getAttribute(self::CREATED_AT);
    }

    public function getUpdatedAt(): Carbon
    {
        return $this->getAttribute(self::UPDATED_AT);
    }

    public function getDimensions(): ?string
    {
        if ($this->getWidth() && $this->getHeight()) {
            return $this->getWidth() . 'x' . $this->getHeight();
        }
        return null;
    }

    public function getFormattedSize(): string
    {
        $size = $this->getSize();
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

    public function isImage(): bool
    {
        return str_starts_with($this->getMimeType(), 'image/');
    }

    public function scopeActive($query)
    {
        return $query->where(self::IS_ACTIVE, true);
    }

    public function scopePrimary($query)
    {
        return $query->where(self::IS_PRIMARY, true);
    }

    public function scopeOfType($query, ImageTypeEnum $type)
    {
        return $query->where(self::TYPE, $type);
    }

    public function scopeOrderedBySortOrder($query)
    {
        return $query->orderBy(self::SORT_ORDER)->orderBy(self::CREATED_AT);
    }
}