<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $venues = Venue::paginate($request->per_page ?? 15);
        return response()->json($venues);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:venues',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer',
            'type' => 'nullable|string',
        ]);

        $venue = Venue::create($validated);
        return response()->json($venue, 201);
    }

    public function show(Venue $venue): JsonResponse
    {
        return response()->json($venue);
    }

    public function update(Request $request, Venue $venue): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer',
            'type' => 'nullable|string',
        ]);

        $venue->update($validated);
        return response()->json($venue);
    }

    public function destroy(Venue $venue): JsonResponse
    {
        $venue->delete();
        return response()->json(null, 204);
    }
}
