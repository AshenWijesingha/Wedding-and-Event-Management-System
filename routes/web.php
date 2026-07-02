<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\CustomFieldController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OnboardingController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\PlatformSettingsController;
use App\Http\Controllers\Admin\PluginController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\QuotationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SessionManagementController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\TaskController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PayHereWebhookController;
use App\Http\Controllers\Portal\PortalController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\VenueController;
use App\Http\Middleware\EnforceSessionTimeout;
use App\Http\Middleware\EnsureOnboarded;
use App\Http\Middleware\SetCurrentTenant;
use App\Models\Venue;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

// Public website routes
Route::get('/', function () {
    $featuredVenues = Venue::active()->approved()->where(fn ($q) => $q->whereNull('hotel_id')->orWhereHas('hotel', fn ($h) => $h->approved()))->orderByDesc('capacity_max')->take(4)->get();
    $hallCount = Venue::active()->approved()->where(fn ($q) => $q->whereNull('hotel_id')->orWhereHas('hotel', fn ($h) => $h->approved()))->count();
    $hotelCount = Venue::active()->approved()->where(fn ($q) => $q->whereNull('hotel_id')->orWhereHas('hotel', fn ($h) => $h->approved()))->get()
        ->map(fn ($v) => Str::before($v->name, ' — '))
        ->unique()->count();

    return view('welcome', compact('featuredVenues', 'hallCount', 'hotelCount'));
})->name('home');

Route::get('/venues', [VenueController::class, 'index'])->name('venues.index');
Route::get('/venues/{venue:slug}', [VenueController::class, 'show'])->name('venues.show');

Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

// Directory of all user-facing public pages, each linked separately.
Route::get('/links', function () {
    return view('links');
})->name('links');

Route::post('/inquiry', [InquiryController::class, 'store'])->name('inquiry.store')->middleware('throttle:inquiry');

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Password re-confirmation gate for sensitive actions.
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store'])->middleware('throttle:6,1');

    // Email verification (signed link delivered by the verification notification)
    Route::get('verify-email/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        // Clients manage their account in the portal; everyone else in the admin area.
        $target = $request->user()->hasRole('client') ? '/portal' : '/admin/profile';

        return redirect($target)->with('success', 'Email verified successfully.');
    })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
});

