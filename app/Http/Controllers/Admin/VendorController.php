<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\VendorResource;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VendorController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Vendor::class, 'vendor');
    }

    public function index(Request $request): Response
    {
        $vendors = Vendor::query()
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('contact_name', 'like', "%{$s}%")
            )
            ->when($request->category, fn ($q, $cat) => $q->where('category', $cat))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->withCount('bookings')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Vendors/Index', [
            'vendors' => VendorResource::collection($vendors),
            'filters' => $request->only(['search', 'category', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Vendors/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'manager']), 403);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:50',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'base_rate' => 'nullable|numeric|min:0',
            'rate_type' => 'nullable|in:fixed,hourly,per_event',
            'services' => 'nullable|array',
            'services.*' => 'string|max:100',
            'status' => 'nullable|in:active,inactive',
            'notes' => 'nullable|string',
        ]);

        Vendor::create($data);

        return redirect('/admin/vendors')->with('success', 'Vendor created successfully.');
    }

    public function show(Vendor $vendor): Response
    {
        return Inertia::render('Vendors/Show', [
            'vendor' => new VendorResource($vendor->load('bookings')),
        ]);
    }

    public function edit(Vendor $vendor): Response
    {
        return Inertia::render('Vendors/Edit', [
            'vendor' => new VendorResource($vendor),
        ]);
    }

    public function update(Request $request, Vendor $vendor): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'manager']), 403);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:50',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'base_rate' => 'nullable|numeric|min:0',
            'rate_type' => 'nullable|in:fixed,hourly,per_event',
            'services' => 'nullable|array',
            'services.*' => 'string|max:100',
            'status' => 'nullable|in:active,inactive',
            'notes' => 'nullable|string',
        ]);

        $vendor->update($data);

        return redirect('/admin/vendors')->with('success', 'Vendor updated.');
    }

    public function destroy(Vendor $vendor): RedirectResponse
    {
        abort_unless(request()->user()->hasAnyRole(['admin', 'manager']), 403);
        if ($vendor->bookings()->whereNotIn('booking_vendor.status', ['cancelled'])->exists()) {
            return back()->with('error', 'Cannot delete vendor with active booking assignments.');
        }

        $vendor->delete();

        return redirect('/admin/vendors')->with('success', 'Vendor deleted.');
    }
}
