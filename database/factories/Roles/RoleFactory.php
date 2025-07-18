<?php

declare(strict_types=1);

namespace Database\Factories\Roles;

use App\Models\Roles\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method Role create($attributes = [], ?Model $parent = null)
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            Role::NAME => $this->faker->name,
            Role::DISPLAY_NAME => 'Buyer',
            Role::DESCRIPTION => $this->faker->paragraph,
            Role::PERMISSIONS => $this->faker->randomElements([
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
                'moderate_listings',
                'approve_listings',
                'reject_listings',
                'system_settings',
                'platform_analytics'
            ], $this->faker->numberBetween(3, 8)),
            Role::IS_ACTIVE => true,
        ];
    }
}