// Admin panel routes (Inertia.js SPA).
// The whole area is locked to staff-and-above; the `client` role is redirected to the
// portal. Each section is gated by its `<resource>.view` permission, and individual
// mutating actions are protected further by policies (see app/Policies) and, for the
// most sensitive ones, explicit permission middleware below.
Route::middleware([
    'auth',
    EnforceSessionTimeout::class,
    SetCurrentTenant::class,
    'tenant.active',
    'role:super_admin|tenant_owner|admin|manager|staff',
])->prefix('admin')->name('admin.')->group(function () {
    // Only the dashboard landing redirects first-time owners/admins to the
    // setup wizard (once). Scoped here so it never intercepts other admin pages.
    Route::get('/', [DashboardController::class, 'index'])
        ->middleware(EnsureOnboarded::class)
        ->name('dashboard');

    // First-run setup wizard — owner/admin only.
    Route::middleware('role:super_admin|tenant_owner|admin')->group(function () {
        Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
        Route::post('/onboarding/branding', [OnboardingController::class, 'storeBranding'])->name('onboarding.branding');
        Route::post('/onboarding/venue', [OnboardingController::class, 'storeVenue'])->name('onboarding.venue');
        Route::post('/onboarding/package', [OnboardingController::class, 'storePackage'])->name('onboarding.package');
        Route::post('/onboarding/invite', [OnboardingController::class, 'invite'])->name('onboarding.invite');
        Route::post('/onboarding/finish', [OnboardingController::class, 'finish'])->name('onboarding.finish');
    });

    // Exit impersonation — reachable while acting as the impersonated user.
    Route::post('/impersonate-stop', [ImpersonationController::class, 'stop'])->name('impersonate.stop');

    // Venues — full resource with web controller
    Route::middleware('permission:venues.view')->group(function () {
        Route::resource('venues', App\Http\Controllers\Admin\VenueController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::get('venues/{venue}/availability', [App\Http\Controllers\Admin\VenueController::class, 'availability'])->name('admin.venues.availability');
        Route::post('venues/{venue}/submit', [App\Http\Controllers\Admin\VenueController::class, 'submit'])->name('venues.submit');
    });

    // Hotels — full resource with approval workflow
    Route::middleware('permission:hotels.view')->group(function () {
        Route::resource('hotels', App\Http\Controllers\Admin\HotelController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::post('hotels/{hotel}/submit', [App\Http\Controllers\Admin\HotelController::class, 'submit'])
            ->name('hotels.submit');
    });

    // Packages
    Route::middleware('permission:packages.view')->group(function () {
        Route::resource('packages', App\Http\Controllers\Admin\PackageController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::post('packages/{package}/submit', [App\Http\Controllers\Admin\PackageController::class, 'submit'])->name('packages.submit');
    });

    // Bookings
    Route::middleware('permission:bookings.view')->group(function () {
        Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/create', [BookingController::class, 'create'])->middleware('permission:bookings.create')->name('bookings.create');
        Route::post('/bookings', [BookingController::class, 'store'])->middleware('permission:bookings.create')->name('bookings.store');
        Route::get('/bookings/{booking}/edit', [BookingController::class, 'edit'])->middleware('permission:bookings.edit')->name('bookings.edit');
        Route::put('/bookings/{booking}', [BookingController::class, 'update'])->middleware('permission:bookings.edit')->name('bookings.update');
        Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
        Route::post('/bookings/{booking}/confirm', [BookingController::class, 'confirm'])->middleware('permission:bookings.confirm')->name('bookings.confirm');
        Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->middleware('permission:bookings.cancel')->name('bookings.cancel');
        Route::post('/bookings/{booking}/vendors', [BookingController::class, 'attachVendor'])->middleware('permission:bookings.edit')->name('bookings.vendors.attach');
        Route::delete('/bookings/{booking}/vendors/{vendor}', [BookingController::class, 'detachVendor'])->middleware('permission:bookings.edit')->name('bookings.vendors.detach');
    });

    // Calendar (unified events view of all bookings)
    Route::get('/calendar', [CalendarController::class, 'index'])
        ->middleware('permission:bookings.view')->name('calendar.index');

    // In-app notifications (mark read)
    Route::post('/notifications/read', [NotificationController::class, 'read'])
        ->name('notifications.read');

    // Vendors
    Route::middleware('permission:vendors.view')->group(function () {
        Route::resource('vendors', VendorController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    });

    // Staff (employee records)
    Route::middleware('permission:staff.view')->group(function () {
        Route::resource('staff', StaffController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    });

    // Tasks
    Route::middleware('permission:tasks.view')->group(function () {
        Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
        Route::post('/tasks', [TaskController::class, 'store'])->middleware('permission:tasks.create')->name('tasks.store');
        Route::patch('/tasks/{task}', [TaskController::class, 'update'])->middleware('permission:tasks.edit')->name('tasks.update');
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->middleware('permission:tasks.delete')->name('tasks.destroy');
    });

    // Inquiries
    Route::middleware('permission:inquiries.view')->group(function () {
        Route::get('/inquiries', [App\Http\Controllers\Admin\InquiryController::class, 'index'])->name('inquiries.index');
        Route::get('/inquiries/{inquiry}', [App\Http\Controllers\Admin\InquiryController::class, 'show'])->name('inquiries.show');
        Route::get('/inquiries/{inquiry}/pdf', [App\Http\Controllers\Admin\InquiryController::class, 'downloadPdf'])->name('inquiries.pdf');
        Route::get('/inquiries/{inquiry}/print', [App\Http\Controllers\Admin\InquiryController::class, 'print'])->name('inquiries.print');
        Route::patch('/inquiries/{inquiry}', [App\Http\Controllers\Admin\InquiryController::class, 'update'])->middleware('permission:inquiries.edit')->name('inquiries.update');
    });

    // Quotations
    Route::middleware('permission:quotations.view')->group(function () {
        Route::get('/quotations', [QuotationController::class, 'index'])->name('quotations.index');
        Route::get('/quotations/create', [QuotationController::class, 'create'])->middleware('permission:quotations.create')->name('quotations.create');
        Route::post('/quotations', [QuotationController::class, 'store'])->middleware('permission:quotations.create')->name('quotations.store');
        Route::get('/quotations/{quotation}', [QuotationController::class, 'show'])->name('quotations.show');
        Route::get('/quotations/{quotation}/pdf', [QuotationController::class, 'downloadPdf'])->name('quotations.pdf');
        Route::get('/quotations/{quotation}/print', [QuotationController::class, 'print'])->name('quotations.print');
        Route::post('/quotations/{quotation}/send', [QuotationController::class, 'send'])->middleware('permission:quotations.send')->name('quotations.send');
        Route::post('/quotations/{quotation}/accept', [QuotationController::class, 'accept'])->middleware('permission:quotations.edit')->name('quotations.accept');
        Route::post('/quotations/{quotation}/reject', [QuotationController::class, 'reject'])->middleware('permission:quotations.edit')->name('quotations.reject');
        Route::post('/quotations/{quotation}/expire', [QuotationController::class, 'markExpired'])->middleware('permission:quotations.edit')->name('quotations.expire');
    });

    Route::middleware('permission:clients.view')->group(function () {
        Route::resource('clients', ClientController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    });

    Route::middleware('permission:payments.view')->group(function () {
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
        Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
        Route::get('/bookings/{booking}/payments/create', [PaymentController::class, 'create'])->middleware('permission:payments.create')->name('payments.create');
        Route::post('/bookings/{booking}/payments', [PaymentController::class, 'store'])->middleware('permission:payments.create')->name('payments.store');
    });

    // Custom Fields
    Route::middleware('permission:custom_fields.view')->group(function () {
        Route::get('/custom-fields', [CustomFieldController::class, 'index'])->name('custom-fields.index');
        Route::post('/custom-fields', [CustomFieldController::class, 'store'])->middleware('permission:custom_fields.create')->name('custom-fields.store');
        Route::patch('/custom-fields/{customField}', [CustomFieldController::class, 'update'])->middleware('permission:custom_fields.edit')->name('custom-fields.update');
        Route::delete('/custom-fields/{customField}', [CustomFieldController::class, 'destroy'])->middleware('permission:custom_fields.delete')->name('custom-fields.destroy');
    });

    // Reports — viewing gated by reports.view; CSV/PDF exports require reports.export.
    Route::middleware('permission:reports.view')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/revenue', [ReportController::class, 'revenue'])->name('reports.revenue');
        Route::get('/reports/bookings', [ReportController::class, 'bookings'])->name('reports.bookings');
        Route::get('/reports/occupancy', [ReportController::class, 'occupancy'])->name('reports.occupancy');

        Route::middleware('permission:reports.export')->group(function () {
            Route::get('/reports/revenue/export', [ReportController::class, 'exportRevenue'])->name('reports.revenue.export');
            Route::get('/reports/revenue/pdf', [ReportController::class, 'pdfRevenue'])->name('reports.revenue.pdf');
            Route::get('/reports/bookings/export', [ReportController::class, 'exportBookings'])->name('reports.bookings.export');
            Route::get('/reports/bookings/pdf', [ReportController::class, 'pdfBookings'])->name('reports.bookings.pdf');
            Route::get('/reports/occupancy/export', [ReportController::class, 'exportOccupancy'])->name('reports.occupancy.export');
            Route::get('/reports/occupancy/pdf', [ReportController::class, 'pdfOccupancy'])->name('reports.occupancy.pdf');
        });
    });

    // Profile (self-service account management) — available to every admin-area user.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/verification-notification', [ProfileController::class, 'sendVerification'])->name('profile.verification');

    // Active sessions / device management (self-service, every admin-area user).
    Route::get('/profile/sessions', [SessionManagementController::class, 'index'])->name('sessions.index');
    Route::delete('/profile/sessions/{id}', [SessionManagementController::class, 'destroy'])->middleware('password.confirm')->name('sessions.destroy');
    Route::delete('/profile/sessions', [SessionManagementController::class, 'destroyOthers'])->middleware('password.confirm')->name('sessions.destroy-others');

    // User management. Viewing/managing requires users.view; deletion requires users.delete
    // (which `admin` deliberately lacks — only owners/super admins can delete users).
    Route::middleware('permission:users.view')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
        Route::put('/users/{id}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])
            ->middleware(['permission:users.delete', 'password.confirm'])->name('users.destroy');
    });

    // Platform administration (super admin only): tenants and plans.
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::get('/tenants/create', [TenantController::class, 'create'])->name('tenants.create');
        Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
        Route::get('/tenants/{tenant}/edit', [TenantController::class, 'edit'])->name('tenants.edit');
        Route::put('/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
        Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy'])->name('tenants.destroy');
        Route::post('/tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::post('/tenants/{tenant}/activate', [TenantController::class, 'activate'])->name('tenants.activate');

        // Impersonate a tenant (start). Stop is registered outside this group so it
        // remains reachable while acting as the impersonated (non-super-admin) user.
        Route::post('/impersonate/{tenant}', [ImpersonationController::class, 'start'])->name('impersonate.start');

        // Platform audit log + global settings.
        Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
        Route::get('/platform-settings', [PlatformSettingsController::class, 'edit'])->name('platform-settings.edit');
        Route::put('/platform-settings', [PlatformSettingsController::class, 'update'])->middleware('password.confirm')->name('platform-settings.update');

        Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
        Route::get('/plans/create', [PlanController::class, 'create'])->name('plans.create');
        Route::post('/plans', [PlanController::class, 'store'])->name('plans.store');
        Route::get('/plans/{plan}/edit', [PlanController::class, 'edit'])->name('plans.edit');
        Route::put('/plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
        Route::delete('/plans/{plan}', [PlanController::class, 'destroy'])->name('plans.destroy');

        // Approvals queue — super-admin reviews hotel/venue/package submissions.
        Route::get('/approvals', [App\Http\Controllers\Admin\ApprovalController::class, 'index'])->name('approvals.index');
        Route::post('/approvals/{type}/{id}/approve', [App\Http\Controllers\Admin\ApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('/approvals/{type}/{id}/reject', [App\Http\Controllers\Admin\ApprovalController::class, 'reject'])->name('approvals.reject');
    });

    // Settings, themes and plugins. Viewing requires settings.view; every mutation
    // requires settings.edit (which `admin` deliberately lacks — view-only for them).
    Route::middleware('permission:settings.view')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::get('/themes', [ThemeController::class, 'index'])->name('themes.index');
        Route::get('/plugins', [PluginController::class, 'index'])->name('plugins.index');

        Route::middleware('permission:settings.edit')->group(function () {
            Route::post('/themes/activate', [ThemeController::class, 'activate'])->name('themes.activate');
            Route::post('/plugins/enable', [PluginController::class, 'enable'])->name('plugins.enable');
            Route::post('/plugins/disable', [PluginController::class, 'disable'])->name('plugins.disable');
            Route::post('/settings/general', [SettingsController::class, 'updateGeneral'])->name('settings.general');
            Route::post('/settings/branding', [SettingsController::class, 'updateBranding'])->name('settings.branding');
            Route::post('/settings/email-templates', [SettingsController::class, 'updateEmailTemplates'])->name('settings.email-templates');
            Route::post('/settings/document-templates', [SettingsController::class, 'updateDocumentTemplates'])->name('settings.document-templates');
            Route::post('/settings/payhere', [SettingsController::class, 'updatePayHere'])->name('settings.payhere');
            Route::post('/settings', [SettingsController::class, 'updateSettings'])->name('settings.update');
        });
    });
});

// Client portal routes — restricted to the `client` role.
Route::middleware(['auth', EnforceSessionTimeout::class, SetCurrentTenant::class, 'tenant.active', 'role:client'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [PortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/bookings', [PortalController::class, 'bookings'])->name('bookings');
    Route::get('/bookings/{booking}', [PortalController::class, 'bookingShow'])->name('bookings.show');
    Route::get('/quotations', [PortalController::class, 'quotations'])->name('quotations');
    Route::get('/payments', [PortalController::class, 'payments'])->name('payments');
    Route::post('/payments/initiate', [App\Http\Controllers\Portal\PaymentController::class, 'initiate'])->name('payments.initiate');
    Route::get('/payments/return', [App\Http\Controllers\Portal\PaymentController::class, 'return'])->name('payments.return');
    Route::get('/payments/{payment}/cancel', [App\Http\Controllers\Portal\PaymentController::class, 'cancel'])->name('payments.cancel');
    Route::get('/payments/{payment}/receipt', [App\Http\Controllers\Portal\PaymentController::class, 'receipt'])->name('payments.receipt');
    Route::post('/notifications/read', [PortalController::class, 'markNotificationRead'])->name('notifications.read');

    // Active sessions / device management for clients.
    Route::get('/sessions', [SessionManagementController::class, 'index'])->name('sessions.index');
    Route::delete('/sessions/{id}', [SessionManagementController::class, 'destroy'])->middleware('password.confirm')->name('sessions.destroy');
    Route::delete('/sessions', [SessionManagementController::class, 'destroyOthers'])->middleware('password.confirm')->name('sessions.destroy-others');
});

// PayHere server-to-server webhook. Public, CSRF-exempt (see bootstrap/app.php),
// no auth and no tenant middleware — it resolves the tenant from the Payment.
Route::post('/payhere/notify', [PayHereWebhookController::class, 'notify'])->name('payhere.notify');

// Mark an in-app guided tour complete for the current user (any authenticated role).
Route::middleware('auth')->post('/tours/complete', [TourController::class, 'complete'])->name('tours.complete');

// Public demo sandbox — provisions a throwaway tenant. Gated by DEMO_MODE inside
// the controller; per-IP throttled to limit abuse.
Route::post('/demo/start', [DemoController::class, 'start'])
    ->middleware('throttle:' . config('eventpro.demo.throttle', 5) . ',60')
    ->name('demo.start');
