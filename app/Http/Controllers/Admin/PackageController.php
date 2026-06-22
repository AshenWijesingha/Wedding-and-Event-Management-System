<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PackageController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Package::class, 'package');
    }

    public function index(Request $request): Response
    {
        $packages = Package::query()
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
            )
            ->when($request->status, fn ($q, $status) => $q->where('status', $status)
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Packages/Index', [
            'packages' => PackageResource::collection($packages),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Packages/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'manager']), 403);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:packages',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'min_guests' => 'nullable|integer|min:1',
            'max_guests' => 'nullable|integer|min:1',
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

        try {
            Package::create($validated);
        } catch (UniqueConstraintViolationException $e) {
            return back()->withErrors(['slug' => 'A package with this slug already exists.'])->withInput();
        }

        return redirect()->route('admin.packages.index')->with('success', 'Package created successfully.');
    }

    public function edit(Package $package): Response
    {
        return Inertia::render('Packages/Edit', [
            'package' => new PackageResource($package),
        ]);
    }

    public function update(Request $request, Package $package): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'manager']), 403);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:packages,slug,' . $package->id,
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'min_guests' => 'nullable|integer|min:1',
            'max_guests' => 'nullable|integer|min:1',
            'included_services' => 'nullable|array',
            'included_services.*' => 'string',
            'status' => 'nullable|in:active,inactive,archived',
        ]);

        $package->update($validated);

        return redirect()->route('admin.packages.index')->with('success', 'Package updated successfully.');
    }

    public function destroy(Package $package): RedirectResponse
    {
        abort_unless(request()->user()->hasAnyRole(['admin', 'manager']), 403);
        if ($package->bookings()->whereNotIn('status', ['cancelled'])->exists()) {
            return back()->with('error', 'Cannot delete package with active bookings.');
        }

        $package->delete();

        return redirect()->route('admin.packages.index')->with('success', 'Package deleted.');
    }
}
