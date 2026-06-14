<script setup>
import FormField from './FormField.vue';
defineProps({
    modelValue: [String, Number],
    label: String, error: String, hint: String,
    type: { type: String, default: 'text' },
    placeholder: String, required: Boolean, disabled: Boolean,
    step: [String, Number], min: [String, Number], max: [String, Number],
});
defineEmits(['update:modelValue']);
</script>

<template>
    <FormField :label="label" :error="error" :hint="hint" :required="required">
        <template #default="{ id, describedby, invalid }">
            <input :id="id" :type="type" :value="modelValue"
                   @input="$emit('update:modelValue', $event.target.value)"
                   :placeholder="placeholder" :required="required" :disabled="disabled"
                   :step="step" :min="min" :max="max"
                   :aria-describedby="describedby" :aria-invalid="invalid"
                   class="w-full rounded-lg border-border text-sm text-ink focus:border-primary focus:ring-primary disabled:bg-surface-sunken disabled:text-ink-subtle" />
        </template>
    </FormField>
</template>
