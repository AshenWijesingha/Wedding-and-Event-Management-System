<script setup>
import Card from './Card.vue';
defineProps({ label: String, value: [String, Number], hint: String, accent: { type: String, default: 'ink' } });
const accents = {
    ink: 'text-ink',
    primary: 'text-primary',
    success: 'text-success',
    warning: 'text-warning',
    danger: 'text-danger',
};
</script>

<template>
    <Card body-class="p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-ink-subtle">{{ label }}</p>
        <p :class="['mt-1 text-2xl font-bold', accents[accent] ?? accents.ink]"><slot>{{ value }}</slot></p>
        <p v-if="hint" class="text-xs text-ink-muted mt-1">{{ hint }}</p>
    </Card>
</template>
