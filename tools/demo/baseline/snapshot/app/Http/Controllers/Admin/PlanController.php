<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Platform-level subscription plan management. Restricted to super admins via
 * the route group. Plans are landlord records (not tenant-scoped).
 */
class PlanController extends Controller
{
    /** Known per-tenant limit keys; null value means unlimited. */
    private const LIMIT_KEYS = ['users', 'venues', 'bookings'];

    public function index(): Response
    {
        $plans = Plan::withCount('tenants')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Plan $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'description' => $p->description,
                'price_monthly' => (float) $p->price_monthly,
                'price_yearly' => (float) $p->price_yearly,
                'features' => $p->features ?? [],
                'limits' => $p->limits ?? [],
                'is_active' => $p->is_active,
                'sort_order' => $p->sort_order,
                'tenants_count' => $p->tenants_count,
            ]);

        return Inertia::render('Plans/Index', ['plans' => $plans]);
    }

    public function create(): Response
    {
        return Inertia::render('Plans/Create', ['limitKeys' => self::LIMIT_KEYS]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        Plan::create($data);

        return redirect('/admin/plans')->with('success', 'Plan created.');
    }

    public function edit(Plan $plan): Response
    {
        return Inertia::render('Plans/Edit', [
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price_monthly' => (float) $plan->price_monthly,
                'price_yearly' => (float) $plan->price_yearly,
                'features' => $plan->features ?? [],
                'limits' => $plan->limits ?? [],
                'is_active' => $plan->is_active,
                'sort_order' => $plan->sort_order,
            ],
            'limitKeys' => self::LIMIT_KEYS,
        ]);
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $data = $this->validateData($request, $plan);

        $plan->update($data);

        return redirect('/admin/plans')->with('success', 'Plan updated.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->tenants()->exists()) {
            return back()->with('error', 'Cannot delete a plan that still has tenants. Reassign them first.');
        }

        $plan->delete();

        return redirect('/admin/plans')->with('success', 'Plan deleted.');
    }

    /**
     * Validate and normalize plan input. Features arrive as a list of strings;
     * limits as a map of LIMIT_KEYS to nullable integers (null = unlimited).
     */
    private function validateData(Request $request, ?Plan $plan = null): array
    {
        // Blank limit inputs arrive as "" from the form; normalize to null so the
        // nullable|integer rule accepts them (treated as "unlimited").
        $request->merge([
            'limits' => collect($request->input('limits', []))
                ->map(fn ($v) => ($v === '' || $v === null) ? null : $v)
                ->all(),
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('plans', 'slug')->ignore($plan?->id)],
            'description' => 'nullable|string|max:1000',
            'price_monthly' => 'required|numeric|min:0',
            'price_yearly' => 'required|numeric|min:0',
            'features' => 'array',
            'features.*' => 'nullable|string|max:255',
            'limits' => 'array',
            'limits.*' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        // Keep only recognized limit keys with a real value.
        $limits = collect($validated['limits'] ?? [])
            ->only(self::LIMIT_KEYS)
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v) => (int) $v)
            ->all();

        return [
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'price_monthly' => $validated['price_monthly'],
            'price_yearly' => $validated['price_yearly'],
            'features' => array_values(array_filter($validated['features'] ?? [], fn ($f) => filled($f))),
            'limits' => $limits,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $validated['sort_order'] ?? 0,
        ];
    }
}
