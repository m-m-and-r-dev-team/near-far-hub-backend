<?php

declare(strict_types=1);

namespace App\Models\Locations;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LocationCache extends Model
{
    protected $table = 'location_cache';

    protected $fillable = [
        'cache_key', 'query', 'type', 'data', 'source', 'expires_at'
    ];

    protected $casts = [
        'data' => 'array',
        'expires_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public static function getCached(string $key): ?array
    {
        $cached = static::where('cache_key', $key)
            ->where('expires_at', '>', now())
            ->first();

        return $cached?->data;
    }

    public static function setCached(string $key, string $query, string $type, array $data, string $source = 'hybrid', int $ttl = 3600): void
    {
        static::updateOrCreate(
            ['cache_key' => $key],
            [
                'query' => $query,
                'type' => $type,
                'data' => $data,
                'source' => $source,
                'expires_at' => now()->addSeconds($ttl),
            ]
        );
    }
}