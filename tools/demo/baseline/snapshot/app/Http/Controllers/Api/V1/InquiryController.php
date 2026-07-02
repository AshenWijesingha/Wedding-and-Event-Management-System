<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Inquiry;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    /**
     * Store a newly created public inquiry.
     *
     * Public submissions are normalized into a Client + Inquiry under the current
     * tenant (resolved by domain in production, falling back to the single active
     * tenant for single-tenant deployments).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'event_type' => 'nullable|string|max:100',
            'event_date' => 'nullable|date',
            'message' => 'required|string|max:2000',
            'venue_id' => 'nullable|exists:venues,id',
            'guest_count' => 'nullable|integer|min:1',
        ]);

        $tenant = Tenant::current()
            ?? Tenant::where('status', '!=', 'suspended')->first();

        abort_if($tenant === null, 422, 'No tenant is available to receive this inquiry.');

        $tenant->makeCurrent();

        [$firstName, $lastName] = array_pad(explode(' ', trim($validated['name']), 2), 2, '');

        $client = Client::firstOrCreate(
            ['tenant_id' => $tenant->id, 'email' => $validated['email']],
            [
                'first_name' => $firstName,
                'last_name' => $lastName ?: $firstName,
                'phone' => $validated['phone'] ?? null,
            ]
        );

        $inquiry = Inquiry::create([
            'tenant_id' => $tenant->id,
            'inquiry_number' => Inquiry::generateInquiryNumber(),
            'client_id' => $client->id,
            'venue_id' => $validated['venue_id'] ?? null,
            'event_type' => $validated['event_type'] ?? 'general',
            'preferred_date' => $validated['event_date'] ?? null,
            'guest_count' => $validated['guest_count'] ?? null,
            'message' => $validated['message'],
            'source' => 'website',
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Inquiry submitted successfully',
            'inquiry' => $inquiry->load('client'),
        ], 201);
    }
}
