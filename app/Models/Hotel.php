<?php

namespace App\Models;

use App\Models\Concerns\Approvable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    use Approvable, BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id', 'name', 'slug', 'city', 'address',
        'description', 'star_rating', 'images', 'status',
    ];

    protected $casts = [
        'images' => 'array',
        'star_rating' => 'integer',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'changes_pending_review' => 'boolean',
    ];

    public function venues()
    {
        return $this->hasMany(Venue::class);
    }

    public function packages()
    {
        return $this->hasMany(Package::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
