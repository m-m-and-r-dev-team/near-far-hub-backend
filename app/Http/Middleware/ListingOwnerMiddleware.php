<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Repositories\Listings\ListingRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ListingOwnerMiddleware
{
    public function __construct(
        private readonly ListingRepository $listingRepository
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $listingId = $request->route('listingId') ?? $request->route('listing');

        if (!$listingId) {
            return response()->json(['message' => 'Listing ID not found'], 400);
        }

        try {
            $this->listingRepository->findByIdAndUserId((int) $listingId, auth()->id());
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Listing not found or you do not have permission to access it'
            ], 404);
        }

        return $next($request);
    }
}