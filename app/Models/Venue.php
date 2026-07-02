<?php

namespace App\Models;

use App\Models\Concerns\Approvable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    use Approvable, BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'hotel_id',
        'name',
        'slug',
        'description',
        'capacity_min',
        'capacity_max',
        'base_price',
        'weekend_surcharge',
        'amenities',
        'images',
        'status',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'weekend_surcharge' => 'decimal:2',
        'amenities' => 'array',
        'images' => 'array',
        'capacity_min' => 'integer',
        'capacity_max' => 'integer',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'changes_pending_review' => 'boolean',
    ];

    /**
     * Get the hotel this venue belongs to.
     */
    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Get the bookings for this venue.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get the packages available at this venue.
     */
    public function packages()
    {
        return $this->belongsToMany(Package::class, 'venue_packages');
    }

    /**
     * Check if venue is available on a specific date.
     */
    public function isAvailableOn(string $date): bool
    {
        return ! $this->bookings()
            ->where('event_date', $date)
            ->whereNotIn('status', ['cancelled'])
            ->exists();
    }

    /**
     * Scope query to active venues only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
