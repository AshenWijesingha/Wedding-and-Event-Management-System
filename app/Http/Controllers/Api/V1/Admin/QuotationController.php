<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuotationResource;
use App\Http\Traits\ApiResponse;
use App\Models\Quotation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $quotations = Quotation::with(['client', 'venue'])
            ->when($request->status, fn ($q, $status) =>
                $q->where('status', $status)
            )
            ->when($request->client_id, fn ($q, $clientId) =>
                $q->where('client_id', $clientId)
            )
            ->when($request->search, fn ($q, $search) =>
                $q->where('quotation_number', 'like', "%{$search}%")
                  ->orWhereHas('client', fn ($cq) =>
                      $cq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                  )
            )
            ->orderBy($request->sort_by ?? 'created_at', $request->sort_dir ?? 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->success(QuotationResource::collection($quotations));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'venue_id' => 'required|exists:venues,id',
            'inquiry_id' => 'nullable|exists:inquiries,id',
            'event_date' => 'required|date',
            'guest_count' => 'required|integer|min:1',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'valid_until' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
        ]);

        $validated['quotation_number'] = Quotation::generateQuotationNumber();
        $validated['status'] = 'draft';
        $validated['discount_amount'] = $validated['discount_amount'] ?? 0;
        $validated['tax_amount'] = $validated['tax_amount'] ?? 0;
        $validated['prepared_by'] = $request->user()?->id;

        $quotation = Quotation::create($validated);

        return $this->created(new QuotationResource($quotation->load(['client', 'venue'])));
    }

    public function show(Quotation $quotation): JsonResponse
    {
        return $this->success(new QuotationResource($quotation->load(['client', 'venue'])));
    }

    public function update(Request $request, Quotation $quotation): JsonResponse
    {
        $validated = $request->validate([
            'venue_id' => 'sometimes|exists:venues,id',
            'event_date' => 'sometimes|date',
            'guest_count' => 'sometimes|integer|min:1',
            'items' => 'sometimes|array|min:1',
            'items.*.name' => 'required_with:items|string',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
            'subtotal' => 'sometimes|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'sometimes|numeric|min:0',
            'valid_until' => 'sometimes|date',
            'notes' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
            'status' => 'sometimes|in:draft,sent,viewed,accepted,declined,expired,cancelled',
        ]);

        $quotation->update($validated);

        return $this->success(new QuotationResource($quotation->fresh(['client', 'venue'])));
    }

    public function destroy(Quotation $quotation): JsonResponse
    {
        if (in_array($quotation->status, ['accepted'])) {
            return $this->error('Cannot delete an accepted quotation.', 422);
        }

        $quotation->delete();

        return $this->noContent();
    }

    public function send(Quotation $quotation): JsonResponse
    {
        if (!in_array($quotation->status, ['draft', 'sent'])) {
            return $this->error('Only draft quotations can be sent.', 422);
        }

        $quotation->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return $this->success(new QuotationResource($quotation->fresh()));
    }

    public function pdf(Quotation $quotation): JsonResponse
    {
        return $this->error('PDF generation not yet implemented.', 501);
    }
}
