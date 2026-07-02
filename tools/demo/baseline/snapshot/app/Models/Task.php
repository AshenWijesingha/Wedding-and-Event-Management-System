<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'booking_id',
        'assigned_to',
        'title',
        'description',
        'priority',
        'status',
        'due_date',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function assignee()
    {
        return $this->belongsTo(Staff::class, 'assigned_to');
    }

    public function markCompleted(): void
    {
        $this->update(['status' => 'completed', 'completed_at' => now()]);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'completed')
            ->where('due_date', '<', now()->toDateString());
    }
}
