<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\InquiryResource;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InquiryController extends Controller
{
    public function index(Request $request): Response
    {
        $inquiries = Inquiry::with(['client', 'venue', 'package'])
            ->when($request->search, fn ($q, $search) =>
                $q->whereHas('client', fn ($cq) =>
                    $cq->where('first_name', 'like', "%{$search}%")
                       ->orWhere('last_name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%")
                )
            )
            ->when($request->status, fn ($q, $status) =>
                $q->where('status', $status)
            )
            ->when($request->event_type, fn ($q, $type) =>
                $q->where('event_type', $type)
            )
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Inquiries/Index', [
            'inquiries' => InquiryResource::collection($inquiries),
            'filters' => $request->only(['search', 'status', 'event_type']),
        ]);
    }

    public function show(Inquiry $inquiry): Response
    {
        $inquiry->load(['client', 'venue', 'package']);

        $users = User::select('id', 'name')->active()->get();

        return Inertia::render('Inquiries/Show', [
            'inquiry' => new InquiryResource($inquiry),
            'users' => $users,
        ]);
    }

    public function update(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:pending,contacted,qualified,proposal_sent,converted,closed',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $inquiry->update($validated);

        return back()->with('success', 'Inquiry updated.');
    }
}
