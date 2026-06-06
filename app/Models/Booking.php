<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Booking extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'booking_number',
        'client_id',
        'venue_id',
        'package_id',
        'event_type',
        'event_date',
        'setup_time',
        'event_start_time',
        'event_end_time',
        'guest_count',
        'total_amount',
        'paid_amount',
        'balance_amount',
        'status',
        'notes',
        'custom_data',
        'created_by',
    ];

    protected $casts = [
        'event_date' => 'date',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'guest_count' => 'integer',
        'custom_data' => 'array',
    ];

    /**
     * Get the client for this booking.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the venue for this booking.
     */
    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Get the package for this booking.
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Get the payments for this booking.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the vendors for this booking.
     */
    public function vendors()
    {
        return $this->belongsToMany(Vendor::class, 'booking_vendor')
            ->withPivot(['service_description', 'agreed_amount', 'status', 'notes'])
            ->withTimestamps();
    }

    /**
     * Get the quotation for this booking.
     */
    public function quotation()
    {
        return $this->hasOne(Quotation::class);
    }

    /**
     * Calculate balance amount.
     */
    public function updateBalance(): void
    {
        $calculated = (float) $this->total_amount - (float) $this->paid_amount;
        if ((float) $this->balance_amount !== $calculated) {
            $this->balance_amount = $calculated;
            $this->save();
        }
    }

    /**
     * Check if booking is confirmed.
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if booking is pending.
     */
    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'tentative']);
    }

    /**
     * Check if booking can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return !in_array($this->status, ['completed', 'cancelled']);
    }

    /**
     * Generate unique booking number.
     */
    public static function generateBookingNumber(): string
    {
        $year = date('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('BK%s%04d', $year, $count);
    }

    /**
     * Scope query by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope query for upcoming events.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>=', now()->toDateString())
            ->orderBy('event_date');
    }
}
