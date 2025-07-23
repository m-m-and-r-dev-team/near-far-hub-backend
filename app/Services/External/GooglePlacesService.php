<?php

declare(strict_types=1);

namespace App\Services\External;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

class GooglePlacesService
{
    private string $apiKey;
    private string $baseUrl = 'https://maps.googleapis.com/maps/api';

    public function __construct()
    {
        $this->apiKey = config('services.google.places_api_key');

        if (empty($this->apiKey)) {
            throw new \Exception('Google Places API key is not configured');
        }
    }

    /**
     * Get autocomplete suggestions for location input
     */
    public function getAutocompleteSuggestions(string $input, array $options = []): array
    {
        if (strlen($input) < 2) {
            return [];
        }

        try {
            $params = [
                'input' => $input,
                'key' => $this->apiKey,
                'types' => $options['types'] ?? '(cities)',
                'language' => $options['language'] ?? 'en',
            ];

            // Add session token for billing optimization
            if (isset($options['session_token'])) {
                $params['sessiontoken'] = $options['session_token'];
            }

            // Add location bias if provided
            if (isset($options['location_bias'])) {
                $params['locationbias'] = $options['location_bias'];
            }

            $response = Http::timeout(5)->get("{$this->baseUrl}/place/autocomplete/json", $params);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'OK') {
                    return $this->formatAutocompleteResponse($data['predictions'] ?? []);
                }

                Log::warning('Google Places API returned non-OK status', [
                    'status' => $data['status'],
                    'input' => $input
                ]);
                return [];
            }

