<script setup>
defineProps({ value: String, required: Boolean });
</script>
<template>
    <label class="block text-sm font-medium text-ink-muted">
        <slot>{{ value }}</slot>
        <span v-if="required" class="text-red-500 ml-1">*</span>
    </label>
</template>
