<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomFieldValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'custom_field_id',
        'entity_type',
        'entity_id',
        'value',
    ];

    /**
     * Get the custom field definition.
     */
    public function customField()
    {
        return $this->belongsTo(CustomField::class);
    }

    /**
     * Get the entity that owns this value.
     */
    public function entity()
    {
        return $this->morphTo('entity', 'entity_type', 'entity_id');
    }

    /**
     * Get the typed value based on field type.
     */
    public function getTypedValueAttribute()
    {
        $field = $this->customField;

        if (! $field) {
            return $this->value;
        }

        switch ($field->type) {
            case 'number':
                return (float) $this->value;
            case 'boolean':
                return (bool) $this->value;
            case 'date':
                return $this->value ? Carbon::parse($this->value) : null;
            case 'multiselect':
            case 'checkbox':
                return json_decode($this->value, true) ?? [];
            default:
                return $this->value;
        }
    }

    /**
     * Set value with proper encoding based on field type.
     */
    public function setValueAttribute($value): void
    {
        if (is_array($value)) {
            $this->attributes['value'] = json_encode($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }
}
