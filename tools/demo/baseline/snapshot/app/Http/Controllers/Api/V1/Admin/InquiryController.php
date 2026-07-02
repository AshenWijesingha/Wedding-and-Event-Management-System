<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\InquiryResource;
use App\Http\Traits\ApiResponse;
use App\Models\Inquiry;
use App\Support\TenantRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $inquiries = Inquiry::with(['client', 'venue', 'package'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status)
            )
            ->when($request->event_type, fn ($q, $type) => $q->where('event_type', $type)
            )
            ->when($request->search, fn ($q, $search) => $q->whereHas('client', fn ($cq) => $cq->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            )
            )
            ->when($request->assigned_to, fn ($q, $userId) => $q->where('assigned_to', $userId)
            )
            ->orderBy($request->sort_by ?? 'created_at', $request->sort_dir ?? 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->success(InquiryResource::collection($inquiries));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', TenantRule::exists('clients')],
            'venue_id' => ['nullable', TenantRule::exists('venues')],
            'package_id' => ['nullable', TenantRule::exists('packages')],
            'event_type' => 'required|string|max:100',
            'preferred_date' => 'required|date|after_or_equal:today',
            'alternate_date' => 'nullable|date|after_or_equal:today',
            'guest_count' => 'required|integer|min:1',
            'budget_range_min' => 'nullable|numeric|min:0',
            'budget_range_max' => 'nullable|numeric|min:0|gte:budget_range_min',
            'message' => 'nullable|string',
            'source' => 'nullable|string|max:100',
            'assigned_to' => ['nullable', TenantRule::exists('users')],
            'notes' => 'nullable|string',
        ]);

        $validated['inquiry_number'] = Inquiry::generateInquiryNumber();
        $validated['status'] = 'pending';

        $inquiry = Inquiry::create($validated);

        return $this->created(new InquiryResource($inquiry->load(['client', 'venue', 'package'])));
    }

    public function show(Inquiry $inquiry): JsonResponse
    {
        return $this->success(new InquiryResource($inquiry->load(['client', 'venue', 'package'])));
    }

    public function update(Request $request, Inquiry $inquiry): JsonResponse
    {
        $validated = $request->validate([
            'venue_id' => ['nullable', TenantRule::exists('venues')],
            'package_id' => ['nullable', TenantRule::exists('packages')],
            'event_type' => 'sometimes|string|max:100',
            'preferred_date' => 'sometimes|date',
            'alternate_date' => 'nullable|date',
            'guest_count' => 'sometimes|integer|min:1',
            'budget_range_min' => 'nullable|numeric|min:0',
            'budget_range_max' => 'nullable|numeric|min:0',
            'message' => 'nullable|string',
            'status' => 'sometimes|in:pending,contacted,qualified,proposal_sent,converted,closed',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $inquiry->update($validated);

        return $this->success(new InquiryResource($inquiry->fresh(['client', 'venue', 'package'])));
    }

    public function destroy(Inquiry $inquiry): JsonResponse
    {
        $inquiry->delete();

        return $this->noContent();
    }
}
