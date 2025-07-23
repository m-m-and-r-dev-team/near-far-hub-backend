<?php

declare(strict_types=1);

namespace App\Services\Locations;

use App\Models\Locations\Country;
use App\Models\Locations\State;
use App\Models\Locations\City;
use App\Models\Locations\LocationCache;
use App\Services\External\GooglePlacesService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class HybridLocationService
{
    private array $popularLocations;
    private array $regions;
    private array $searchOptions;
    private array $googleConfig;

    public function __construct(
        private readonly GooglePlacesService $googlePlacesService,
        private readonly Country $country,
        private readonly State $state,
        private readonly City $city
    ) {
        $this->initializeFromConfig();
    }

    /**
     * Initialize service from configuration
     */
    private function initializeFromConfig(): void
    {
        $this->popularLocations = Config::get('locations.popular_locations', []);
        $this->regions = Config::get('locations.regions', []);
        $this->searchOptions = Config::get('locations.search_options', []);
        $this->googleConfig = Config::get('locations.google_places', []);

        // Set defaults if config is missing
        $this->searchOptions = array_merge([
            'min_query_length' => 2,
            'max_results' => 10,
            'cache_ttl' => 3600,
            'enable_fuzzy_search' => true,
            'boost_local_results' => true,
            'local_boost_factor' => 200,
            'priority_weights' => [
                'exact_match' => 1000,
                'starts_with' => 500,
                'contains' => 200,
                'population_factor' => 30,
                'name_length_bonus' => 50,
            ]
        ], $this->searchOptions);
    }

    /**
     * Get intelligent location suggestions
     */
    public function getLocationSuggestions(string $input, int $limit = 10): array
    {
        $limit = min($limit, $this->searchOptions['max_results']);

        if (strlen($input) < $this->searchOptions['min_query_length']) {
            return ['data' => [], 'source' => 'none'];
        }

        $cacheKey = "suggestions:" . md5($input . $limit);
        $cached = LocationCache::getCached($cacheKey);

        if ($cached) {
            return $cached;
        }

        // Step 1: Search popular locations from config
        $configSuggestions = $this->searchConfigLocations($input, $limit);

        // Step 2: Search existing database
        $localSuggestions = $this->getLocalSuggestions($input, max(0, $limit - count($configSuggestions)));

        // Step 3: Get external suggestions if needed
        $externalSuggestions = [];
        $totalFound = count($configSuggestions) + count($localSuggestions);

        if ($totalFound < $limit && strlen($input) >= $this->searchOptions['min_query_length']) {
            $externalSuggestions = $this->getSmartExternalSuggestions($input, $limit - $totalFound);
        }

        // Smart merge with prioritization
        $mergedSuggestions = $this->mergeAllSuggestions($configSuggestions, $localSuggestions, $externalSuggestions, $input, $limit);

        $result = [
            'data' => $mergedSuggestions,
            'source' => $this->determineSource($configSuggestions, $localSuggestions, $externalSuggestions),
            'config_count' => count($configSuggestions),
            'local_count' => count($localSuggestions),
            'external_count' => count($externalSuggestions),
            'total_results' => count($mergedSuggestions),
            'query_length' => strlen($input),
            'region' => $this->detectQueryRegion($input)
        ];

        LocationCache::setCached($cacheKey, $input, 'autocomplete', $result, 'hybrid', $this->searchOptions['cache_ttl']);
        return $result;
    }

    /**
     * Search popular locations from configuration
     */
    private function searchConfigLocations(string $input, int $limit): array
    {
        $input = strtolower(trim($input));
        $suggestions = [];

        foreach ($this->popularLocations as $location) {
            if (count($suggestions) >= $limit) break;

            $name = strtolower($location['name']);
            $country = strtolower($location['country']);
            $aliases = array_map('strtolower', $location['aliases'] ?? []);

            // Check matching strategies
            $matches = false;
            $matchType = '';

            if ($name === $input) {
                $matches = true;
                $matchType = 'exact';
            } elseif (str_starts_with($name, $input)) {
                $matches = true;
                $matchType = 'starts_with';
            } elseif (str_contains($name, $input)) {
                $matches = true;
                $matchType = 'contains';
            } elseif (str_contains($country, $input)) {
                $matches = true;
                $matchType = 'country';
            } else {
                // Check aliases
                foreach ($aliases as $alias) {
                    if ($alias === $input || str_starts_with($alias, $input) || str_contains($alias, $input)) {
                        $matches = true;
                        $matchType = 'alias';
                        break;
                    }
                }
            }

            if ($matches) {
                $priority = $this->calculateConfigPriority($location, $input, $matchType);

                $suggestions[] = [
                    'id' => 'config_' . strtolower(str_replace(' ', '_', $location['name'])),
                    'place_id' => null,
                    'description' => $this->formatDescription($location),
                    'main_text' => $location['name'],
                    'secondary_text' => $this->formatSecondaryText($location),
                    'type' => $location['type'] ?? 'city',
                    'source' => 'config',
                    'priority' => $priority,
                    'match_type' => $matchType,
                    'data' => [
                        'latitude' => $location['latitude'],
                        'longitude' => $location['longitude'],
                        'population' => $location['population'] ?? 0,
                        'country' => $location['country'],
                        'state' => $location['state'] ?? null,
                        'config_priority' => $location['priority'] ?? 0
                    ]
                ];
            }
        }

        // Sort by priority
        usort($suggestions, fn($a, $b) => $b['priority'] - $a['priority']);

        // Remove priority and match_type from output
        return array_slice(array_map(function($suggestion) {
            unset($suggestion['priority'], $suggestion['match_type']);
            return $suggestion;
        }, $suggestions), 0, $limit);
    }

    /**
     * Calculate priority for config locations
     */
    private function calculateConfigPriority(array $location, string $input, string $matchType): int
    {
        $weights = $this->searchOptions['priority_weights'];
        $priority = 0.0; // Start with float

        // Base priority from match type
        $priority += match($matchType) {
            'exact' => $weights['exact_match'],
            'starts_with' => $weights['starts_with'],
            'contains', 'alias' => $weights['contains'],
            'country' => $weights['contains'] * 0.5,
            default => 0
        };

        // Add population-based priority
        if (isset($location['population']) && $location['population'] > 0) {
            $priority += log10($location['population']) * $weights['population_factor'];
        }

        // Add config priority boost
        $priority += ($location['priority'] ?? 0) * 2;

        // Local boost if enabled
        if ($this->searchOptions['boost_local_results']) {
            $priority += $this->searchOptions['local_boost_factor'];
        }

        // Name length bonus (shorter names often more relevant)
        $priority += max(0, $weights['name_length_bonus'] - strlen($location['name']));

        return (int)round($priority); // Convert to int at the end
    }

    /**
     * Format description for location
     */
    private function formatDescription(array $location): string
    {
        $parts = [$location['name']];
        $type = $location['type'] ?? 'city'; // Default to 'city' if not specified

        if (isset($location['state']) && $type !== 'country') {
            $parts[] = $location['state'];
        }

        if ($type !== 'country') {
            $parts[] = $location['country'];
        }

        return implode(', ', $parts);
    }

    /**
     * Format secondary text for location
     */
    private function formatSecondaryText(array $location): string
    {
        $type = $location['type'] ?? 'city'; // Default to 'city' if not specified

        if ($type === 'country') {
            return 'Country';
        }

        $parts = [];
        if (isset($location['state'])) {
            $parts[] = $location['state'];
        }
        $parts[] = $location['country'];

        return implode(', ', $parts);
    }

    /**
     * Get smart external suggestions using region-aware configuration
     */
    private function getSmartExternalSuggestions(string $input, int $limit): array
    {
        if (!$this->googlePlacesService->isAvailable() || $limit <= 0) {
            return [];
        }

        try {
            $region = $this->detectQueryRegion($input);
            $searchConfigs = $this->getSearchConfigsForRegion($region);

            $allSuggestions = [];

            foreach ($searchConfigs as $configName => $config) {
                try {
                    $googleSuggestions = $this->googlePlacesService->getAutocompleteSuggestions($input, $config);

                    foreach ($googleSuggestions as $suggestion) {
                        $key = strtolower($suggestion['main_text']);
                        if (!isset($allSuggestions[$key]) && count($allSuggestions) < $limit) {
                            $allSuggestions[$key] = [
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
                                    'config_used' => $configName
                                ]
                            ];
                        }
                    }

                    if (count($allSuggestions) >= $limit) {
                        break;
                    }
                } catch (\Exception $e) {
                    Log::warning('Google Places search config failed', [
                        'config' => $configName,
                        'input' => $input,
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            }

            return array_slice(array_values($allSuggestions), 0, $limit);

        } catch (\Exception $e) {
            Log::warning('Smart external suggestions failed', ['input' => $input, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Detect query region based on input
     */
    private function detectQueryRegion(string $input): string
    {
        $input = strtolower($input);

        // Check against region countries
        foreach ($this->regions as $regionName => $region) {
            foreach ($region['countries'] as $country) {
                if (str_contains($input, strtolower($country))) {
                    return $regionName;
                }
            }
        }

        // Check against popular locations
        foreach ($this->popularLocations as $location) {
            $name = strtolower($location['name']);
            $country = strtolower($location['country']);

            if (str_contains($input, $name) || str_contains($input, $country)) {
                // Determine region based on country
                foreach ($this->regions as $regionName => $region) {
                    if (in_array($location['country'], $region['countries'])) {
                        return $regionName;
                    }
                }
            }
        }

        // Default to Baltic region (user's region)
        return Config::get('locations.default_region', 'baltic');
    }

    /**
     * Get search configurations for specific region
     */
    private function getSearchConfigsForRegion(string $region): array
    {
        $baseConfigs = $this->googleConfig['search_configs'] ?? [];

        // Add region-specific bias if available
        if (isset($this->regions[$region])) {
            $regionData = $this->regions[$region];
            $locationBias = $regionData['google_bias'] ?? null;

            if ($locationBias) {
                foreach ($baseConfigs as &$config) {
                    $config['location_bias'] = $locationBias;
                }
            }
        }

        return $baseConfigs;
    }

    /**
     * Get local suggestions from existing database
     */
    private function getLocalSuggestions(string $input, int $limit): array
    {
        if ($limit <= 0) return [];

        $suggestions = [];
        $input = trim(strtolower($input));

        // Search cities
        $cities = $this->city->with(['country', 'state'])
            ->whereRaw('LOWER(name) LIKE ?', ['%' . $input . '%'])
            ->where('is_active', true)
            ->orderBy('population', 'desc')
            ->limit($limit)
            ->get();

        foreach ($cities as $city) {
            $suggestions[] = [
                'id' => 'city_' . $city->id,
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

        return array_slice($suggestions, 0, $limit);
    }

    /**
     * Merge all suggestion sources with smart prioritization
     */
    private function mergeAllSuggestions(array $config, array $local, array $external, string $input, int $limit): array
    {
        $merged = [];
        $seen = [];

        // Priority order: config (most relevant) → local → external
        $allSuggestions = array_merge(
            array_map(fn($s) => array_merge($s, ['source_priority' => 3]), $config),
            array_map(fn($s) => array_merge($s, ['source_priority' => 2]), $local),
            array_map(fn($s) => array_merge($s, ['source_priority' => 1]), $external)
        );

        // Sort by source priority first, then by relevance
        usort($allSuggestions, function($a, $b) {
            if ($a['source_priority'] !== $b['source_priority']) {
                return $b['source_priority'] - $a['source_priority'];
            }
            // Within same source, prioritize by name length (shorter usually better)
            return strlen($a['main_text']) - strlen($b['main_text']);
        });

        foreach ($allSuggestions as $suggestion) {
            $key = strtolower(trim($suggestion['main_text']));

            if (!in_array($key, $seen)) {
                unset($suggestion['source_priority']);
                $merged[] = $suggestion;
                $seen[] = $key;

                if (count($merged) >= $limit) {
                    break;
                }
            }
        }

        return $merged;
    }

    /**
     * Determine the source for response metadata
     */
    private function determineSource(array $config, array $local, array $external): string
    {
        $sources = [];
        if (!empty($config)) $sources[] = 'config';
        if (!empty($local)) $sources[] = 'local';
        if (!empty($external)) $sources[] = 'external';

        return match(count($sources)) {
            0 => 'none',
            1 => $sources[0],
            default => 'hybrid'
        };
    }

    /**
     * Get popular locations from configuration + database
     */
    public function getPopularLocations(int $limit = 20): array
    {
        $cacheKey = "popular_locations:" . $limit;
        $cached = LocationCache::getCached($cacheKey);

        if ($cached) {
            return $cached;
        }

        $popular = [];

        // Get from config first (sorted by priority)
        $configLocations = $this->popularLocations;
        usort($configLocations, fn($a, $b) => ($b['priority'] ?? 0) - ($a['priority'] ?? 0));

        foreach (array_slice($configLocations, 0, min($limit, count($configLocations))) as $location) {
            $popular[] = [
                'id' => 'config_' . strtolower(str_replace(' ', '_', $location['name'])),
                'name' => $location['name'],
                'full_name' => $this->formatDescription($location),
                'type' => $location['type'] ?? 'city',
                'source' => 'config',
                'users_count' => 0,
                'population' => $location['population'] ?? 0,
                'country' => $location['country'],
                'state' => $location['state'] ?? null,
                'priority' => $location['priority'] ?? 0,
                'data' => [
                    'latitude' => $location['latitude'],
                    'longitude' => $location['longitude'],
                ]
            ];
        }

        // Fill remaining with database results
        if (count($popular) < $limit) {
            $dbCities = $this->city
                ->with(['country', 'state'])
                ->withCount('users')
                ->where('is_active', true)
                ->orderBy('users_count', 'desc')
                ->orderBy('population', 'desc')
                ->limit($limit - count($popular))
                ->get();

            foreach ($dbCities as $city) {
                $popular[] = [
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
            }
        }

        $result = [
            'data' => array_slice($popular, 0, $limit),
            'source' => 'hybrid',
            'cached_at' => now()->toISOString()
        ];

        LocationCache::setCached($cacheKey, 'popular', 'popular', $result, 'hybrid', $this->searchOptions['cache_ttl'] * 6);
        return $result;
    }

    // Keep all other existing methods unchanged...
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

        // If it's config data
        if (isset($locationData['source']) && $locationData['source'] === 'config') {
            return $locationData; // Already enriched
        }

        // If it's just a string, try to geocode it
        if (isset($locationData['address']) || isset($locationData['location_string'])) {
            return $this->enrichFromAddressString($locationData);
        }

        return $locationData;
    }

    public function geocodeAddress(string $address): ?array
    {
        $cacheKey = "geocode:" . md5($address);
        $cached = LocationCache::getCached($cacheKey);

        if ($cached) {
            return $cached;
        }

        // Check config data first
        $configResult = $this->geocodeFromConfigData($address);
        if ($configResult) {
            LocationCache::setCached($cacheKey, $address, 'geocode', $configResult, 'config');
            return $configResult;
        }

        // Try Google geocoding with region-specific configuration
        if ($this->googlePlacesService->isAvailable()) {
            try {
                $region = $this->detectQueryRegion($address);
                $options = [
                    'region' => $this->googleConfig['region_bias'] ?? 'eu',
                    'language' => $this->googleConfig['language'] ?? 'en'
                ];

                $result = $this->googlePlacesService->geocodeAddress($address, $options);

                if ($result) {
                    $enriched = array_merge($result, [
                        'source' => 'google_geocoding',
                        'region_detected' => $region,
                        'cached_at' => now()->toISOString()
                    ]);

                    LocationCache::setCached($cacheKey, $address, 'geocode', $enriched, 'external', $this->searchOptions['cache_ttl'] * 24);
                    return $enriched;
                }
            } catch (\Exception $e) {
                Log::warning('Google geocoding failed', ['address' => $address, 'error' => $e->getMessage()]);
            }
        }

        // Fallback to existing local database
        $localResult = $this->geocodeFromLocalData($address);
        if ($localResult) {
            LocationCache::setCached($cacheKey, $address, 'geocode', $localResult, 'local');
            return $localResult;
        }

        return null;
    }

    private function geocodeFromConfigData(string $address): ?array
    {
        $address = strtolower(trim($address));

        foreach ($this->popularLocations as $location) {
            $name = strtolower($location['name']);
            $country = strtolower($location['country']);
            $aliases = array_map('strtolower', $location['aliases'] ?? []);

            if (str_contains($address, $name) ||
                str_contains($name, $address) ||
                str_contains($address, $country)) {

                return [
                    'formatted_address' => $this->formatDescription($location),
                    'latitude' => $location['latitude'],
                    'longitude' => $location['longitude'],
                    'source' => 'config_geocoding',
                    'location_data' => $location
                ];
            }

            // Check aliases
            foreach ($aliases as $alias) {
                if (str_contains($address, $alias) || str_contains($alias, $address)) {
                    return [
                        'formatted_address' => $this->formatDescription($location),
                        'latitude' => $location['latitude'],
                        'longitude' => $location['longitude'],
                        'source' => 'config_geocoding',
                        'location_data' => $location,
                        'matched_alias' => $alias
                    ];
                }
            }
        }

        return null;
    }

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

    // Keep all private helper methods...
    private function geocodeFromLocalData(string $address): ?array
    {
        $address = trim(strtolower($address));

        $city = $this->city->with(['country', 'state'])
            ->whereRaw('LOWER(name) LIKE ?', ['%' . $address . '%'])
            ->where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('population', 'desc')
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

    private function prepareLocationForDatabase(array $locationData): array
    {
        return [
            'location_display' => $locationData['formatted_address'] ?? $locationData['description'] ?? null,
            'location_data' => $locationData,
            'country_id' => $locationData['data']['country_id'] ?? null,
            'state_id' => $locationData['data']['state_id'] ?? null,
            'city_id' => $locationData['data']['city_id'] ?? null,
            'latitude' => $locationData['latitude'] ?? $locationData['data']['latitude'] ?? null,
            'longitude' => $locationData['longitude'] ?? $locationData['data']['longitude'] ?? null,
            'google_place_id' => $locationData['place_id'] ?? $locationData['data']['place_id'] ?? null,
        ];
    }

    private function determineLocationTypeFromGoogleTypes(array $types): string
    {
        if (in_array('country', $types)) return 'country';
        if (in_array('administrative_area_level_1', $types)) return 'state';
        if (in_array('locality', $types) || in_array('administrative_area_level_2', $types)) return 'city';
        if (in_array('sublocality', $types)) return 'neighborhood';
        return 'locality';
    }
}