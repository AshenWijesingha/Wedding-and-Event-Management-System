<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Inquiry;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'event_type' => 'nullable|string|max:100',
            'event_date' => 'nullable|date',
            'preferred_date' => 'nullable|date',
            'message' => 'required|string|max:2000',
            'venue_id' => 'nullable|exists:venues,id',
            'guest_count' => 'nullable|integer|min:1',
        ]);

        // Find or create client record from email
        [$firstName, $lastName] = array_pad(explode(' ', trim($validated['name']), 2), 2, '');
        $client = Client::firstOrCreate(
            ['email' => $validated['email']],
            [
                'first_name' => $firstName,
                'last_name' => $lastName ?: $firstName,
                'phone' => $validated['phone'] ?? null,
            ]
        );

        Inquiry::create([
            'inquiry_number' => Inquiry::generateInquiryNumber(),
            'client_id' => $client->id,
            'venue_id' => $validated['venue_id'] ?? null,
            'event_type' => $validated['event_type'] ?? 'general',
            'preferred_date' => $validated['preferred_date'] ?? $validated['event_date'] ?? null,
            'guest_count' => $validated['guest_count'] ?? null,
            'message' => $validated['message'],
            'source' => 'website',
            'status' => 'pending',
        ]);

        return back()->with('success', 'Thank you for your inquiry! We will get back to you soon.');
    }
}
