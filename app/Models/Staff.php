<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToTenant;

class Staff extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'staff';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'role',
        'status',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
