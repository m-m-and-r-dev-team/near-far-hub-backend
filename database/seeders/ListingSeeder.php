<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Listings\Listing;
use App\Models\SellerProfiles\SellerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class ListingSeeder extends Seeder
{
    public function run(): void
    {
        // Create 10 users
        $users = User::factory(10)->create();

        // Create a seller profile for each user
        $sellerProfiles = [];
        foreach ($users as $user) {
            $sellerProfiles[] = SellerProfile::factory()->create([
                'user_id' => $user->id,
                'is_verified' => true,
                'verified_at' => now()->subDays(rand(1, 365)),
            ]);
        }

        // Create listings for each seller profile
        foreach ($sellerProfiles as $sellerProfile) {
            // Create 2-8 active listings
            Listing::factory(rand(2, 8))->create([
                'seller_profile_id' => $sellerProfile->id,
                'status' => 'active',
                'published_at' => now()->subDays(rand(1, 30)),
            ]);

            // Create 0-3 draft listings
            Listing::factory(rand(0, 3))->create([
                'seller_profile_id' => $sellerProfile->id,
                'status' => 'draft',
                'published_at' => null,
            ]);

            // Create 0-2 sold listings
            Listing::factory(rand(0, 2))->create([
                'seller_profile_id' => $sellerProfile->id,
                'status' => 'sold',
                'published_at' => now()->subDays(rand(1, 30)),
            ]);
        }

        // Create some specific category listings
        if (count($sellerProfiles) > 0) {
            // Electronics listings
            Listing::factory(5)->create([
                'seller_profile_id' => $sellerProfiles[0]->id,
                'category' => 'electronics',
                'condition' => 'excellent',
                'status' => 'active',
                'published_at' => now()->subDays(rand(1, 30)),
            ]);
        }

        if (count($sellerProfiles) > 1) {
            // Vehicle listings
            Listing::factory(3)->create([
                'seller_profile_id' => $sellerProfiles[1]->id,
                'category' => 'vehicles',
                'condition' => 'good',
                'price' => rand(5000, 50000),
                'status' => 'active',
                'published_at' => now()->subDays(rand(1, 30)),
            ]);
        }

        if (count($sellerProfiles) > 2) {
            // Service listings
            Listing::factory(4)->create([
                'seller_profile_id' => $sellerProfiles[2]->id,
                'category' => 'services',
                'condition' => null,
                'requires_appointment' => true,
                'status' => 'active',
                'published_at' => now()->subDays(rand(1, 30)),
            ]);
        }

        $this->command->info('Created ' . Listing::count() . ' listings');
        $this->command->info('Created ' . SellerProfile::count() . ' seller profiles');
        $this->command->info('Created ' . User::count() . ' users');
    }
}