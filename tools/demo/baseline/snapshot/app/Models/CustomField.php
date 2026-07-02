<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'entity_type',
        'name',
        'slug',
        'type',
        'options',
        'validation_rules',
        'default_value',
        'placeholder',
        'help_text',
        'is_required',
        'is_active',
        'is_searchable',
        'show_on_list',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'validation_rules' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'is_searchable' => 'boolean',
        'show_on_list' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get all values for this field.
     */
    public function values()
    {
        return $this->hasMany(CustomFieldValue::class);
    }

    /**
     * Scope query to active fields.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query for specific entity type.
     */
    public function scopeForEntity($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Scope query to searchable fields.
     */
    public function scopeSearchable($query)
    {
        return $query->where('is_searchable', true);
    }

    /**
     * Get validation rules for this field.
     */
    public function getValidationRulesAttribute(): array
    {
        $rules = [];

        if ($this->is_required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        switch ($this->type) {
            case 'text':
                $rules[] = 'string';
                $rules[] = 'max:255';
                break;
            case 'textarea':
                $rules[] = 'string';
                $rules[] = 'max:65535';
                break;
            case 'number':
                $rules[] = 'numeric';
                break;
            case 'date':
                $rules[] = 'date';
                break;
            case 'email':
                $rules[] = 'email';
                break;
            case 'select':
            case 'radio':
                $options = collect($this->options)->pluck('value')->toArray();
                $rules[] = 'in:' . implode(',', $options);
                break;
            case 'multiselect':
            case 'checkbox':
                $rules[] = 'array';
                break;
        }

        return $rules;
    }
}
