<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Services\QuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    public function __construct(private QuotationService $quotationService) {}

    private function resolveClientId(Request $request): int
    {
        return $request->user()->client?->id ?? $request->user()->id;
    }

    /**
     * Display a listing of the client's quotations.
     */
    public function index(Request $request): JsonResponse
    {
        $clientId = $this->resolveClientId($request);

        $quotations = Quotation::query()
            ->where('client_id', $clientId)
            ->paginate($request->per_page ?? 15);

        return response()->json($quotations);
    }

    /**
     * Display the specified quotation.
     */
    public function show(Request $request, Quotation $quotation): JsonResponse
    {
        if ($quotation->client_id !== $this->resolveClientId($request)) {
            abort(403, 'You are not authorized to view this quotation.');
        }

        $this->quotationService->markAsViewed($quotation);

        return response()->json($quotation->fresh());
    }

    /**
     * Accept a quotation.
     */
    public function accept(Request $request, Quotation $quotation): JsonResponse
    {
        if ($quotation->client_id !== $this->resolveClientId($request)) {
            abort(403, 'You are not authorized to accept this quotation.');
        }

        if (!$quotation->canBeAccepted()) {
            return response()->json([
                'message' => 'This quotation cannot be accepted. It may have expired or already been actioned.',
            ], 422);
        }

        $quotation = $this->quotationService->accept($quotation);

        return response()->json([
            'message' => 'Quotation accepted successfully.',
            'quotation' => $quotation->fresh(),
        ]);
    }
}
