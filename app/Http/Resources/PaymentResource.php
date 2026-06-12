<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_number' => $this->payment_number,
            'installment_name' => $this->installment_name,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'payment_method' => $this->payment_method,
            'gateway' => $this->gateway,
            'gateway_payment_id' => $this->gateway_payment_id,
            'gateway_status_code' => $this->gateway_status_code,
            'payment_date' => $this->payment_date?->toDateString(),
            'reference_number' => $this->reference_number,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
