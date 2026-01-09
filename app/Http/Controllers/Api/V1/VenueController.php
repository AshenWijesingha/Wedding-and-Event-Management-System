<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    /**
     * Display a listing of venues.
     */
    public function index(Request $request): JsonResponse
    {
        $venues = Venue::query()
            ->when($request->search, fn ($query, $search) => 
                $query->where('name', 'like', "%{$search}%")
            )
            ->when($request->type, fn ($query, $type) => 
                $query->where('type', $type)
            )
            ->paginate($request->per_page ?? 15);

        return response()->json($venues);
    }

    /**
     * Display the specified venue.
     */
    public function show(Venue $venue): JsonResponse
    {
        return response()->json($venue);
    }

    /**
     * Get availability for a venue.
     */
    public function availability(Venue $venue, Request $request): JsonResponse
    {
        // TODO: Implement availability check
        return response()->json([
            'venue_id' => $venue->id,
            'available' => true,
            'dates' => [],
        ]);
    }
}
