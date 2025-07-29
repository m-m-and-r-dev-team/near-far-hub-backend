<?php

declare(strict_types=1);

namespace App\Models\Images;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Builder
 */
class Image extends Model
{
    const ID = 'id';
    const IMAGEABLE_TYPE = 'imageable_type';
    const IMAGEABLE_ID = 'imageable_id';
    const IMAGE_LINK = 'image_link';
    const TYPE = 'type';
    const ALT_TEXT = 'alt_text';
    const SORT_ORDER = 'sort_order';
    const IS_PRIMARY = 'is_primary';
    const IS_ACTIVE = 'is_active';
    const METADATA = 'metadata';
    const WIDTH = 'width';
    const HEIGHT = 'height';
    const FILE_SIZE = 'file_size';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        self::IMAGEABLE_TYPE,
        self::IMAGEABLE_ID,
        self::IMAGE_LINK,
        self::TYPE,
        self::ALT_TEXT,
        self::SORT_ORDER,
        self::IS_PRIMARY,
        self::IS_ACTIVE,
        self::METADATA,
        self::WIDTH,
        self::HEIGHT,
        self::FILE_SIZE,
    ];

    protected $casts = [
        self::IS_PRIMARY => 'boolean',
        self::IS_ACTIVE => 'boolean',
        self::METADATA => 'array',
        self::SORT_ORDER => 'integer',
        self::WIDTH => 'integer',
        self::HEIGHT => 'integer',
        self::FILE_SIZE => 'integer',
    ];

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getId(): int
    {
        return $this->getAttribute(self::ID);
    }

    public function getImageableId(): int
    {
        return $this->getAttribute(self::IMAGEABLE_ID);
    }

    public function getImageLink(): string
    {
        return $this->getAttribute(self::IMAGE_LINK);
    }

    public function getType(): string
    {
        return $this->getAttribute(self::TYPE);
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

    public function getWidth(): ?int
    {
        return $this->getAttribute(self::WIDTH);
    }

    public function getHeight(): ?int
    {
        return $this->getAttribute(self::HEIGHT);
    }

    public function getFileSize(): ?int
    {
        return $this->getAttribute(self::FILE_SIZE);
    }

    public function getCreatedAt(): Carbon
    {
        return $this->getAttribute(self::CREATED_AT);
    }

    public function getUrl(): string
    {
        return Storage::disk('s3')->url($this->getType() . '/' . $this->getImageLink());
    }

    public function getFullPath(): string
    {
        return $this->getType() . '/' . $this->getImageLink();
    }

    public function getThumbnailUrl(string $size = 'medium'): ?string
    {
        $metadata = $this->getMetadata();
        $thumbnails = $metadata['thumbnails'] ?? [];

        if (isset($thumbnails[$size])) {
            return $thumbnails[$size]['url'];
        }

        return $this->getUrl();
    }

    public function getFormattedFileSize(): string
    {
        $size = $this->getFileSize();
        if (!$size) return 'Unknown';

        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    public function scopeActive($query)
    {
        return $query->where(self::IS_ACTIVE, true);
    }

    public function scopePrimary($query)
    {
        return $query->where(self::IS_PRIMARY, true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where(self::TYPE, $type);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy(self::SORT_ORDER)->orderBy(self::CREATED_AT);
    }
}