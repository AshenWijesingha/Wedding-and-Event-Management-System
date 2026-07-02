<?php

namespace App\Services;

use App\Models\CustomField;
use App\Models\CustomFieldValue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CustomFieldService
{
    /**
     * Get all custom fields for an entity type.
     */
    public function getFieldsFor(string $entityType): Collection
    {
        return CustomField::where('entity_type', $entityType)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get custom field values for an entity.
     */
    public function getValues(Model $entity): array
    {
        $values = CustomFieldValue::where('entity_type', get_class($entity))
            ->where('entity_id', $entity->id)
            ->get()
            ->keyBy('custom_field_id');

        $fields = $this->getFieldsFor($entity->getTable());
        $result = [];

        foreach ($fields as $field) {
            $value = $values->get($field->id);
            $result[$field->slug] = [
                'field' => $field,
                'value' => $value ? $value->typed_value : $field->default_value,
                'raw_value' => $value ? $value->value : null,
            ];
        }

        return $result;
    }

    /**
     * Save custom field values for an entity.
     */
    public function saveValues(Model $entity, array $values): void
    {
        foreach ($values as $fieldSlug => $value) {
            $field = CustomField::where('slug', $fieldSlug)->first();
            if (! $field) {
                continue;
            }

            CustomFieldValue::updateOrCreate(
                [
                    'custom_field_id' => $field->id,
                    'entity_type' => get_class($entity),
                    'entity_id' => $entity->id,
                ],
                ['value' => $value]
            );
        }
    }

    /**
     * Delete all custom field values for an entity.
     */
    public function deleteValues(Model $entity): void
    {
        CustomFieldValue::where('entity_type', get_class($entity))
            ->where('entity_id', $entity->id)
            ->delete();
    }

    /**
     * Get validation rules for custom fields.
     */
    public function getValidationRules(string $entityType): array
    {
        $fields = $this->getFieldsFor($entityType);
        $rules = [];

        foreach ($fields as $field) {
            $rules["custom_fields.{$field->slug}"] = $field->validation_rules;
        }

        return $rules;
    }

    /**
     * Create a new custom field.
     */
    public function createField(array $data): CustomField
    {
        return CustomField::create($data);
    }

    /**
     * Update a custom field.
     */
    public function updateField(CustomField $field, array $data): CustomField
    {
        $field->update($data);

        return $field;
    }

    /**
     * Delete a custom field and all its values.
     */
    public function deleteField(CustomField $field): bool
    {
        // Delete all values first
        $field->values()->delete();

        return $field->delete();
    }

    /**
     * Reorder custom fields.
     */
    public function reorderFields(array $fieldIds): void
    {
        foreach ($fieldIds as $order => $id) {
            CustomField::where('id', $id)->update(['sort_order' => $order]);
        }
    }
}
