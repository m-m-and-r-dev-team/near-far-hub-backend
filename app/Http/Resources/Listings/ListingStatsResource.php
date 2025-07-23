<?php

declare(strict_types=1);

namespace App\Http\Resources\Listings;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'totalListings' => $this->resource['total_listings'],
            'activeListings' => $this->resource['active_listings'],
            'draftListings' => $this->resource['draft_listings'],
            'soldListings' => $this->resource['sold_listings'],
            'totalViews' => $this->resource['total_views'],
            'totalFavorites' => $this->resource['total_favorites'],
            'avgViewsPerListing' => $this->resource['avg_views_per_listing'],
            'conversionRate' => $this->resource['total_listings'] > 0
                ? round(($this->resource['sold_listings'] / $this->resource['total_listings']) * 100, 2)
                : 0,
            'engagementRate' => $this->resource['total_listings'] > 0
                ? round(($this->resource['total_favorites'] / $this->resource['total_views']) * 100, 2)
                : 0,
        ];
    }
}