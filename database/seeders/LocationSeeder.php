<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Locations\Country;
use App\Models\Locations\State;
use App\Models\Locations\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $countries = [
                [
                    'name' => 'United States',
                    'code' => 'US',
                    'code_alpha3' => 'USA',
                    'phone_code' => '+1',
                    'currency_code' => 'USD',
                    'states' => [
                        [
                            'name' => 'California',
                            'code' => 'CA',
                            'type' => 'state',
                            'cities' => [
                                ['name' => 'Los Angeles', 'lat' => 34.0522, 'lng' => -118.2437, 'pop' => 3971883],
                                ['name' => 'San Francisco', 'lat' => 37.7749, 'lng' => -122.4194, 'pop' => 881549],
                                ['name' => 'San Diego', 'lat' => 32.7157, 'lng' => -117.1611, 'pop' => 1423851],
                                ['name' => 'Sacramento', 'lat' => 38.5816, 'lng' => -121.4944, 'pop' => 508529],
                            ]
                        ],
                        [
                            'name' => 'New York',
                            'code' => 'NY',
                            'type' => 'state',
                            'cities' => [
                                ['name' => 'New York City', 'lat' => 40.7128, 'lng' => -74.0060, 'pop' => 8336817],
                                ['name' => 'Buffalo', 'lat' => 42.8864, 'lng' => -78.8784, 'pop' => 261310],
                                ['name' => 'Rochester', 'lat' => 43.1566, 'lng' => -77.6088, 'pop' => 206284],
                            ]
                        ],
                        [
                            'name' => 'Texas',
                            'code' => 'TX',
                            'type' => 'state',
                            'cities' => [
                                ['name' => 'Houston', 'lat' => 29.7604, 'lng' => -95.3698, 'pop' => 2320268],
                                ['name' => 'Dallas', 'lat' => 32.7767, 'lng' => -96.7970, 'pop' => 1343573],
                                ['name' => 'Austin', 'lat' => 30.2672, 'lng' => -97.7431, 'pop' => 978908],
                                ['name' => 'San Antonio', 'lat' => 29.4241, 'lng' => -98.4936, 'pop' => 1547253],
                            ]
                        ]
                    ]
                ],
                [
                    'name' => 'United Kingdom',
                    'code' => 'GB',
                    'code_alpha3' => 'GBR',
                    'phone_code' => '+44',
                    'currency_code' => 'GBP',
                    'states' => [
                        [
                            'name' => 'England',
                            'code' => 'ENG',
                            'type' => 'country',
                            'cities' => [
                                ['name' => 'London', 'lat' => 51.5074, 'lng' => -0.1278, 'pop' => 8982000],
                                ['name' => 'Manchester', 'lat' => 53.4808, 'lng' => -2.2426, 'pop' => 547000],
                                ['name' => 'Birmingham', 'lat' => 52.4862, 'lng' => -1.8904, 'pop' => 1141816],
                                ['name' => 'Liverpool', 'lat' => 53.4084, 'lng' => -2.9916, 'pop' => 498042],
                            ]
                        ]
                    ]
                ],
                [
                    'name' => 'Canada',
                    'code' => 'CA',
                    'code_alpha3' => 'CAN',
                    'phone_code' => '+1',
                    'currency_code' => 'CAD',
                    'states' => [
                        [
                            'name' => 'Ontario',
                            'code' => 'ON',
                            'type' => 'province',
                            'cities' => [
                                ['name' => 'Toronto', 'lat' => 43.6532, 'lng' => -79.3832, 'pop' => 2930000],
                                ['name' => 'Ottawa', 'lat' => 45.4215, 'lng' => -75.6972, 'pop' => 994837],
                            ]
                        ]
                    ]
                ]
            ];

            foreach ($countries as $countryData) {
                $country = Country::firstOrCreate(
                    ['code' => $countryData['code']],
                    [
                        'name' => $countryData['name'],
                        'code_alpha3' => $countryData['code_alpha3'],
                        'phone_code' => $countryData['phone_code'],
                        'currency_code' => $countryData['currency_code'],
                        'is_active' => true,
                    ]
                );

                foreach ($countryData['states'] as $stateData) {
                    $state = State::firstOrCreate(
                        [
                            'country_id' => $country->id,
                            'code' => $stateData['code']
                        ],
                        [
                            'name' => $stateData['name'],
                            'type' => $stateData['type'],
                            'is_active' => true,
                        ]
                    );

                    foreach ($stateData['cities'] as $cityData) {
                        City::firstOrCreate(
                            [
                                'country_id' => $country->id,
                                'state_id' => $state->id,
                                'name' => $cityData['name']
                            ],
                            [
                                'latitude' => $cityData['lat'],
                                'longitude' => $cityData['lng'],
                                'population' => $cityData['pop'],
                                'is_active' => true,
                            ]
                        );
                    }
                }
            }
        });

        $this->command->info('✅ Location data seeded successfully!');
    }
}