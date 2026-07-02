<?php

use App\Http\Controllers\Api\V1\Admin\ClientController;
use App\Http\Controllers\Api\V1\Admin\CustomFieldController;
use App\Http\Controllers\Api\V1\Admin\PaymentController;
use App\Http\Controllers\Api\V1\Admin\ReportController;
use App\Http\Controllers\Api\V1\Admin\SettingsController;
use App\Http\Controllers\Api\V1\Admin\StaffController;
use App\Http\Controllers\Api\V1\Admin\TaskController;
use App\Http\Controllers\Api\V1\Admin\VendorController;
use App\Http\Controllers\Api\V1\Client\BookingController;
use App\Http\Controllers\Api\V1\Client\QuotationController;
use App\Http\Controllers\Api\V1\InquiryController;
use App\Http\Controllers\Api\V1\PackageController;
use App\Http\Controllers\Api\V1\PricingController;
use App\Http\Controllers\Api\V1\VenueController;
use App\Http\Middleware\SetCurrentTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public API Routes (v1)
Route::prefix('v1')->name('api.v1.')->middleware('throttle:api')->group(function () {

    // Public endpoints - No authentication required
    Route::get('venues', [VenueController::class, 'index'])->name('venues.index');
    Route::get('venues/{venue}', [VenueController::class, 'show'])->name('venues.show');
    Route::get('venues/{venue}/availability', [VenueController::class, 'availability'])->name('venues.availability');

    Route::get('packages', [PackageController::class, 'index'])->name('packages.index');
    Route::get('packages/{package}', [PackageController::class, 'show'])->name('packages.show');

    Route::middleware('throttle:5,1')->post('inquiries', [InquiryController::class, 'store'])->name('inquiries.store');
    Route::post('calculate-price', [PricingController::class, 'calculate'])->name('pricing.calculate');

    // Authenticated routes
    Route::middleware(['auth:sanctum', SetCurrentTenant::class])->group(function () {

        // User routes
        Route::get('user', function (Request $request) {
            return $request->user();
        })->name('user');

        // Client portal routes
        Route::prefix('client')->name('client.')->group(function () {
            Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
            Route::get('bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
            Route::get('quotations', [QuotationController::class, 'index'])->name('quotations.index');
            Route::get('quotations/{quotation}', [QuotationController::class, 'show'])->name('quotations.show');
            Route::post('quotations/{quotation}/accept', [QuotationController::class, 'accept'])->name('quotations.accept');
        });

        // Admin routes
        Route::prefix('admin')->name('admin.')->middleware('role:admin|manager')->group(function () {

            // Venues management
            Route::apiResource('venues', App\Http\Controllers\Api\V1\Admin\VenueController::class);

            // Packages management
            Route::apiResource('packages', App\Http\Controllers\Api\V1\Admin\PackageController::class);

            // Clients management
            Route::apiResource('clients', ClientController::class);

            // Inquiries management
            Route::apiResource('inquiries', App\Http\Controllers\Api\V1\Admin\InquiryController::class);

            // Bookings management
            Route::apiResource('bookings', App\Http\Controllers\Api\V1\Admin\BookingController::class);

            // Quotations management
            Route::apiResource('quotations', App\Http\Controllers\Api\V1\Admin\QuotationController::class);
            Route::post('quotations/{quotation}/send', [App\Http\Controllers\Api\V1\Admin\QuotationController::class, 'send'])->name('quotations.send');
            Route::get('quotations/{quotation}/pdf', [App\Http\Controllers\Api\V1\Admin\QuotationController::class, 'pdf'])->name('quotations.pdf');

            // Payments management
            Route::apiResource('payments', PaymentController::class);

            // Vendors management
            Route::apiResource('vendors', VendorController::class);

            // Staff management
            Route::apiResource('staff', StaffController::class);
            Route::get('staff/{staff}/schedule', [StaffController::class, 'schedule'])->name('staff.schedule');

            // Task management
            Route::apiResource('tasks', TaskController::class);

            // Reports
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('revenue', [ReportController::class, 'revenue'])->name('revenue');
                Route::get('bookings', [ReportController::class, 'bookings'])->name('bookings');
                Route::get('inquiries', [ReportController::class, 'inquiries'])->name('inquiries');
            });

            // Settings
            Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

            // Custom fields
            Route::apiResource('custom-fields', CustomFieldController::class);
        });
    });
});
