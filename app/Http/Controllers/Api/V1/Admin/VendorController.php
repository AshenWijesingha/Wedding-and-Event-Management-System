<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\VendorResource;
use App\Http\Traits\ApiResponse;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $vendors = Vendor::query()
            ->when($request->search, fn ($q, $s) =>
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('contact_name', 'like', "%{$s}%")
            )
            ->when($request->category, fn ($q, $cat) => $q->where('category', $cat))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return $this->success(VendorResource::collection($vendors));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'category'            => 'required|string|max:50',
            'contact_name'        => 'nullable|string|max:255',
            'email'               => 'nullable|email|max:255',
            'phone'               => 'nullable|string|max:50',
            'website'             => 'nullable|url|max:255',
            'description'         => 'nullable|string',
            'base_rate'           => 'nullable|numeric|min:0',
            'rate_type'           => 'nullable|in:fixed,hourly,per_event',
            'services'            => 'nullable|array',
            'services.*'          => 'string|max:100',
            'status'              => 'nullable|in:active,inactive',
            'notes'               => 'nullable|string',
        ]);

        $vendor = Vendor::create($data);

        return $this->success(new VendorResource($vendor), 'Vendor created.', 201);
    }

    public function show(Vendor $vendor): JsonResponse
    {
        return $this->success(new VendorResource($vendor->load('bookings')));
    }

    public function update(Request $request, Vendor $vendor): JsonResponse
    {
        $data = $request->validate([
            'name'         => 'sometimes|string|max:255',
            'category'     => 'sometimes|string|max:50',
            'contact_name' => 'nullable|string|max:255',
            'email'        => 'nullable|email|max:255',
            'phone'        => 'nullable|string|max:50',
            'website'      => 'nullable|url|max:255',
            'description'  => 'nullable|string',
            'base_rate'    => 'nullable|numeric|min:0',
            'rate_type'    => 'nullable|in:fixed,hourly,per_event',
            'services'     => 'nullable|array',
            'services.*'   => 'string|max:100',
            'status'       => 'nullable|in:active,inactive',
            'notes'        => 'nullable|string',
        ]);

        $vendor->update($data);

        return $this->success(new VendorResource($vendor), 'Vendor updated.');
    }

    public function destroy(Vendor $vendor): JsonResponse
    {
        if ($vendor->bookings()->whereNotIn('booking_vendor.status', ['cancelled'])->exists()) {
            return $this->error('Cannot delete vendor with active booking assignments.', 422);
        }

        $vendor->delete();

        return $this->success(null, 'Vendor deleted.');
    }
}
