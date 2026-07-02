<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\VenueResource;
use App\Http\Traits\ApiResponse;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $venues = Venue::active()
            ->approved()
            ->where(fn ($q) => $q->whereNull('hotel_id')->orWhereHas('hotel', fn ($h) => $h->approved()))
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
            )
            ->when($request->capacity_min, fn ($q, $min) => $q->where('capacity_max', '>=', $min)
            )
            ->when($request->capacity_max, fn ($q, $max) => $q->where('capacity_min', '<=', $max)
            )
            ->orderBy('name')
            ->paginate($request->per_page ?? 12);

        return $this->success(VenueResource::collection($venues));
    }

    public function show(Venue $venue): JsonResponse
    {
        if ($venue->status !== 'active') {
            return $this->notFound('Venue not found.');
        }

        if (! $venue->isApproved()) {
            abort(404);
        }

        if ($venue->hotel_id !== null && ! optional($venue->hotel)->isApproved()) {
            abort(404);
        }

        return $this->success(new VenueResource($venue));
    }

    public function availability(Venue $venue, Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
        ]);

        $available = $venue->isAvailableOn($request->date);

        return $this->success([
            'venue_id' => $venue->id,
            'date' => $request->date,
            'available' => $available,
        ]);
    }
}
