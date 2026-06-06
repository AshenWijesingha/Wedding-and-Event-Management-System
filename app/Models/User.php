<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Concerns\BelongsToTenant;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, BelongsToTenant, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'role',
        'is_active',
        'preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'preferences' => 'array',
    ];

    /**
     * Get the client profile if user is a client.
     */
    public function client()
    {
        return $this->hasOne(Client::class);
    }

    /**
     * Get bookings created by this user.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'created_by');
    }

    /**
     * Get inquiries assigned to this user.
     */
    public function assignedInquiries()
    {
        return $this->hasMany(Inquiry::class, 'assigned_to');
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['super_admin', 'admin']);
    }

    public function isManager(): bool
    {
        return $this->hasAnyRole(['super_admin', 'admin', 'manager']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
