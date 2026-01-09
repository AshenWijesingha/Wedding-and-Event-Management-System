<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

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

// Authentication routes (using Laravel Breeze or Fortify)
// These will be added when the auth package is installed
// Placeholder login route for development
Route::get('/login', function () {
    return redirect('/')->with('error', 'Authentication is not yet configured.');
})->name('login');

// Admin panel routes (Inertia.js SPA)
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return inertia('Dashboard');
    })->name('dashboard');
    
    Route::get('/venues', function () {
        return inertia('Venues/Index');
    })->name('venues.index');
    
    Route::get('/packages', function () {
        return inertia('Packages/Index');
    })->name('packages.index');
    
    Route::get('/bookings', function () {
        return inertia('Bookings/Index');
    })->name('bookings.index');
    
    Route::get('/bookings/{booking}', function ($booking) {
        return inertia('Bookings/Show', ['booking' => $booking]);
    })->name('bookings.show');
    
    Route::get('/inquiries', function () {
        return inertia('Inquiries/Index');
    })->name('inquiries.index');
    
    Route::get('/quotations', function () {
        return inertia('Quotations/Index');
    })->name('quotations.index');
    
    Route::get('/clients', function () {
        return inertia('Clients/Index');
    })->name('clients.index');
    
    Route::get('/payments', function () {
        return inertia('Payments/Index');
    })->name('payments.index');
    
    Route::get('/reports', function () {
        return inertia('Reports/Index');
    })->name('reports.index');
    
    Route::get('/settings', function () {
        return inertia('Settings/Index');
    })->name('settings.index');
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
