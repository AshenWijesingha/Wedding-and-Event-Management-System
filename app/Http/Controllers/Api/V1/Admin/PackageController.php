<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $packages = Package::paginate($request->per_page ?? 15);
        return response()->json($packages);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'venue_id' => 'required|exists:venues,id',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
        ]);

        $package = Package::create($validated);
        return response()->json($package, 201);
    }

    public function show(Package $package): JsonResponse
    {
        return response()->json($package);
    }

    public function update(Request $request, Package $package): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'sometimes|numeric|min:0',
        ]);

        $package->update($validated);
        return response()->json($package);
    }

    public function destroy(Package $package): JsonResponse
    {
        $package->delete();
        return response()->json(null, 204);
    }
}
