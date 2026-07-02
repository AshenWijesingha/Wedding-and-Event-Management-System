<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PackageResource;
use App\Http\Traits\ApiResponse;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PackageController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $packages = Package::query()
            ->approved()
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
            )
            ->when($request->status, fn ($q, $status) => $q->where('status', $status)
            )
            ->orderBy($request->sort_by ?? 'name', $request->sort_dir ?? 'asc')
            ->paginate($request->per_page ?? 15);

        return $this->success(PackageResource::collection($packages));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:packages',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'min_guests' => 'nullable|integer|min:1',
            'max_guests' => 'nullable|integer|min:1|gte:min_guests',
            'guest_pricing' => 'nullable|array',
            'included_services' => 'nullable|array',
            'included_services.*' => 'string',
            'status' => 'nullable|in:active,inactive,archived',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['status'] = $validated['status'] ?? 'active';

        $baseSlug = $validated['slug'];
        $i = 1;
        while (Package::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = "{$baseSlug}-{$i}";
            $i++;
        }

        $package = Package::create($validated);

        return $this->created(new PackageResource($package));
    }

    public function show(Package $package): JsonResponse
    {
        return $this->success(new PackageResource($package));
    }

    public function update(Request $request, Package $package): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:packages,slug,' . $package->id,
            'description' => 'nullable|string',
            'base_price' => 'sometimes|numeric|min:0',
            'min_guests' => 'nullable|integer|min:1',
            'max_guests' => 'nullable|integer|min:1',
            'guest_pricing' => 'nullable|array',
            'included_services' => 'nullable|array',
            'included_services.*' => 'string',
            'status' => 'nullable|in:active,inactive,archived',
        ]);

        $package->update($validated);

        return $this->success(new PackageResource($package->fresh()));
    }

    public function destroy(Package $package): JsonResponse
    {
        if ($package->bookings()->whereNotIn('status', ['cancelled'])->exists()) {
            return $this->error('Cannot delete package with active bookings.', 422);
        }

        $package->delete();

        return $this->noContent();
    }
}
