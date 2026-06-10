<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

// Public website routes
Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/venues', [\App\Http\Controllers\VenueController::class, 'index'])->name('venues.index');
Route::get('/venues/{venue:slug}', [\App\Http\Controllers\VenueController::class, 'show'])->name('venues.show');

Route::get('/packages', [\App\Http\Controllers\PackageController::class, 'index'])->name('packages.index');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

Route::post('/inquiry', [\App\Http\Controllers\InquiryController::class, 'store'])->name('inquiry.store')->middleware('throttle:inquiry');

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

    // Email verification (signed link delivered by the verification notification)
    Route::get('verify-email/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
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
    \App\Http\Middleware\SetCurrentTenant::class,
    'tenant.active',
    'role:super_admin|tenant_owner|admin|manager|staff',
])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // Exit impersonation — reachable while acting as the impersonated user.
    Route::post('/impersonate-stop', [\App\Http\Controllers\Admin\ImpersonationController::class, 'stop'])->name('impersonate.stop');

    // Venues — full resource with web controller
    Route::middleware('permission:venues.view')->group(function () {
        Route::resource('venues', \App\Http\Controllers\Admin\VenueController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::get('venues/{venue}/availability', [\App\Http\Controllers\Admin\VenueController::class, 'availability'])->name('admin.venues.availability');
    });

    // Packages
    Route::middleware('permission:packages.view')->group(function () {
        Route::resource('packages', \App\Http\Controllers\Admin\PackageController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    });

    // Bookings
    Route::middleware('permission:bookings.view')->group(function () {
        Route::get('/bookings', [\App\Http\Controllers\Admin\BookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/create', [\App\Http\Controllers\Admin\BookingController::class, 'create'])->middleware('permission:bookings.create')->name('bookings.create');
        Route::post('/bookings', [\App\Http\Controllers\Admin\BookingController::class, 'store'])->middleware('permission:bookings.create')->name('bookings.store');
        Route::get('/bookings/{booking}', [\App\Http\Controllers\Admin\BookingController::class, 'show'])->name('bookings.show');
        Route::post('/bookings/{booking}/confirm', [\App\Http\Controllers\Admin\BookingController::class, 'confirm'])->middleware('permission:bookings.confirm')->name('bookings.confirm');
        Route::post('/bookings/{booking}/cancel', [\App\Http\Controllers\Admin\BookingController::class, 'cancel'])->middleware('permission:bookings.cancel')->name('bookings.cancel');
        Route::post('/bookings/{booking}/vendors', [\App\Http\Controllers\Admin\BookingController::class, 'attachVendor'])->middleware('permission:bookings.edit')->name('bookings.vendors.attach');
        Route::delete('/bookings/{booking}/vendors/{vendor}', [\App\Http\Controllers\Admin\BookingController::class, 'detachVendor'])->middleware('permission:bookings.edit')->name('bookings.vendors.detach');
    });

    // Vendors
    Route::middleware('permission:vendors.view')->group(function () {
        Route::resource('vendors', \App\Http\Controllers\Admin\VendorController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    });

    // Staff (employee records)
    Route::middleware('permission:staff.view')->group(function () {
        Route::resource('staff', \App\Http\Controllers\Admin\StaffController::class)
            ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    });

    // Tasks
    Route::middleware('permission:tasks.view')->group(function () {
        Route::get('/tasks', [\App\Http\Controllers\Admin\TaskController::class, 'index'])->name('tasks.index');
        Route::post('/tasks', [\App\Http\Controllers\Admin\TaskController::class, 'store'])->middleware('permission:tasks.create')->name('tasks.store');
        Route::patch('/tasks/{task}', [\App\Http\Controllers\Admin\TaskController::class, 'update'])->middleware('permission:tasks.edit')->name('tasks.update');
        Route::delete('/tasks/{task}', [\App\Http\Controllers\Admin\TaskController::class, 'destroy'])->middleware('permission:tasks.delete')->name('tasks.destroy');
    });

    // Inquiries
    Route::middleware('permission:inquiries.view')->group(function () {
        Route::get('/inquiries', [\App\Http\Controllers\Admin\InquiryController::class, 'index'])->name('inquiries.index');
        Route::get('/inquiries/{inquiry}', [\App\Http\Controllers\Admin\InquiryController::class, 'show'])->name('inquiries.show');
        Route::get('/inquiries/{inquiry}/pdf', [\App\Http\Controllers\Admin\InquiryController::class, 'downloadPdf'])->name('inquiries.pdf');
        Route::get('/inquiries/{inquiry}/print', [\App\Http\Controllers\Admin\InquiryController::class, 'print'])->name('inquiries.print');
        Route::patch('/inquiries/{inquiry}', [\App\Http\Controllers\Admin\InquiryController::class, 'update'])->middleware('permission:inquiries.edit')->name('inquiries.update');
    });

    // Quotations
    Route::middleware('permission:quotations.view')->group(function () {
        Route::get('/quotations', [\App\Http\Controllers\Admin\QuotationController::class, 'index'])->name('quotations.index');
        Route::get('/quotations/create', [\App\Http\Controllers\Admin\QuotationController::class, 'create'])->middleware('permission:quotations.create')->name('quotations.create');
        Route::post('/quotations', [\App\Http\Controllers\Admin\QuotationController::class, 'store'])->middleware('permission:quotations.create')->name('quotations.store');
        Route::get('/quotations/{quotation}', [\App\Http\Controllers\Admin\QuotationController::class, 'show'])->name('quotations.show');
        Route::get('/quotations/{quotation}/pdf', [\App\Http\Controllers\Admin\QuotationController::class, 'downloadPdf'])->name('quotations.pdf');
        Route::get('/quotations/{quotation}/print', [\App\Http\Controllers\Admin\QuotationController::class, 'print'])->name('quotations.print');
        Route::post('/quotations/{quotation}/send', [\App\Http\Controllers\Admin\QuotationController::class, 'send'])->middleware('permission:quotations.send')->name('quotations.send');
        Route::post('/quotations/{quotation}/accept', [\App\Http\Controllers\Admin\QuotationController::class, 'accept'])->middleware('permission:quotations.edit')->name('quotations.accept');
        Route::post('/quotations/{quotation}/reject', [\App\Http\Controllers\Admin\QuotationController::class, 'reject'])->middleware('permission:quotations.edit')->name('quotations.reject');
        Route::post('/quotations/{quotation}/expire', [\App\Http\Controllers\Admin\QuotationController::class, 'markExpired'])->middleware('permission:quotations.edit')->name('quotations.expire');
    });

    Route::middleware('permission:clients.view')->group(function () {
        Route::get('/clients', [\App\Http\Controllers\Admin\ClientController::class, 'index'])->name('clients.index');
    });

    Route::middleware('permission:payments.view')->group(function () {
        Route::get('/payments', [\App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('payments.index');
    });

    // Custom Fields
    Route::middleware('permission:custom_fields.view')->group(function () {
        Route::get('/custom-fields', [\App\Http\Controllers\Admin\CustomFieldController::class, 'index'])->name('custom-fields.index');
        Route::post('/custom-fields', [\App\Http\Controllers\Admin\CustomFieldController::class, 'store'])->middleware('permission:custom_fields.create')->name('custom-fields.store');
        Route::patch('/custom-fields/{customField}', [\App\Http\Controllers\Admin\CustomFieldController::class, 'update'])->middleware('permission:custom_fields.edit')->name('custom-fields.update');
        Route::delete('/custom-fields/{customField}', [\App\Http\Controllers\Admin\CustomFieldController::class, 'destroy'])->middleware('permission:custom_fields.delete')->name('custom-fields.destroy');
    });

    // Reports — viewing gated by reports.view; CSV/PDF exports require reports.export.
    Route::middleware('permission:reports.view')->group(function () {
        Route::get('/reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/revenue', [\App\Http\Controllers\Admin\ReportController::class, 'revenue'])->name('reports.revenue');
        Route::get('/reports/bookings', [\App\Http\Controllers\Admin\ReportController::class, 'bookings'])->name('reports.bookings');
        Route::get('/reports/occupancy', [\App\Http\Controllers\Admin\ReportController::class, 'occupancy'])->name('reports.occupancy');

        Route::middleware('permission:reports.export')->group(function () {
            Route::get('/reports/revenue/export', [\App\Http\Controllers\Admin\ReportController::class, 'exportRevenue'])->name('reports.revenue.export');
            Route::get('/reports/revenue/pdf', [\App\Http\Controllers\Admin\ReportController::class, 'pdfRevenue'])->name('reports.revenue.pdf');
            Route::get('/reports/bookings/export', [\App\Http\Controllers\Admin\ReportController::class, 'exportBookings'])->name('reports.bookings.export');
            Route::get('/reports/bookings/pdf', [\App\Http\Controllers\Admin\ReportController::class, 'pdfBookings'])->name('reports.bookings.pdf');
            Route::get('/reports/occupancy/export', [\App\Http\Controllers\Admin\ReportController::class, 'exportOccupancy'])->name('reports.occupancy.export');
            Route::get('/reports/occupancy/pdf', [\App\Http\Controllers\Admin\ReportController::class, 'pdfOccupancy'])->name('reports.occupancy.pdf');
        });
    });

    // Profile (self-service account management) — available to every admin-area user.
    Route::get('/profile', [\App\Http\Controllers\Admin\ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [\App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [\App\Http\Controllers\Admin\ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/verification-notification', [\App\Http\Controllers\Admin\ProfileController::class, 'sendVerification'])->name('profile.verification');

    // User management. Viewing/managing requires users.view; deletion requires users.delete
    // (which `admin` deliberately lacks — only owners/super admins can delete users).
    Route::middleware('permission:users.view')->group(function () {
        Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [\App\Http\Controllers\Admin\UserController::class, 'create'])->name('users.create');
        Route::post('/users', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('users.store');
        Route::get('/users/{id}/edit', [\App\Http\Controllers\Admin\UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{id}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('users.update');
        Route::put('/users/{id}/reset-password', [\App\Http\Controllers\Admin\UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('/users/{id}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])
            ->middleware('permission:users.delete')->name('users.destroy');
    });

    // Platform administration (super admin only): tenants and plans.
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/tenants', [\App\Http\Controllers\Admin\TenantController::class, 'index'])->name('tenants.index');
        Route::get('/tenants/create', [\App\Http\Controllers\Admin\TenantController::class, 'create'])->name('tenants.create');
        Route::post('/tenants', [\App\Http\Controllers\Admin\TenantController::class, 'store'])->name('tenants.store');
        Route::get('/tenants/{tenant}/edit', [\App\Http\Controllers\Admin\TenantController::class, 'edit'])->name('tenants.edit');
        Route::put('/tenants/{tenant}', [\App\Http\Controllers\Admin\TenantController::class, 'update'])->name('tenants.update');
        Route::delete('/tenants/{tenant}', [\App\Http\Controllers\Admin\TenantController::class, 'destroy'])->name('tenants.destroy');
        Route::post('/tenants/{tenant}/suspend', [\App\Http\Controllers\Admin\TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::post('/tenants/{tenant}/activate', [\App\Http\Controllers\Admin\TenantController::class, 'activate'])->name('tenants.activate');

        // Impersonate a tenant (start). Stop is registered outside this group so it
        // remains reachable while acting as the impersonated (non-super-admin) user.
        Route::post('/impersonate/{tenant}', [\App\Http\Controllers\Admin\ImpersonationController::class, 'start'])->name('impersonate.start');

        // Platform audit log + global settings.
        Route::get('/audit-log', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-log.index');
        Route::get('/platform-settings', [\App\Http\Controllers\Admin\PlatformSettingsController::class, 'edit'])->name('platform-settings.edit');
        Route::put('/platform-settings', [\App\Http\Controllers\Admin\PlatformSettingsController::class, 'update'])->name('platform-settings.update');

        Route::get('/plans', [\App\Http\Controllers\Admin\PlanController::class, 'index'])->name('plans.index');
        Route::get('/plans/create', [\App\Http\Controllers\Admin\PlanController::class, 'create'])->name('plans.create');
        Route::post('/plans', [\App\Http\Controllers\Admin\PlanController::class, 'store'])->name('plans.store');
        Route::get('/plans/{plan}/edit', [\App\Http\Controllers\Admin\PlanController::class, 'edit'])->name('plans.edit');
        Route::put('/plans/{plan}', [\App\Http\Controllers\Admin\PlanController::class, 'update'])->name('plans.update');
        Route::delete('/plans/{plan}', [\App\Http\Controllers\Admin\PlanController::class, 'destroy'])->name('plans.destroy');
    });

    // Settings, themes and plugins. Viewing requires settings.view; every mutation
    // requires settings.edit (which `admin` deliberately lacks — view-only for them).
    Route::middleware('permission:settings.view')->group(function () {
        Route::get('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
        Route::get('/themes', [\App\Http\Controllers\Admin\ThemeController::class, 'index'])->name('themes.index');
        Route::get('/plugins', [\App\Http\Controllers\Admin\PluginController::class, 'index'])->name('plugins.index');

        Route::middleware('permission:settings.edit')->group(function () {
            Route::post('/themes/activate', [\App\Http\Controllers\Admin\ThemeController::class, 'activate'])->name('themes.activate');
            Route::post('/plugins/enable', [\App\Http\Controllers\Admin\PluginController::class, 'enable'])->name('plugins.enable');
            Route::post('/plugins/disable', [\App\Http\Controllers\Admin\PluginController::class, 'disable'])->name('plugins.disable');
            Route::post('/settings/general', [\App\Http\Controllers\Admin\SettingsController::class, 'updateGeneral'])->name('settings.general');
            Route::post('/settings/branding', [\App\Http\Controllers\Admin\SettingsController::class, 'updateBranding'])->name('settings.branding');
            Route::post('/settings/email-templates', [\App\Http\Controllers\Admin\SettingsController::class, 'updateEmailTemplates'])->name('settings.email-templates');
            Route::post('/settings/document-templates', [\App\Http\Controllers\Admin\SettingsController::class, 'updateDocumentTemplates'])->name('settings.document-templates');
            Route::post('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'updateSettings'])->name('settings.update');
        });
    });
});

// Client portal routes — restricted to the `client` role.
Route::middleware(['auth', \App\Http\Middleware\SetCurrentTenant::class, 'tenant.active', 'role:client'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Portal\PortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/bookings', [\App\Http\Controllers\Portal\PortalController::class, 'bookings'])->name('bookings');
    Route::get('/bookings/{booking}', [\App\Http\Controllers\Portal\PortalController::class, 'bookingShow'])->name('bookings.show');
    Route::get('/quotations', [\App\Http\Controllers\Portal\PortalController::class, 'quotations'])->name('quotations');
    Route::get('/payments', [\App\Http\Controllers\Portal\PortalController::class, 'payments'])->name('payments');
    Route::post('/notifications/read', [\App\Http\Controllers\Portal\PortalController::class, 'markNotificationRead'])->name('notifications.read');
});
