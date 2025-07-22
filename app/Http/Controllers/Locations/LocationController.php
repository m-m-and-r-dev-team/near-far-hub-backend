<?php

declare(strict_types=1);

namespace App\Http\Controllers\Locations;

use App\Http\Controllers\Controller;
use App\Services\Locations\HybridLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    public function __construct(
        private readonly HybridLocationService $hybridLocationService
    ) {
    }

    /**
     * Get location suggestions for autocomplete
     * GET /api/locations/suggestions?q=New York&limit=10
     */
    public function getSuggestions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2|max:100',
            'limit' => 'sometimes|integer|min:1|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid input',
                'messages' => $validator->errors()
            ], 400);
        }

        $input = $request->get('q');
        $limit = $request->get('limit', 10);

        $suggestions = $this->hybridLocationService->getLocationSuggestions($input, $limit);

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions['data'],
            'meta' => [
                'source' => $suggestions['source'],
                'total_results' => $suggestions['total_results'] ?? count($suggestions['data']),
                'query' => $input,
                'limit' => $limit
            ]
        ]);
    }

    /**
     * Validate and enrich location data
     * POST /api/locations/validate
     */
    public function validateLocation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'location' => 'required|array',
            'location.description' => 'sometimes|string',
            'location.place_id' => 'sometimes|string',
            'location.source' => 'sometimes|string|in:local,external'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid location data',
                'messages' => $validator->errors()
            ], 400);
        }

        $locationData = $request->get('location');
        $enrichedData = $this->hybridLocationService->validateAndEnrichLocation($locationData);

        return response()->json([
            'success' => true,
            'location' => $enrichedData,
            'valid' => !empty($enrichedData['latitude']) && !empty($enrichedData['longitude'])
        ]);
    }

    /**
     * Geocode an address
     * GET /api/locations/geocode?address=123 Main St, New York
     */
    public function geocode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'address' => 'required|string|min:5|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid address',
                'messages' => $validator->errors()
            ], 400);
        }

        $address = $request->get('address');
        $result = $this->hybridLocationService->geocodeAddress($address);

        if (!$result) {
            return response()->json([
                'error' => 'Address could not be geocoded',
                'address' => $address
            ], 404);
        }

        return response()->json([
            'success' => true,
            'result' => $result,
            'query' => $address
        ]);
    }

    /**
     * Get popular locations
     * GET /api/locations/popular?limit=20
     */
    public function getPopularLocations(Request $request): JsonResponse
    {
        $limit = min($request->get('limit', 20), 50);
        $popular = $this->hybridLocationService->getPopularLocations($limit);

        return response()->json([
            'success' => true,
            'locations' => $popular['data'],
            'meta' => [
                'source' => $popular['source'],
                'limit' => $limit,
                'cached_at' => $popular['cached_at'] ?? null
            ]
        ]);
    }
}
