<script setup>
import FormField from './FormField.vue';
defineProps({
    modelValue: String,
    label: String, error: String, hint: String,
    placeholder: String, required: Boolean, disabled: Boolean,
    rows: { type: [String, Number], default: 3 },
});
defineEmits(['update:modelValue']);
</script>

<template>
    <FormField :label="label" :error="error" :hint="hint" :required="required">
        <template #default="{ id, describedby, invalid }">
            <textarea :id="id" :value="modelValue" :rows="rows"
                      @input="$emit('update:modelValue', $event.target.value)"
                      :placeholder="placeholder" :required="required" :disabled="disabled"
                      :aria-describedby="describedby" :aria-invalid="invalid"
                      class="w-full rounded-lg border-border text-sm text-ink focus:border-primary focus:ring-primary disabled:bg-surface-sunken"></textarea>
        </template>
    </FormField>
</template>
