<?php

return [
    'default_region' => 'baltic',
    'user_location' => [
        'latitude' => 56.9496,
        'longitude' => 24.1052,
        'city' => 'Riga',
        'country' => 'Latvia'
    ],

    'popular_locations' => [
        // Baltic States (High Priority)
        [
            'name' => 'Riga',
            'country' => 'Latvia',
            'state' => 'Riga Region',
            'latitude' => 56.9496,
            'longitude' => 24.1052,
            'population' => 632614,
            'priority' => 100,
            'aliases' => ['rīga']
        ],
        [
            'name' => 'Latvia',
            'country' => 'Latvia',
            'latitude' => 56.8796,
            'longitude' => 24.6032,
            'population' => 1900000,
            'priority' => 95,
            'type' => 'country'
        ],
        [
            'name' => 'Vilnius',
            'country' => 'Lithuania',
            'state' => 'Vilnius County',
            'latitude' => 54.6872,
            'longitude' => 25.2797,
            'population' => 574147,
            'priority' => 90
        ],
        [
            'name' => 'Tallinn',
            'country' => 'Estonia',
            'state' => 'Harju County',
            'latitude' => 59.4370,
            'longitude' => 24.7536,
            'population' => 437619,
            'priority' => 85
        ],
        [
            'name' => 'Jūrmala',
            'country' => 'Latvia',
            'state' => 'Riga Region',
            'latitude' => 56.9681,
            'longitude' => 23.7794,
            'population' => 49325,
            'priority' => 80,
            'aliases' => ['jurmala']
        ],
        [
            'name' => 'Liepāja',
            'country' => 'Latvia',
            'state' => 'Kurzeme',
            'latitude' => 56.5046,
            'longitude' => 21.0086,
            'population' => 71441,
            'priority' => 75,
            'aliases' => ['liepaja']
        ],
        [
            'name' => 'Daugavpils',
            'country' => 'Latvia',
            'state' => 'Latgale',
            'latitude' => 55.8747,
            'longitude' => 26.5428,
            'population' => 82604,
            'priority' => 70
        ],

        // Nordic Countries (Medium Priority)
        [
            'name' => 'Stockholm',
            'country' => 'Sweden',
            'state' => 'Stockholm County',
            'latitude' => 59.3293,
            'longitude' => 18.0686,
            'population' => 975551,
            'priority' => 65
        ],
        [
            'name' => 'Helsinki',
            'country' => 'Finland',
            'state' => 'Uusimaa',
            'latitude' => 60.1699,
            'longitude' => 24.9384,
            'population' => 658457,
            'priority' => 60
        ],
        [
            'name' => 'Oslo',
            'country' => 'Norway',
            'state' => 'Oslo',
            'latitude' => 59.9139,
            'longitude' => 10.7522,
            'population' => 697549,
            'priority' => 55
        ],

        // Central/Eastern Europe (Lower Priority)
        [
            'name' => 'Warsaw',
            'country' => 'Poland',
            'state' => 'Masovian Voivodeship',
            'latitude' => 52.2297,
            'longitude' => 21.0122,
            'population' => 1790658,
            'priority' => 50
        ],
        [
            'name' => 'Berlin',
            'country' => 'Germany',
            'state' => 'Berlin',
            'latitude' => 52.5200,
            'longitude' => 13.4050,
            'population' => 3669491,
            'priority' => 45
        ],
        [
            'name' => 'Prague',
            'country' => 'Czech Republic',
            'state' => 'Prague',
            'latitude' => 50.0755,
            'longitude' => 14.4378,
            'population' => 1324277,
            'priority' => 40
        ]
    ],

    'regions' => [
        'baltic' => [
            'name' => 'Baltic Region',
            'countries' => ['Latvia', 'Lithuania', 'Estonia'],
            'center' => ['latitude' => 56.5, 'longitude' => 24.5],
            'radius' => 300000, // 300km
            'google_bias' => 'circle:300000@56.5,24.5'
        ],
        'nordic' => [
            'name' => 'Nordic Region',
            'countries' => ['Sweden', 'Finland', 'Norway', 'Denmark'],
            'center' => ['latitude' => 62.0, 'longitude' => 15.0],
            'radius' => 500000,
            'google_bias' => 'circle:500000@62.0,15.0'
        ],
        'central_europe' => [
            'name' => 'Central Europe',
            'countries' => ['Germany', 'Poland', 'Czech Republic', 'Slovakia'],
            'center' => ['latitude' => 52.0, 'longitude' => 19.0],
            'radius' => 400000,
            'google_bias' => 'circle:400000@52.0,19.0'
        ]
    ],

    'google_places' => [
        'default_types' => 'geocode',
        'language' => 'en',
        'region_bias' => 'eu',
        'session_tokens' => env('GOOGLE_PLACES_USE_SESSIONS', true),

        // Search configurations for different query types
        'search_configs' => [
            'cities' => [
                'types' => '(cities)',
                'location_bias' => 'circle:200000@56.9496,24.1052', // 200km around Riga
            ],
            'regions' => [
                'types' => '(regions)',
                'location_bias' => 'rectangle:50.0,12.0|71.0,40.0', // Northern/Eastern Europe
            ],
            'all' => [
                'types' => 'geocode',
                'location_bias' => 'circle:500000@56.9496,24.1052', // 500km around Riga
            ]
        ]
    ],

    'search_options' => [
        'min_query_length' => 2,
        'max_results' => 10,
        'cache_ttl' => 3600, // 1 hour
        'enable_fuzzy_search' => true,
        'boost_local_results' => true,
        'local_boost_factor' => 200,

        // Priority weights
        'priority_weights' => [
            'exact_match' => 1000,
            'starts_with' => 500,
            'contains' => 200,
            'population_factor' => 30,
            'name_length_bonus' => 50,
        ]
    ]
];