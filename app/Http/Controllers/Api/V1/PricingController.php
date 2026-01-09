<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function __construct(
        protected PricingService $pricingService
    ) {}

    /**
     * Calculate pricing for a booking.
     */
    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'venue_id' => 'required|exists:venues,id',
            'package_id' => 'nullable|exists:packages,id',
            'event_date' => 'required|date',
            'guest_count' => 'required|integer|min:1',
            'addons' => 'nullable|array',
        ]);

        // TODO: Implement pricing calculation
        $price = [
            'base_price' => 0,
            'addons_total' => 0,
            'tax' => 0,
            'total' => 0,
        ];

        return response()->json($price);
    }
}
