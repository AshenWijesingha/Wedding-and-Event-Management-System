<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'payment_number',
        'booking_id',
        'client_id',
        'installment_name',
        'amount',
        'currency',
        'payment_method',
        'gateway',
        'gateway_payment_id',
        'gateway_status_code',
        'payment_date',
        'reference_number',
        'status',
        'notes',
        'gateway_response',
        'received_by',
        'due_date',
        'reminder_sent_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'due_date' => 'date',
        'reminder_sent_at' => 'datetime',
        'gateway_status_code' => 'integer',
        'gateway_response' => 'array',
    ];

    /**
     * Get the booking for this payment.
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the client for this payment.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user who received the payment.
     */
    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Generate unique payment number.
     */
    public static function generatePaymentNumber(): string
    {
        $year = date('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;

        return sprintf('PAY%s%04d', $year, $count);
    }

    /**
     * Scope query to completed payments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope query by payment method.
     */
    public function scopeByMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }
}
