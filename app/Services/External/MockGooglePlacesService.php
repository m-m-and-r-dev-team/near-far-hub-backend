<?php

declare(strict_types=1);

namespace App\Services\External;

/**
 * Mock Google Places Service for testing/development
 * Use this when you don't want to set up real API keys
 */
class MockGooglePlacesService extends GooglePlacesService
{
    public function __construct()
    {
        // Skip the API key check for mock
    }

    public function getAutocompleteSuggestions(string $input, array $options = []): array
    {
        // Return mock data based on input
        $mockSuggestions = [
            [
                'place_id' => 'mock_place_1',
                'description' => 'New York, NY, USA',
                'main_text' => 'New York',
                'secondary_text' => 'NY, USA',
                'types' => ['locality', 'political'],
                'terms' => [
                    ['offset' => 0, 'value' => 'New York'],
                    ['offset' => 10, 'value' => 'NY'],
                    ['offset' => 14, 'value' => 'USA']
                ],
                'distance_meters' => null
            ],
            [
                'place_id' => 'mock_place_2',
                'description' => 'Los Angeles, CA, USA',
                'main_text' => 'Los Angeles',
                'secondary_text' => 'CA, USA',
                'types' => ['locality', 'political'],
                'terms' => [
                    ['offset' => 0, 'value' => 'Los Angeles'],
                    ['offset' => 13, 'value' => 'CA'],
                    ['offset' => 17, 'value' => 'USA']
                ],
                'distance_meters' => null
            ]
        ];

        // Filter based on input
        return array_filter($mockSuggestions, function($suggestion) use ($input) {
            return stripos($suggestion['description'], $input) !== false;
        });
    }

    public function getPlaceDetails(string $placeId, array $fields = null): ?array
    {
        $mockDetails = [
            'mock_place_1' => [
                'place_id' => 'mock_place_1',
                'name' => 'New York',
                'formatted_address' => 'New York, NY, USA',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'types' => ['locality', 'political'],
                'address_components' => [
                    'locality' => ['long_name' => 'New York', 'short_name' => 'New York'],
                    'administrative_area_level_1' => ['long_name' => 'New York', 'short_name' => 'NY'],
                    'country' => ['long_name' => 'United States', 'short_name' => 'US']
                ]
            ],
            'mock_place_2' => [
                'place_id' => 'mock_place_2',
                'name' => 'Los Angeles',
                'formatted_address' => 'Los Angeles, CA, USA',
                'latitude' => 34.0522,
                'longitude' => -118.2437,
                'types' => ['locality', 'political'],
                'address_components' => [
                    'locality' => ['long_name' => 'Los Angeles', 'short_name' => 'LA'],
                    'administrative_area_level_1' => ['long_name' => 'California', 'short_name' => 'CA'],
                    'country' => ['long_name' => 'United States', 'short_name' => 'US']
                ]
            ]
        ];

        return $mockDetails[$placeId] ?? null;
    }

    public function geocodeAddress(string $address): ?array
    {
        // Simple mock geocoding
        if (stripos($address, 'new york') !== false) {
            return [
                'formatted_address' => 'New York, NY, USA',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'place_id' => 'mock_place_1'
            ];
        }

        if (stripos($address, 'los angeles') !== false) {
            return [
                'formatted_address' => 'Los Angeles, CA, USA',
                'latitude' => 34.0522,
                'longitude' => -118.2437,
                'place_id' => 'mock_place_2'
            ];
        }

        return null;
    }

    public function isAvailable(): bool
    {
        return true; // Mock is always "available"
    }
}