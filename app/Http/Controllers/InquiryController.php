<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InquiryController extends Controller
{
    /**
     * Store a newly created inquiry.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'event_type' => 'nullable|string|max:100',
            'event_date' => 'nullable|date',
            'message' => 'required|string|max:2000',
        ]);

        // TODO: Store inquiry in database
        // Inquiry::create($validated);

        return back()->with('success', 'Thank you for your inquiry! We will get back to you soon.');
    }
}
