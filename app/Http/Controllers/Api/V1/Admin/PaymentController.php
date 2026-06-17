<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Http\Traits\ApiResponse;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $payments = Payment::query()
            ->when($request->booking_id, fn ($q, $bookingId) =>
                $q->where('booking_id', $bookingId)
            )
            ->when($request->client_id, fn ($q, $clientId) =>
                $q->where('client_id', $clientId)
            )
            ->when($request->status, fn ($q, $status) =>
                $q->where('status', $status)
            )
            ->when($request->method, fn ($q, $method) =>
                $q->where('payment_method', $method)
            )
            ->when($request->date_from, fn ($q, $date) =>
                $q->where('payment_date', '>=', $date)
            )
            ->when($request->date_to, fn ($q, $date) =>
                $q->where('payment_date', '<=', $date)
            )
            ->orderBy($request->sort_by ?? 'payment_date', $request->sort_dir ?? 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->success(PaymentResource::collection($payments));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => ['required', \App\Support\TenantRule::exists('bookings')],
            'installment_name' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,credit_card,cheque,online',
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $validated['payment_number'] = Payment::generatePaymentNumber();
        $validated['status'] = 'completed';
        $validated['client_id'] = Booking::findOrFail($validated['booking_id'])->client_id;
        $validated['received_by'] = $request->user()->id;

        $payment = DB::transaction(function () use ($validated) {
            $payment = Payment::create($validated);
            $booking = $payment->booking;
            $booking->paid_amount = $booking->payments()->where('status', 'completed')->sum('amount');
            $booking->balance_amount = $booking->total_amount - $booking->paid_amount;
            $booking->save();
            return $payment;
        });

        return $this->created(new PaymentResource($payment));
    }

    public function show(Payment $payment): JsonResponse
    {
        return $this->success(new PaymentResource($payment));
    }

    public function update(Request $request, Payment $payment): JsonResponse
    {
        if ($payment->status === 'completed') {
            return $this->error('Completed payments cannot be edited. Use refund instead.', 422);
        }

        $validated = $request->validate([
            'installment_name' => 'sometimes|string|max:100',
            'amount' => 'sometimes|numeric|min:0.01',
            'payment_method' => 'sometimes|in:cash,bank_transfer,credit_card,cheque,online',
            'payment_date' => 'sometimes|date',
            'reference_number' => 'nullable|string|max:100',
            'status' => 'sometimes|in:pending,completed,failed,refunded',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($payment, $validated) {
            $payment->update($validated);

            $booking = $payment->booking;
            $booking->paid_amount = $booking->payments()->where('status', 'completed')->sum('amount');
            $booking->balance_amount = $booking->total_amount - $booking->paid_amount;
            $booking->save();
        });

        return $this->success(new PaymentResource($payment->fresh()));
    }

    public function destroy(Payment $payment): JsonResponse
    {
        if ($payment->status === 'completed') {
            return $this->error('Completed payments cannot be deleted. Use refund instead.', 422);
        }

        $payment->delete();

        return $this->noContent();
    }
}
