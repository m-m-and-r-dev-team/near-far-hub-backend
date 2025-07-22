<?php

namespace Database\Seeders;

use App\Enums\Roles\RoleEnum;
use App\Models\Roles\Role;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            LocationSeeder::class,
        ]);

        $buyerRole = Role::where(Role::NAME, RoleEnum::BUYER->value)->first();
        $adminRole = Role::where(Role::NAME, RoleEnum::ADMIN->value)->first();
        $sellerRole = Role::where(Role::NAME, RoleEnum::SELLER->value)->first();

        User::factory()->create([
            'name' => 'Test Buyer',
            'email' => 'buyer@example.com',
            'password' => Hash::make('password'),
            'role_id' => $buyerRole->getId(),
        ]);

        User::factory()->create([
            'name' => 'Test Seller',
            'email' => 'seller@example.com',
            'password' => Hash::make('password'),
            'role_id' => $sellerRole->getId(),
        ]);

        User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->getId(),
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role_id' => $buyerRole->getId(),
        ]);
    }
}