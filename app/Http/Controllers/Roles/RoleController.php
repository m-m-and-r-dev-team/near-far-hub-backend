<?php

declare(strict_types=1);

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Http\Resources\Roles\RoleResource;
use App\Http\Resources\Users\UserResource;
use App\Models\Roles\Role;
use App\Models\User;
use App\Services\Repositories\Auth\AuthLogicRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    public function __construct(
        private readonly AuthLogicRepository $authLogicRepository
    )
    {
    }

    /**
     * Get all available roles
     */
    public function getAllAvailableRoles(): AnonymousResourceCollection
    {
        $roles = Role::where(Role::IS_ACTIVE, true)->get();

        return RoleResource::collection($roles);
    }

    /**
     * Get current user's role
     */
    public function getCurrentUserRole(): RoleResource
    {
        /** @var User $user */
        $user = Auth::user();

        return RoleResource::make($user->relatedRole());
    }

    /**
     * Upgrade current user to seller
     */
    public function upgradeToSeller(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->canUpgradeToSeller()) {
            return response()->json([
                'message' => 'You cannot upgrade to seller. You are already a ' . $user->getRoleDisplayName() . '.',
                'current_role' => $user->getRoleName()
            ], 400);
        }

        $success = $this->authLogicRepository->upgradeToSeller($user);

        if ($success) {
            $user->refresh();
            $user->load(User::ROLE_RELATION);

            return response()->json([
                'message' => 'Successfully upgraded to seller account!',
                'user' => UserResource::make($user),
            ]);
        }

        return response()->json([
            'message' => 'Failed to upgrade to seller account. Please try again.'
        ], 500);
    }

    /**
     * Check if user can upgrade to seller
     */
    public function canUpgradeToSeller(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        return response()->json([
            'canUpgrade' => $user->canUpgradeToSeller(),
            'currentRole' => $user->getRoleName(),
            'currentRoleDisplay' => $user->getRoleDisplayName(),
        ]);
    }

    /**
     * Get role permissions (for frontend to know what features to show)
     */
    public function permissions(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        return response()->json([
            'role' => $user->getRoleName(),
            'roleDisplay' => $user->getRoleDisplayName(),
            'permissions' => [
                'canSell' => $user->canSell(),
                'canModerate' => $user->canModerate(),
                'canAccessAdmin' => $user->canAccessAdmin(),
                'canUpgradeToSeller' => $user->canUpgradeToSeller(),
            ],
            'features' => [
                'listings' => [
                    'canView' => true,
                    'canCreate' => $user->canSell(),
                    'canEdit' => $user->canSell(),
                    'canDelete' => $user->canSell(),
                ],
                'moderation' => [
                    'canView' => $user->canModerate(),
                    'canApprove' => $user->canModerate(),
                    'canReject' => $user->canModerate(),
                ],
                'admin' => [
                    'canAccess' => $user->canAccessAdmin(),
                    'canManageUsers' => $user->canAccessAdmin(),
                    'canManageRoles' => $user->canAccessAdmin(),
                ],
            ],
        ]);
    }
}