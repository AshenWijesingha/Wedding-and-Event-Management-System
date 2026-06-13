<script setup>
import FormField from './FormField.vue';
defineProps({
    modelValue: [String, Number],
    label: String, error: String, hint: String,
    required: Boolean, disabled: Boolean,
});
defineEmits(['update:modelValue']);
</script>

<template>
    <FormField :label="label" :error="error" :hint="hint" :required="required">
        <template #default="{ id }">
            <select :id="id" :value="modelValue"
                    @change="$emit('update:modelValue', $event.target.value)"
                    :required="required" :disabled="disabled"
                    class="w-full rounded-lg border-border text-sm text-ink focus:border-primary focus:ring-primary disabled:bg-surface-sunken">
                <slot />
            </select>
        </template>
    </FormField>
</template>
