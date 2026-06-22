<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'status', 'plan_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('tenant');
    }

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant) {
            if (empty($tenant->uuid)) {
                $tenant->uuid = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'database',
        'plan_id',
        'logo',
        'favicon',
        'primary_color',
        'email',
        'phone',
        'settings',
        'status',
        'is_demo',
        'demo_expires_at',
        'trial_ends_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'trial_ends_at' => 'datetime',
        'is_demo' => 'boolean',
        'demo_expires_at' => 'datetime',
    ];

    /**
     * Get a specific setting value.
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set a specific setting value.
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
        $this->save();
    }

    /**
     * Check if tenant has a specific feature based on their plan.
     */
    public function hasFeature(string $feature): bool
    {
        $plan = $this->plan;
        if (! $plan) {
            return false;
        }

        return $plan->hasFeature($feature);
    }

    /**
     * Get the plan associated with the tenant.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get all venues for this tenant.
     */
    public function venues()
    {
        return $this->hasMany(Venue::class);
    }

    /**
     * Get all users for this tenant.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all bookings for this tenant.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Check if tenant is on trial.
     */
    public function isOnTrial(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if tenant is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' || $this->isOnTrial();
    }
}
