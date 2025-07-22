<?php

declare(strict_types=1);

namespace App\Services\Locations;

use App\Models\Locations\Country;
use App\Models\Locations\State;
use App\Models\Locations\City;
use App\Models\Locations\LocationCache;
use App\Services\External\GooglePlacesService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HybridLocationService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const EXTERNAL_API_THRESHOLD = 3; // Minimum query length for external API

    public function __construct(
        private readonly GooglePlacesService $googlePlacesService,
        private readonly Country $country,
        private readonly State $state,
        private readonly City $city
    ) {
    }

    /**
     * Get intelligent location suggestions with local + external API
     */
    public function getLocationSuggestions(string $input, int $limit = 10): array
    {
        if (strlen($input) < 2) {
            return ['data' => [], 'source' => 'none'];
        }

        $cacheKey = "suggestions:" . md5($input . $limit);
        $cached = LocationCache::getCached($cacheKey);

        if ($cached) {
            return $cached;
        }

        // Start with local suggestions
        $localSuggestions = $this->getLocalSuggestions($input, $limit);

        // If we have enough local results or query is too short, return local only
        if (count($localSuggestions) >= $limit || strlen($input) < self::EXTERNAL_API_THRESHOLD) {
            $result = [
                'data' => array_slice($localSuggestions, 0, $limit),
                'source' => 'local',
                'total_results' => count($localSuggestions)
            ];

            LocationCache::setCached($cacheKey, $input, 'autocomplete', $result, 'local');
            return $result;
        }

        // Get external suggestions to supplement
        $externalSuggestions = $this->getExternalSuggestions($input, $limit);

        // Merge and deduplicate
        $mergedSuggestions = $this->mergeAndDeduplicateSuggestions($localSuggestions, $externalSuggestions, $limit);

        $result = [
            'data' => $mergedSuggestions,
            'source' => 'hybrid',
            'local_count' => count($localSuggestions),
            'external_count' => count($externalSuggestions),
            'total_results' => count($mergedSuggestions)
        ];

        LocationCache::setCached($cacheKey, $input, 'autocomplete', $result, 'hybrid');
        return $result;
    }

    /**
     * Validate and enrich location selection
     */
    public function validateAndEnrichLocation(array $locationData): array
    {
        // If it's a Google Place, get full details
        if (isset($locationData['place_id']) && isset($locationData['source']) && $locationData['source'] === 'external') {
            return $this->enrichFromGooglePlace($locationData);
        }

        // If it's local data, enrich from database
        if (isset($locationData['city_id']) || isset($locationData['local_id'])) {
            return $this->enrichFromLocalData($locationData);
        }

        // If it's just a string, try to geocode it
        if (isset($locationData['address']) || isset($locationData['location_string'])) {
            return $this->enrichFromAddressString($locationData);
        }

        return $locationData;
    }

    /**
     * Geocode address using best available method
     */
    public function geocodeAddress(string $address): ?array
    {
        $cacheKey = "geocode:" . md5($address);
        $cached = LocationCache::getCached($cacheKey);

        if ($cached) {
            return $cached;
        }

        // Try Google geocoding first (most accurate)
        if ($this->googlePlacesService->isAvailable()) {
            try {
                $result = $this->googlePlacesService->geocodeAddress($address);
                if ($result) {
                    $enriched = array_merge($result, [
                        'source' => 'google_geocoding',
                        'cached_at' => now()->toISOString()
                    ]);

                    LocationCache::setCached($cacheKey, $address, 'geocode', $enriched, 'external', self::CACHE_TTL * 24);
                    return $enriched;
                }
            } catch (\Exception $e) {
                Log::warning('Google geocoding failed', ['address' => $address, 'error' => $e->getMessage()]);
            }
        }

        // Fallback to local geocoding (if we have coordinates in our database)
        $localResult = $this->geocodeFromLocalData($address);
        if ($localResult) {
            LocationCache::setCached($cacheKey, $address, 'geocode', $localResult, 'local');
            return $localResult;
        }

        return null;
    }

    /**
     * Save user location efficiently
     */
    public function saveUserLocation(int $userId, array $locationData): array
    {
        // Validate and enrich the location data first
        $enrichedLocation = $this->validateAndEnrichLocation($locationData);

        // Prepare data for database
        $dbData = $this->prepareLocationForDatabase($enrichedLocation);

        // Update user record
        $user = \App\Models\User::findOrFail($userId);
        $user->update($dbData);

        return [
            'success' => true,
            'location_data' => $enrichedLocation,
            'db_data' => $dbData
        ];
    }

    /**
     * Get popular locations for quick selection
     */
    public function getPopularLocations(int $limit = 20): array
    {
        $cacheKey = "popular_locations:" . $limit;
        $cached = LocationCache::getCached($cacheKey);

        if ($cached) {
            return $cached;
        }

        // Get cities with most users
        $popularCities = $this->city
            ->with(['country', 'state'])
            ->withCount('users')
            ->where('is_active', true)
            ->orderBy('users_count', 'desc')
            ->orderBy('population', 'desc')
            ->limit($limit)
            ->get();

        $result = [
            'data' => $popularCities->map(function ($city) {
                return [
                    'id' => $city->id,
                    'name' => $city->name,
                    'full_name' => $city->full_name,
                    'type' => 'city',
                    'source' => 'local',
                    'users_count' => $city->users_count,
                    'population' => $city->population,
                    'country' => $city->country->name,
                    'state' => $city->state?->name,
                    'data' => [
                        'city_id' => $city->id,
                        'state_id' => $city->state_id,
                        'country_id' => $city->country_id,
                        'latitude' => $city->latitude,
                        'longitude' => $city->longitude,
                    ]
                ];
            })->toArray(),
            'source' => 'local',
            'cached_at' => now()->toISOString()
        ];

        LocationCache::setCached($cacheKey, 'popular', 'popular', $result, 'local', self::CACHE_TTL * 6);
        return $result;
    }

    /**
     * Get local suggestions from database
     */
    private function getLocalSuggestions(string $input, int $limit): array
    {
        $suggestions = [];

        // Search cities first (most specific)
        $cities = $this->city
            ->with(['country', 'state'])
            ->where('name', 'LIKE', "%{$input}%")
            ->where('is_active', true)
            ->orderBy('population', 'desc')
            ->limit($limit)
            ->get();

        foreach ($cities as $city) {
            $suggestions[] = [
                'id' => $city->id,
                'place_id' => $city->google_place_id,
                'description' => $city->full_name,
                'main_text' => $city->name,
                'secondary_text' => ($city->state ? $city->state->name . ', ' : '') . $city->country->name,
                'type' => 'city',
                'source' => 'local',
                'data' => [
                    'city_id' => $city->id,
                    'state_id' => $city->state_id,
                    'country_id' => $city->country_id,
                    'latitude' => $city->latitude,
                    'longitude' => $city->longitude,
                ]
            ];
        }

        // If not enough results, add states
        if (count($suggestions) < $limit) {
            $states = $this->state
                ->with('country')
                ->where('name', 'LIKE', "%{$input}%")
                ->where('is_active', true)
                ->limit($limit - count($suggestions))
                ->get();

            foreach ($states as $state) {
                $suggestions[] = [
                    'id' => $state->id,
                    'description' => $state->name . ', ' . $state->country->name,
                    'main_text' => $state->name,
                    'secondary_text' => $state->country->name,
                    'type' => 'state',
                    'source' => 'local',
                    'data' => [
                        'state_id' => $state->id,
                        'country_id' => $state->country_id,
                    ]
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Get external suggestions from Google Places
     */
    private function getExternalSuggestions(string $input, int $limit): array
    {
        if (!$this->googlePlacesService->isAvailable()) {
            return [];
        }

        try {
            $googleSuggestions = $this->googlePlacesService->getAutocompleteSuggestions($input, [
                'types' => '(cities)',
                'language' => 'en'
            ]);

            return array_map(function ($suggestion) {
                return [
                    'id' => $suggestion['place_id'],
                    'place_id' => $suggestion['place_id'],
                    'description' => $suggestion['description'],
                    'main_text' => $suggestion['main_text'],
                    'secondary_text' => $suggestion['secondary_text'],
                    'type' => $this->determineLocationTypeFromGoogleTypes($suggestion['types']),
                    'source' => 'external',
                    'data' => [
                        'place_id' => $suggestion['place_id'],
                        'google_types' => $suggestion['types'],
                    ]
                ];
            }, array_slice($googleSuggestions, 0, $limit));

        } catch (\Exception $e) {
            Log::warning('External suggestions failed', ['input' => $input, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Merge and deduplicate suggestions intelligently
     */
    private function mergeAndDeduplicateSuggestions(array $local, array $external, int $limit): array
    {
        $merged = [];
        $seen = [];

        // Add local suggestions first (they're faster and more relevant)
        foreach ($local as $suggestion) {
            $key = strtolower(trim($suggestion['main_text']));
            if (!in_array($key, $seen)) {
                $merged[] = $suggestion;
                $seen[] = $key;
            }
        }

        // Add external suggestions if we need more
        foreach ($external as $suggestion) {
            if (count($merged) >= $limit) break;

            $key = strtolower(trim($suggestion['main_text']));
            if (!in_array($key, $seen)) {
                $merged[] = $suggestion;
                $seen[] = $key;
            }
        }

        return array_slice($merged, 0, $limit);
    }

    /**
     * Enrich location from Google Place details
     */
    private function enrichFromGooglePlace(array $locationData): array
    {
        try {
            $placeDetails = $this->googlePlacesService->getPlaceDetails($locationData['place_id']);

            if (!$placeDetails) {
                return $locationData;
            }

            return array_merge($locationData, [
                'formatted_address' => $placeDetails['formatted_address'],
                'latitude' => $placeDetails['latitude'],
                'longitude' => $placeDetails['longitude'],
                'address_components' => $placeDetails['address_components'],
                'google_types' => $placeDetails['types'],
                'enriched_at' => now()->toISOString(),
                'source' => 'external_enriched'
            ]);

        } catch (\Exception $e) {
            Log::warning('Failed to enrich Google Place', ['place_id' => $locationData['place_id'], 'error' => $e->getMessage()]);
            return $locationData;
        }
    }

    /**
     * Enrich location from local database
     */
    private function enrichFromLocalData(array $locationData): array
    {
        $enriched = $locationData;

        if (isset($locationData['city_id'])) {
            $city = $this->city->with(['country', 'state'])->find($locationData['city_id']);
            if ($city) {
                $enriched = array_merge($enriched, [
                    'city_name' => $city->name,
                    'state_name' => $city->state?->name,
                    'country_name' => $city->country->name,
                    'formatted_address' => $city->full_name,
                    'latitude' => $city->latitude,
                    'longitude' => $city->longitude,
                    'google_place_id' => $city->google_place_id,
                    'source' => 'local_enriched'
                ]);
            }
        }

        return $enriched;
    }

    /**
     * Enrich location from address string
     */
    private function enrichFromAddressString(array $locationData): array
    {
        $address = $locationData['address'] ?? $locationData['location_string'] ?? '';

        if (empty($address)) {
            return $locationData;
        }

        $geocoded = $this->geocodeAddress($address);

        if ($geocoded) {
            return array_merge($locationData, $geocoded, [
                'original_input' => $address,
                'source' => 'geocoded'
            ]);
        }

        return $locationData;
    }

    /**
     * Geocode from local database (basic implementation)
     */
    private function geocodeFromLocalData(string $address): ?array
    {
        // Simple matching against city names
        $city = $this->city
            ->with(['country', 'state'])
            ->where('name', 'LIKE', "%{$address}%")
            ->where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->first();

        if ($city) {
            return [
                'formatted_address' => $city->full_name,
                'latitude' => $city->latitude,
                'longitude' => $city->longitude,
                'source' => 'local_geocoding',
                'city_id' => $city->id,
                'state_id' => $city->state_id,
                'country_id' => $city->country_id,
            ];
        }

        return null;
    }

    /**
     * Prepare location data for database storage
     */
    private function prepareLocationForDatabase(array $locationData): array
    {
        return [
            'location_display' => $locationData['formatted_address'] ?? $locationData['description'] ?? null,
            'location_data' => $locationData,
            'country_id' => $locationData['data']['country_id'] ?? null,
            'state_id' => $locationData['data']['state_id'] ?? null,
            'city_id' => $locationData['data']['city_id'] ?? null,
            'latitude' => $locationData['latitude'] ?? null,
            'longitude' => $locationData['longitude'] ?? null,
            'google_place_id' => $locationData['place_id'] ?? $locationData['data']['place_id'] ?? null,
        ];
    }

    /**
     * Determine location type from Google Place types
     */
    private function determineLocationTypeFromGoogleTypes(array $types): string
    {
        if (in_array('country', $types)) return 'country';
        if (in_array('administrative_area_level_1', $types)) return 'state';
        if (in_array('locality', $types) || in_array('administrative_area_level_2', $types)) return 'city';
        return 'locality';
    }
}