<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Support\Str;

class Vendor extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'category',
        'contact_name',
        'email',
        'phone',
        'website',
        'description',
        'base_rate',
        'rate_type',
        'services',
        'status',
        'notes',
    ];

    protected $casts = [
        'base_rate' => 'decimal:2',
        'services'  => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Vendor $vendor) {
            if (empty($vendor->slug)) {
                $base = Str::slug($vendor->name);
                $slug = $base;
                $i = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $vendor->slug = $slug;
            }
        });
    }

    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'booking_vendor')
            ->withPivot(['service_description', 'agreed_amount', 'status', 'notes'])
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
