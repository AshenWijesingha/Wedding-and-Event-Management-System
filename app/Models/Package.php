<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Package extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'base_price',
        'min_guests',
        'max_guests',
        'guest_pricing',
        'included_services',
        'status',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'min_guests' => 'integer',
        'max_guests' => 'integer',
        'guest_pricing' => 'array',
        'included_services' => 'array',
    ];

    /**
     * Get the venues this package is available at.
     */
    public function venues()
    {
        return $this->belongsToMany(Venue::class, 'venue_packages');
    }

    /**
     * Get bookings that use this package.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Scope query to active packages only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Calculate price for given guest count.
     */
    public function calculatePrice(int $guestCount): float
    {
        $price = (float) $this->base_price;

        if ($this->guest_pricing) {
            foreach ($this->guest_pricing as $tier) {
                if ($guestCount >= $tier['from'] && $guestCount <= $tier['to']) {
                    $price += ($guestCount - $this->min_guests) * $tier['price_per_guest'];
                    break;
                }
            }
        }

        return $price;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
