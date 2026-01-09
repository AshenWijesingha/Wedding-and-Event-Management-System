<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Services\QuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class QuotationController extends Controller
{
    public function __construct(
        protected QuotationService $quotationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $quotations = Quotation::with(['client', 'inquiry'])
            ->paginate($request->per_page ?? 15);
        return response()->json($quotations);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'inquiry_id' => 'nullable|exists:inquiries,id',
            'venue_id' => 'required|exists:venues,id',
            'event_date' => 'required|date',
            'items' => 'required|array',
            'valid_until' => 'required|date',
        ]);

        $quotation = Quotation::create($validated);
        return response()->json($quotation, 201);
    }

    public function show(Quotation $quotation): JsonResponse
    {
        return response()->json($quotation->load(['client', 'venue']));
    }

    public function update(Request $request, Quotation $quotation): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'sometimes|array',
            'valid_until' => 'sometimes|date',
            'status' => 'sometimes|string|in:draft,sent,accepted,rejected,expired',
        ]);

        $quotation->update($validated);
        return response()->json($quotation);
    }

    public function destroy(Quotation $quotation): JsonResponse
    {
        $quotation->delete();
        return response()->json(null, 204);
    }

    public function send(Quotation $quotation): JsonResponse
    {
        // TODO: Implement email sending
        $quotation->update(['status' => 'sent', 'sent_at' => now()]);
        return response()->json(['message' => 'Quotation sent successfully']);
    }

    public function pdf(Quotation $quotation)
    {
        $pdf = Pdf::loadView('quotations.pdf', ['quotation' => $quotation]);
        return $pdf->download("quotation-{$quotation->id}.pdf");
    }
}
