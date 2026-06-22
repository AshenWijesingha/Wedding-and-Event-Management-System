<?php

namespace App\Services;

use App\Models\Tenant;
use Carbon\Carbon;

class SettingsService
{
    private array $defaults;

    private ?Tenant $tenant;

    public function __construct(?Tenant $tenant = null)
    {
        $this->tenant = $tenant ?? Tenant::current();
        $this->defaults = config('eventpro.defaults', []);
    }

    /**
     * Get a setting value with fallback to defaults.
     */
    public function get(string $key, $default = null)
    {
        // Check tenant settings first
        if ($this->tenant) {
            $tenantValue = $this->tenant->getSetting($key);
            if ($tenantValue !== null) {
                return $tenantValue;
            }
        }

        // Fall back to application defaults
        return data_get($this->defaults, $key, $default);
    }

    /**
     * Set a setting value.
     */
    public function set(string $key, $value): void
    {
        if ($this->tenant) {
            $this->tenant->setSetting($key, $value);
        }
    }

    /**
     * Get all settings for a category.
     */
    public function getCategory(string $category): array
    {
        $defaults = data_get($this->defaults, $category, []);

        if ($this->tenant) {
            $tenantSettings = $this->tenant->getSetting($category, []);

            return array_merge($defaults, $tenantSettings);
        }

        return $defaults;
    }

    /**
     * Get the settings schema for a category.
     */
    public function getSchema(?string $category = null): array
    {
        $schema = config('eventpro.settings-schema', []);

        if ($category) {
            return $schema[$category] ?? [];
        }

        return $schema;
    }

    /**
     * Format currency value.
     */
    public function formatCurrency(float $amount): string
    {
        $symbol = $this->get('currency.symbol', '$');
        $position = $this->get('currency.position', 'before');
        $decimals = $this->get('currency.decimal_places', 2);

        $formatted = number_format($amount, $decimals);

        return $position === 'before'
            ? "{$symbol}{$formatted}"
            : "{$formatted}{$symbol}";
    }

    /**
     * Format date value.
     */
    public function formatDate($date): string
    {
        if (! $date) {
            return '';
        }

        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        $format = $this->get('general.date_format', 'd M Y');

        return $date->format($format);
    }

    /**
     * Format time value.
     */
    public function formatTime($time): string
    {
        if (! $time) {
            return '';
        }

        if (is_string($time)) {
            $time = Carbon::parse($time);
        }

        $format = $this->get('general.time_format', 'h:i A');

        return $time->format($format);
    }

    /**
     * Get enabled event types.
     */
    public function getEventTypes(): array
    {
        $enabledTypes = $this->get('event_types.enabled_types', []);
        $customTypes = $this->get('event_types.custom_types', []);

        return array_merge($enabledTypes, $customTypes);
    }

    /**
     * Get accepted payment methods.
     */
    public function getPaymentMethods(): array
    {
        return $this->get('payment.accepted_methods', ['bank_transfer', 'credit_card', 'cash']);
    }

    /**
     * Get installment schedule.
     */
    public function getInstallmentSchedule(): array
    {
        return $this->get('payment.installment_schedule', []);
    }
}
