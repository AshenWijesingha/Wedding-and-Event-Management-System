<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Quotation extends Model
{
    use BelongsToTenant, HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['quotation_number', 'status', 'total_amount'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('quotation');
    }

    protected $fillable = [
        'tenant_id',
        'quotation_number',
        'inquiry_id',
        'booking_id',
        'client_id',
        'venue_id',
        'package_id',
        'event_date',
        'guest_count',
        'items',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'valid_until',
        'status',
        'notes',
        'terms_and_conditions',
        'prepared_by',
        'sent_at',
        'viewed_at',
        'accepted_at',
    ];

    protected $casts = [
        'event_date' => 'date',
        'valid_until' => 'date',
        'items' => 'array',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'guest_count' => 'integer',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    /**
     * Get the inquiry for this quotation.
     */
    public function inquiry()
    {
        return $this->belongsTo(Inquiry::class);
    }

    /**
     * Get the booking for this quotation.
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the client for this quotation.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the venue for this quotation.
     */
    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Get the package for this quotation.
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Get the user who prepared this quotation.
     */
    public function preparedBy()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    /**
     * Check if quotation is valid.
     */
    public function isValid(): bool
    {
        return $this->status !== 'expired' &&
               $this->valid_until &&
               $this->valid_until->gte(now()->startOfDay());
    }

    /**
     * Check if quotation can be accepted.
     */
    public function canBeAccepted(): bool
    {
        return $this->isValid() && in_array($this->status, ['sent', 'viewed']);
    }

    /**
     * Generate unique quotation number.
     */
    public static function generateQuotationNumber(string $prefix = 'QT'): string
    {
        return DB::transaction(function () use ($prefix) {
            $year = date('Y');
            $count = static::withoutGlobalScopes()->whereYear('created_at', $year)->lockForUpdate()->count() + 1;

            return sprintf('%s%s%04d', $prefix, $year, $count);
        });
    }

    /**
     * Scope query to valid quotations.
     */
    public function scopeValid($query)
    {
        return $query->where('valid_until', '>=', now()->toDateString())
            ->whereNotIn('status', ['expired', 'cancelled']);
    }
}