            Log::error('Google Places API request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return [];

        } catch (RequestException $e) {
            Log::error('Google Places API request exception', [
                'error' => $e->getMessage(),
                'input' => $input
            ]);
            return [];
        }
    }

    /**
     * Get detailed information about a place
     */
    public function getPlaceDetails(string $placeId, array $fields = null): ?array
    {
        try {
            $defaultFields = [
                'place_id', 'name', 'formatted_address', 'geometry',
                'address_components', 'types', 'plus_code'
            ];

            $params = [
                'place_id' => $placeId,
                'fields' => implode(',', $fields ?? $defaultFields),
                'key' => $this->apiKey,
            ];

            $response = Http::timeout(5)->get("{$this->baseUrl}/place/details/json", $params);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'OK' && isset($data['result'])) {
                    return $this->formatPlaceDetailsResponse($data['result']);
                }

                Log::warning('Google Places Details API returned non-OK status', [
                    'status' => $data['status'],
                    'place_id' => $placeId
                ]);
                return null;
            }

            Log::error('Google Places Details API request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;

        } catch (RequestException $e) {
            Log::error('Google Places Details API exception', [
                'error' => $e->getMessage(),
                'place_id' => $placeId
            ]);
            return null;
        }
    }

    /**
     * Geocode an address to get coordinates
     */
    public function geocodeAddress(string $address): ?array
    {
        try {
            $params = [
                'address' => $address,
                'key' => $this->apiKey,
            ];

            $response = Http::timeout(5)->get("{$this->baseUrl}/geocode/json", $params);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'OK' && !empty($data['results'])) {
                    return $this->formatGeocodeResponse($data['results'][0]);
                }

                Log::info('Google Geocoding API found no results', ['address' => $address]);
                return null;
            }

            Log::error('Google Geocoding API request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;

        } catch (RequestException $e) {
            Log::error('Google Geocoding API exception', [
                'error' => $e->getMessage(),
                'address' => $address
            ]);
            return null;
        }
    }

    /**
     * Search for places using text search
     */
    public function searchPlaces(string $query, array $options = []): array
    {
        try {
            $params = [
                'query' => $query,
                'key' => $this->apiKey,
                'type' => $options['type'] ?? 'locality',
            ];

            // Add location bias if provided
            if (isset($options['location'])) {
                $params['location'] = $options['location'];
                $params['radius'] = $options['radius'] ?? 50000; // 50km default
            }

            $response = Http::timeout(10)->get("{$this->baseUrl}/place/textsearch/json", $params);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'OK') {
                    return $this->formatPlacesSearchResponse($data['results'] ?? []);
                }

                Log::warning('Google Places Text Search API returned non-OK status', [
                    'status' => $data['status'],
                    'query' => $query
                ]);
                return [];
            }

            Log::error('Google Places Text Search API request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return [];

        } catch (RequestException $e) {
            Log::error('Google Places Text Search API exception', [
                'error' => $e->getMessage(),
                'query' => $query
            ]);
            return [];
        }
    }

    /**
     * Format autocomplete response
     */
    private function formatAutocompleteResponse(array $predictions): array
    {
        return array_map(function ($prediction) {
            return [
                'place_id' => $prediction['place_id'],
                'description' => $prediction['description'],
                'main_text' => $prediction['structured_formatting']['main_text'] ?? '',
                'secondary_text' => $prediction['structured_formatting']['secondary_text'] ?? '',
                'types' => $prediction['types'] ?? [],
                'terms' => $prediction['terms'] ?? [],
                'distance_meters' => $prediction['distance_meters'] ?? null,
            ];
        }, $predictions);
    }

    /**
     * Format place details response
     */
    private function formatPlaceDetailsResponse(array $place): array
    {
        return [
            'place_id' => $place['place_id'],
            'name' => $place['name'] ?? '',
            'formatted_address' => $place['formatted_address'] ?? '',
            'latitude' => $place['geometry']['location']['lat'] ?? null,
            'longitude' => $place['geometry']['location']['lng'] ?? null,
            'types' => $place['types'] ?? [],
            'address_components' => $this->parseAddressComponents($place['address_components'] ?? []),
            'plus_code' => $place['plus_code'] ?? null,
            'viewport' => $place['geometry']['viewport'] ?? null,
        ];
    }

    /**
     * Format geocode response
     */
    private function formatGeocodeResponse(array $result): array
    {
        return [
            'formatted_address' => $result['formatted_address'],
            'latitude' => $result['geometry']['location']['lat'],
            'longitude' => $result['geometry']['location']['lng'],
            'place_id' => $result['place_id'] ?? null,
            'types' => $result['types'] ?? [],
            'address_components' => $this->parseAddressComponents($result['address_components'] ?? []),
            'location_type' => $result['geometry']['location_type'] ?? null,
            'viewport' => $result['geometry']['viewport'] ?? null,
        ];
    }

    /**
     * Format places search response
     */
    private function formatPlacesSearchResponse(array $places): array
    {
        return array_map(function ($place) {
            return [
                'place_id' => $place['place_id'],
                'name' => $place['name'],
                'formatted_address' => $place['formatted_address'],
                'latitude' => $place['geometry']['location']['lat'] ?? null,
                'longitude' => $place['geometry']['location']['lng'] ?? null,
                'types' => $place['types'] ?? [],
                'rating' => $place['rating'] ?? null,
                'user_ratings_total' => $place['user_ratings_total'] ?? null,
            ];
        }, $places);
    }

    /**
     * Parse address components into structured data
     */
    private function parseAddressComponents(array $components): array
    {
        $parsed = [
            'street_number' => null,
            'route' => null,
            'neighborhood' => null,
            'locality' => null,
            'sublocality' => null,
            'administrative_area_level_1' => null,
            'administrative_area_level_2' => null,
            'country' => null,
            'postal_code' => null,
        ];

        foreach ($components as $component) {
            foreach ($component['types'] as $type) {
                if (array_key_exists($type, $parsed)) {
                    $parsed[$type] = [
                        'long_name' => $component['long_name'],
                        'short_name' => $component['short_name'],
                    ];
                    break; // Take the first matching type
                }
            }
        }

        return $parsed;
    }

    /**
     * Generate session token for billing optimization
     */
    public function generateSessionToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Check if API is configured and available
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }
}