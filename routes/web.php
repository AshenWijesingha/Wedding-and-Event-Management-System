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

Route::get('/venues', function () {
    return view('venues.index');
})->name('venues.index');

Route::get('/venues/{venue:slug}', function (\App\Models\Venue $venue) {
    return view('venues.show', compact('venue'));
})->name('venues.show');

Route::get('/packages', function () {
    return view('packages.index');
})->name('packages.index');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

Route::post('/inquiry', [\App\Http\Controllers\InquiryController::class, 'store'])->name('inquiry.store');

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

// Admin panel routes (Inertia.js SPA)
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => inertia('Dashboard'))->name('dashboard');

    // Venues — full resource with web controller
    Route::resource('venues', \App\Http\Controllers\Admin\VenueController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::get('venues/{venue}/availability', [\App\Http\Controllers\Admin\VenueController::class, 'availability'])->name('admin.venues.availability');

    // Placeholder routes for pages not yet built
    Route::get('/packages', fn () => inertia('Packages/Index'))->name('packages.index');
    Route::get('/packages/create', fn () => inertia('Packages/Create'))->name('packages.create');
    Route::get('/bookings', fn () => inertia('Bookings/Index'))->name('bookings.index');
    Route::get('/bookings/create', fn () => inertia('Bookings/Create'))->name('bookings.create');
    Route::get('/bookings/{id}', fn ($id) => inertia('Bookings/Show', ['bookingId' => $id]))->name('bookings.show');
    Route::get('/inquiries', fn () => inertia('Inquiries/Index'))->name('inquiries.index');
    Route::get('/quotations', fn () => inertia('Quotations/Index'))->name('quotations.index');
    Route::get('/clients', fn () => inertia('Clients/Index'))->name('clients.index');
    Route::get('/payments', fn () => inertia('Payments/Index'))->name('payments.index');
    Route::get('/reports', fn () => inertia('Reports/Index'))->name('reports.index');
    Route::get('/settings', fn () => inertia('Settings/Index'))->name('settings.index');
});

// Client portal routes
Route::middleware(['auth'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', function () {
        return inertia('Portal/Dashboard');
    })->name('dashboard');
    
    Route::get('/bookings', function () {
        return inertia('Portal/Bookings');
    })->name('bookings');
    
    Route::get('/quotations', function () {
        return inertia('Portal/Quotations');
    })->name('quotations');
    
    Route::get('/payments', function () {
        return inertia('Portal/Payments');
    })->name('payments');
});
