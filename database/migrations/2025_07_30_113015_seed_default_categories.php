<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use App\Models\Categories\Category;

return new class extends Migration
{
    public function up(): void
    {
        $this->seedCategories();
    }

    public function down(): void
    {
        Category::truncate();
    }

    private function seedCategories(): void
    {
        $categories = [
            [
                'name' => 'Electronics',
                'description' => 'Electronic devices, gadgets, and accessories',
                'icon' => 'laptop',
                'color' => '#3B82F6',
                'is_featured' => true,
                'sort_order' => 1,
                'attributes' => [
                    'brand' => [
                        'type' => 'select',
                        'label' => 'Brand',
                        'required' => true,
                        'options' => ['Apple', 'Samsung', 'Sony', 'LG', 'Dell', 'HP', 'Other']
                    ],
                    'warranty' => [
                        'type' => 'select',
                        'label' => 'Warranty Status',
                        'required' => false,
                        'options' => ['Under warranty', 'Expired warranty', 'No warranty']
                    ],
                    'storage' => [
                        'type' => 'text',
                        'label' => 'Storage Capacity',
                        'required' => false,
                        'placeholder' => 'e.g., 256GB, 1TB'
                    ]
                ],
                'children' => [
                    [
                        'name' => 'Smartphones',
                        'description' => 'Mobile phones and accessories',
                        'icon' => 'smartphone',
                        'attributes' => [
                            'network' => [
                                'type' => 'select',
                                'label' => 'Network',
                                'required' => false,
                                'options' => ['Unlocked', 'Verizon', 'AT&T', 'T-Mobile', 'Sprint', 'Other']
                            ],
                            'screen_size' => [
                                'type' => 'text',
                                'label' => 'Screen Size',
                                'required' => false,
                                'placeholder' => 'e.g., 6.1 inches'
                            ]
                        ]
                    ],
                    [
                        'name' => 'Laptops & Computers',
                        'description' => 'Laptops, desktops, and computer accessories',
                        'icon' => 'laptop',
                        'attributes' => [
                            'processor' => [
                                'type' => 'text',
                                'label' => 'Processor',
                                'required' => false,
                                'placeholder' => 'e.g., Intel i7, AMD Ryzen 5'
                            ],
                            'ram' => [
                                'type' => 'select',
                                'label' => 'RAM',
                                'required' => false,
                                'options' => ['4GB', '8GB', '16GB', '32GB', '64GB', 'Other']
                            ],
                            'operating_system' => [
                                'type' => 'select',
                                'label' => 'Operating System',
                                'required' => false,
                                'options' => ['Windows 11', 'Windows 10', 'macOS', 'Linux', 'Chrome OS', 'Other']
                            ]
                        ]
                    ],
                    [
                        'name' => 'Gaming',
                        'description' => 'Gaming consoles, games, and accessories',
                        'icon' => 'gamepad-2',
                        'attributes' => [
                            'platform' => [
                                'type' => 'select',
                                'label' => 'Platform',
                                'required' => true,
                                'options' => ['PlayStation 5', 'PlayStation 4', 'Xbox Series X/S', 'Xbox One', 'Nintendo Switch', 'PC', 'Other']
                            ],
                            'genre' => [
                                'type' => 'select',
                                'label' => 'Genre',
                                'required' => false,
                                'options' => ['Action', 'Adventure', 'RPG', 'Sports', 'Racing', 'Strategy', 'Puzzle', 'Other']
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Automotive',
                'description' => 'Cars, motorcycles, and automotive accessories',
                'icon' => 'car',
                'color' => '#DC2626',
                'is_featured' => true,
                'sort_order' => 2,
                'attributes' => [
                    'make' => [
                        'type' => 'text',
                        'label' => 'Make',
                        'required' => true,
                        'placeholder' => 'e.g., Toyota, Ford, BMW'
                    ],
                    'model' => [
                        'type' => 'text',
                        'label' => 'Model',
                        'required' => true,
                        'placeholder' => 'e.g., Camry, F-150, X5'
                    ],
                    'year' => [
                        'type' => 'number',
                        'label' => 'Year',
                        'required' => true,
                        'min' => 1900,
                        'max' => date('Y') + 1
                    ],
                    'mileage' => [
                        'type' => 'number',
                        'label' => 'Mileage',
                        'required' => false,
                        'min' => 0,
                        'placeholder' => 'Miles or Kilometers'
                    ],
                    'fuel_type' => [
                        'type' => 'select',
                        'label' => 'Fuel Type',
                        'required' => false,
                        'options' => ['Gasoline', 'Diesel', 'Electric', 'Hybrid', 'Plug-in Hybrid', 'Other']
                    ],
                    'transmission' => [
                        'type' => 'select',
                        'label' => 'Transmission',
                        'required' => false,
                        'options' => ['Automatic', 'Manual', 'CVT', 'Other']
                    ]
                ],
                'children' => [
                    [
                        'name' => 'Cars',
                        'description' => 'Passenger cars, sedans, SUVs, and trucks',
                        'icon' => 'car'
                    ],
                    [
                        'name' => 'Motorcycles',
                        'description' => 'Motorcycles, scooters, and bikes',
                        'icon' => 'bike'
                    ],
                    [
                        'name' => 'Auto Parts',
                        'description' => 'Car parts, accessories, and tools',
                        'icon' => 'wrench',
                        'attributes' => [
                            'part_type' => [
                                'type' => 'select',
                                'label' => 'Part Type',
                                'required' => true,
                                'options' => ['Engine', 'Transmission', 'Brakes', 'Suspension', 'Electrical', 'Body', 'Interior', 'Tires', 'Other']
                            ],
                            'compatible_vehicles' => [
                                'type' => 'textarea',
                                'label' => 'Compatible Vehicles',
                                'required' => false,
                                'placeholder' => 'List compatible makes/models/years'
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Fashion & Apparel',
                'description' => 'Clothing, shoes, and fashion accessories',
                'icon' => 'shirt',
                'color' => '#EC4899',
                'is_featured' => true,
                'sort_order' => 3,
                'attributes' => [
                    'size' => [
                        'type' => 'select',
                        'label' => 'Size',
                        'required' => true,
                        'options' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'Other']
                    ],
                    'color' => [
                        'type' => 'text',
                        'label' => 'Color',
                        'required' => false,
                        'placeholder' => 'Primary color'
                    ],
                    'material' => [
                        'type' => 'text',
                        'label' => 'Material',
                        'required' => false,
                        'placeholder' => 'e.g., Cotton, Polyester, Leather'
                    ]
                ],
                'children' => [
                    [
                        'name' => 'Men\'s Clothing',
                        'description' => 'Men\'s shirts, pants, jackets, and more',
                        'icon' => 'shirt'
                    ],
                    [
                        'name' => 'Women\'s Clothing',
                        'description' => 'Women\'s dresses, tops, bottoms, and more',
                        'icon' => 'shirt'
                    ],
                    [
                        'name' => 'Shoes',
                        'description' => 'Footwear for men, women, and children',
                        'icon' => 'footprints',
                        'attributes' => [
                            'shoe_size' => [
                                'type' => 'text',
                                'label' => 'Shoe Size',
                                'required' => true,
                                'placeholder' => 'e.g., 9, 9.5, 42 EU'
                            ],
                            'shoe_type' => [
                                'type' => 'select',
                                'label' => 'Type',
                                'required' => false,
                                'options' => ['Sneakers', 'Dress Shoes', 'Boots', 'Sandals', 'Heels', 'Flats', 'Athletic', 'Other']
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Home & Garden',
                'description' => 'Furniture, appliances, and home improvement items',
                'icon' => 'home',
                'color' => '#059669',
                'is_featured' => true,
                'sort_order' => 4,
                'attributes' => [
                    'condition_details' => [
                        'type' => 'textarea',
                        'label' => 'Condition Details',
                        'required' => false,
                        'placeholder' => 'Describe any wear, damage, or special features'
                    ],
                    'dimensions' => [
                        'type' => 'text',
                        'label' => 'Dimensions',
                        'required' => false,
                        'placeholder' => 'L x W x H'
                    ]
                ],
                'children' => [
                    [
                        'name' => 'Furniture',
                        'description' => 'Tables, chairs, sofas, and bedroom furniture',
                        'icon' => 'sofa'
                    ],
                    [
                        'name' => 'Appliances',
                        'description' => 'Kitchen and home appliances',
                        'icon' => 'refrigerator'
                    ],
                    [
                        'name' => 'Garden & Outdoor',
                        'description' => 'Gardening tools, outdoor furniture, and lawn equipment',
                        'icon' => 'flower'
                    ]
                ]
            ],
            [
                'name' => 'Sports & Recreation',
                'description' => 'Sports equipment, fitness gear, and recreational items',
                'icon' => 'dumbbell',
                'color' => '#F59E0B',
                'sort_order' => 5,
                'attributes' => [
                    'sport_type' => [
                        'type' => 'select',
                        'label' => 'Sport/Activity',
                        'required' => false,
                        'options' => ['Basketball', 'Football', 'Soccer', 'Tennis', 'Golf', 'Baseball', 'Hockey', 'Cycling', 'Running', 'Swimming', 'Fitness', 'Other']
                    ],
                    'skill_level' => [
                        'type' => 'select',
                        'label' => 'Skill Level',
                        'required' => false,
                        'options' => ['Beginner', 'Intermediate', 'Advanced', 'Professional', 'All Levels']
                    ]
                ]
            ],
            [
                'name' => 'Books & Media',
                'description' => 'Books, movies, music, and educational materials',
                'icon' => 'book',
                'color' => '#8B5CF6',
                'sort_order' => 6,
                'attributes' => [
                    'media_type' => [
                        'type' => 'select',
                        'label' => 'Type',
                        'required' => true,
                        'options' => ['Book', 'DVD', 'Blu-ray', 'CD', 'Vinyl', 'Magazine', 'Digital', 'Other']
                    ],
                    'genre' => [
                        'type' => 'select',
                        'label' => 'Genre',
                        'required' => false,
                        'options' => ['Fiction', 'Non-fiction', 'Science Fiction', 'Mystery', 'Romance', 'Biography', 'Self-help', 'Educational', 'Children', 'Other']
                    ],
                    'author_artist' => [
                        'type' => 'text',
                        'label' => 'Author/Artist',
                        'required' => false,
                        'placeholder' => 'Name of author, artist, or creator'
                    ],
                    'language' => [
                        'type' => 'select',
                        'label' => 'Language',
                        'required' => false,
                        'options' => ['English', 'Spanish', 'French', 'German', 'Italian', 'Other']
                    ]
                ]
            ]
        ];

        foreach ($categories as $categoryData) {
            $this->createCategoryWithChildren($categoryData);
        }
    }

    private function createCategoryWithChildren(array $categoryData, ?int $parentId = null): void
    {
        $children = $categoryData['children'] ?? [];
        unset($categoryData['children']);

        if ($parentId) {
            $categoryData['parent_id'] = $parentId;
        }

        $category = Category::create($categoryData);

        foreach ($children as $childData) {
            $this->createCategoryWithChildren($childData, $category->id);
        }
    }
};
