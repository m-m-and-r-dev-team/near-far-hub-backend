<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SellerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        if (!$user->canSell()) {
            return response()->json([
                'message' => 'Access denied. You need to upgrade to a seller account to perform this action.',
                'upgrade_required' => true,
                'current_role' => $user->getRoleName()
            ], 403);
        }

        return $next($request);
    }
}