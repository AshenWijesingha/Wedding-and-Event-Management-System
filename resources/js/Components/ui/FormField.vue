<script setup>
const props = defineProps({ label: String, error: String, hint: String, required: Boolean, id: String });
const fieldId = props.id ?? `f-${Math.random().toString(36).slice(2, 9)}`;
</script>

<template>
    <div>
        <label v-if="label" :for="fieldId" class="block text-sm font-medium text-ink mb-1">
            {{ label }} <span v-if="required" class="text-danger">*</span>
        </label>
        <slot :id="fieldId" />
        <p v-if="error" class="text-xs text-danger mt-1">{{ error }}</p>
        <p v-else-if="hint" class="text-xs text-ink-subtle mt-1">{{ hint }}</p>
    </div>
</template>
