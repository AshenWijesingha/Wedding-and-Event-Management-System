<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PricingService $pricingService
    ) {}

    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'venue_id' => 'required|exists:venues,id',
            'package_id' => 'nullable|exists:packages,id',
            'event_date' => 'required|date|after_or_equal:today',
            'guest_count' => 'required|integer|min:1',
            'services' => 'nullable|array',
            'services.*.name' => 'required_with:services|string',
            'services.*.quantity' => 'required_with:services|integer|min:1',
            'services.*.unit_price' => 'required_with:services|numeric|min:0',
            'services.*.pricing_type' => 'required_with:services|in:flat,per_guest',
            'discount' => 'nullable|array',
            'discount.type' => 'required_with:discount|in:percentage,fixed',
            'discount.value' => 'required_with:discount|numeric|min:0',
        ]);

        $breakdown = $this->pricingService->calculateEventPrice($validated);

        $deposit = $this->pricingService->calculateDeposit($breakdown['total']);
        $schedule = $this->pricingService->generatePaymentSchedule($breakdown['total'], $validated['event_date']);

        return $this->success([
            'breakdown' => $breakdown,
            'deposit' => $deposit,
            'payment_schedule' => $schedule,
        ]);
    }
}
