<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Inquiry extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'inquiry_number',
        'client_id',
        'venue_id',
        'package_id',
        'event_type',
        'preferred_date',
        'alternate_date',
        'guest_count',
        'budget_range_min',
        'budget_range_max',
        'message',
        'source',
        'status',
        'assigned_to',
        'notes',
    ];

    protected $casts = [
        'preferred_date' => 'date',
        'alternate_date' => 'date',
        'guest_count' => 'integer',
        'budget_range_min' => 'decimal:2',
        'budget_range_max' => 'decimal:2',
    ];

    /**
     * Get the client for this inquiry.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the venue for this inquiry.
     */
    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Get the package for this inquiry.
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Get the user this inquiry is assigned to.
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get quotations for this inquiry.
     */
    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    /**
     * Generate unique inquiry number.
     */
    public static function generateInquiryNumber(): string
    {
        $year = date('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('INQ%s%04d', $year, $count);
    }

    /**
     * Scope query to pending inquiries.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope query to open inquiries (not closed or converted).
     */
    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', ['closed', 'converted']);
    }
}
