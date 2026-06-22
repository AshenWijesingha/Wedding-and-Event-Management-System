<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\VenueResource;
use App\Http\Traits\ApiResponse;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VenueController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $venues = Venue::query()
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
            )
            ->when($request->status, fn ($q, $status) => $q->where('status', $status)
            )
            ->when($request->capacity_min, fn ($q, $min) => $q->where('capacity_max', '>=', $min)
            )
            ->when($request->capacity_max, fn ($q, $max) => $q->where('capacity_min', '<=', $max)
            )
            ->orderBy($request->sort_by ?? 'name', $request->sort_dir ?? 'asc')
            ->paginate($request->per_page ?? 15);

        return $this->success(VenueResource::collection($venues));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:venues',
            'description' => 'nullable|string',
            'capacity_min' => 'required|integer|min:1',
            'capacity_max' => 'required|integer|min:1|gte:capacity_min',
            'base_price' => 'required|numeric|min:0',
            'weekend_surcharge' => 'nullable|numeric|min:0',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string',
            'images' => 'nullable|array',
            'images.*' => 'string',
            'status' => 'nullable|in:active,inactive,maintenance',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['status'] = $validated['status'] ?? 'active';

        // Ensure slug is unique
        $baseSlug = $validated['slug'];
        $i = 1;
        while (Venue::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = "{$baseSlug}-{$i}";
            $i++;
        }

        $venue = Venue::create($validated);

        return $this->created(new VenueResource($venue));
    }

    public function show(Venue $venue): JsonResponse
    {
        return $this->success(new VenueResource($venue->load('packages')));
    }

    public function update(Request $request, Venue $venue): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:venues,slug,' . $venue->id,
            'description' => 'nullable|string',
            'capacity_min' => 'nullable|integer|min:1',
            'capacity_max' => 'nullable|integer|min:1',
            'base_price' => 'nullable|numeric|min:0',
            'weekend_surcharge' => 'nullable|numeric|min:0',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string',
            'images' => 'nullable|array',
            'images.*' => 'string',
            'status' => 'nullable|in:active,inactive,maintenance',
        ]);

        $venue->update($validated);

        return $this->success(new VenueResource($venue->fresh()));
    }

    public function destroy(Venue $venue): JsonResponse
    {
        if ($venue->bookings()->whereNotIn('status', ['cancelled'])->exists()) {
            return $this->error('Cannot delete venue with active bookings.', 422);
        }

        $venue->delete();

        return $this->noContent();
    }
}
