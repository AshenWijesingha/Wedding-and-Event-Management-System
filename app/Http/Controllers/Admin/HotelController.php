<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\HotelResource;
use App\Models\Hotel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class HotelController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Hotel::class, 'hotel');
    }

    public function index(Request $request): Response
    {
        $hotels = Hotel::query()
            ->withCount(['venues', 'packages'])
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->status, fn ($q, $s) => $q->where('approval_status', $s))
            ->orderBy('name')->paginate(15)->withQueryString();

        return Inertia::render('Hotels/Index', [
            'hotels' => HotelResource::collection($hotels),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Hotels/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['approval_status'] = 'draft';
        $hotel = Hotel::create($data);

        return redirect()->route('admin.hotels.edit', $hotel)->with('success', 'Hotel created. Submit it for approval when ready.');
    }

    public function edit(Hotel $hotel): Response
    {
        $hotel->load(['venues', 'packages']);

        return Inertia::render('Hotels/Edit', [
            'hotel' => (new HotelResource($hotel))->resolve(),
            'venues' => $hotel->venues,
            'packages' => $hotel->packages,
        ]);
    }

    public function update(Request $request, Hotel $hotel): RedirectResponse
    {
        $hotel->update($this->validated($request));

        return back()->with('success', 'Hotel updated.');
    }

    public function destroy(Hotel $hotel): RedirectResponse
    {
        $hotel->delete();

        return redirect()->route('admin.hotels.index')->with('success', 'Hotel deleted.');
    }

    public function submit(Hotel $hotel): RedirectResponse
    {
        $this->authorize('submit', $hotel);

        $request = request();
        abort_unless($hotel->name && $hotel->city, 422, 'Complete the hotel details before submitting.');

        $hotel->submit($request->user());

        \App\Models\User::whereNull('tenant_id')->role('super_admin')->get()
            ->each->notify(new \App\Notifications\ApprovalSubmitted(class_basename($hotel), $hotel->name, request()->user()->name));

        return back()->with('success', 'Submitted for approval.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'star_rating' => 'nullable|integer|min:1|max:5',
            'status' => 'nullable|in:active,inactive',
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;
        while (Hotel::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
