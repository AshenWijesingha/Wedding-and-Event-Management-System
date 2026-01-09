<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    /**
     * Display a listing of the client's quotations.
     */
    public function index(Request $request): JsonResponse
    {
        $quotations = Quotation::query()
            ->where('client_id', $request->user()->client_id ?? $request->user()->id)
            ->paginate($request->per_page ?? 15);

        return response()->json($quotations);
    }

    /**
     * Display the specified quotation.
     */
    public function show(Quotation $quotation): JsonResponse
    {
        // TODO: Add authorization check
        return response()->json($quotation);
    }

    /**
     * Accept a quotation.
     */
    public function accept(Quotation $quotation): JsonResponse
    {
        // TODO: Implement quotation acceptance logic
        return response()->json([
            'message' => 'Quotation accepted successfully',
            'quotation' => $quotation,
        ]);
    }
}
