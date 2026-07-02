<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    /**
     * Display a listing of packages.
     */
    public function index(Request $request): JsonResponse
    {
        $packages = Package::query()
            ->when($request->venue_id, fn ($query, $venueId) => $query->where('venue_id', $venueId)
            )
            ->paginate($request->per_page ?? 15);

        return response()->json($packages);
    }

    /**
     * Display the specified package.
     */
    public function show(Package $package): JsonResponse
    {
        return response()->json($package);
    }
}
