<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        if ($request->user()->isSuperAdmin()) {
            return $this->platform();
        }

        // Tenant-level dashboard (data wiring handled separately).
        return Inertia::render('Dashboard');
    }

    /**
     * Cross-tenant overview for the platform super admin. Booking/Payment/User
     * are tenant-scoped models, so queries explicitly drop the tenant scope.
     */
    private function platform(): Response
    {
        $monthStart = Carbon::now()->startOfMonth();

        $newTenants = Tenant::where('created_at', '>=', $monthStart)->count();

        $revenueAllTime = (float) Payment::withoutTenantScope()->completed()->sum('amount');
        $revenueThisMonth = (float) Payment::withoutTenantScope()->completed()
            ->where('payment_date', '>=', $monthStart)->sum('amount');

        // Monthly recurring revenue: monthly price of every active/trial tenant's plan.
        $mrr = (float) Tenant::whereIn('status', ['active', 'trial'])
            ->whereNotNull('plan_id')
            ->with('plan:id,price_monthly')
            ->get()
            ->sum(fn (Tenant $t) => (float) ($t->plan->price_monthly ?? 0));

        return Inertia::render('Platform/Dashboard', [
            'stats' => [
                'tenants_total'     => Tenant::count(),
                'tenants_active'    => Tenant::where('status', 'active')->count(),
                'tenants_trial'     => Tenant::where('status', 'trial')->count(),
                'tenants_suspended' => Tenant::where('status', 'suspended')->count(),
                'tenants_new'       => $newTenants,
                'users_total'       => User::withoutTenantScope()->whereNotNull('tenant_id')->count(),
                'bookings_total'    => Booking::withoutTenantScope()->count(),
                'revenue_all_time'  => $revenueAllTime,
                'revenue_month'     => $revenueThisMonth,
                'mrr'               => $mrr,
            ],
            'recentTenants' => Tenant::with('plan:id,name')
                ->withCount(['users', 'bookings'])
                ->latest()
                ->take(5)
                ->get()
                ->map(fn (Tenant $t) => [
                    'id'            => $t->id,
                    'name'          => $t->name,
                    'status'        => $t->status,
                    'plan'          => $t->plan?->name,
                    'users_count'   => $t->users_count,
                    'bookings_count' => $t->bookings_count,
                    'created_at'    => $t->created_at?->toDateString(),
                ]),
            'planDistribution' => Plan::withCount('tenants')
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Plan $p) => ['name' => $p->name, 'count' => $p->tenants_count]),
        ]);
    }
}
