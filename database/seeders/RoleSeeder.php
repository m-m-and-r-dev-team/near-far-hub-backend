<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Roles\RoleEnum;
use App\Models\Roles\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => RoleEnum::BUYER->value,
                'display_name' => 'Buyer',
                'description' => 'Can browse and purchase items. Default role for new users.',
                'permissions' => [
                    'browse_listings',
                    'view_products',
                    'add_to_cart',
                    'make_purchases',
                    'leave_reviews',
                    'message_sellers',
                    'manage_profile',
                    'view_order_history'
                ],
                'is_active' => true,
            ],
            [
                'name' => RoleEnum::SELLER->value,
                'display_name' => 'Seller',
                'description' => 'Can create listings and sell items. Paid upgrade from Buyer.',
                'permissions' => [
                    'browse_listings',
                    'view_products',
                    'add_to_cart',
                    'make_purchases',
                    'leave_reviews',
                    'message_sellers',
                    'manage_profile',
                    'view_order_history',
                    'create_listings',
                    'edit_own_listings',
                    'delete_own_listings',
                    'manage_inventory',
                    'view_sales_analytics',
                    'respond_to_messages',
                    'seller_dashboard'
                ],
                'is_active' => true,
            ],
            [
                'name' => RoleEnum::MODERATOR->value,
                'display_name' => 'Moderator',
                'description' => 'Can moderate content and handle disputes. Staff role.',
                'permissions' => [
                    'browse_listings',
                    'view_products',
                    'add_to_cart',
                    'make_purchases',
                    'leave_reviews',
                    'message_sellers',
                    'manage_profile',
                    'view_order_history',
                    'moderate_listings',
                    'approve_listings',
                    'reject_listings',
                    'handle_reports',
                    'moderate_communications',
                    'moderation_dashboard',
                    'view_user_reports'
                ],
                'is_active' => true,
            ],
            [
                'name' => RoleEnum::ADMIN->value,
                'display_name' => 'Administrator',
                'description' => 'Full access to all platform features and settings.',
                'permissions' => [
                    'all_permissions',
                    'manage_users',
                    'manage_roles',
                    'manage_moderators',
                    'system_settings',
                    'financial_reports',
                    'platform_analytics',
                    'database_access'
                ],
                'is_active' => true,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );
        }
    }
}