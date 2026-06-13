<script setup>
defineProps({ title: String, subtitle: String, eyebrow: String });
</script>

<template>
    <div class="flex flex-wrap items-end justify-between gap-3 mb-6">
        <div>
            <p v-if="eyebrow" class="text-xs font-semibold uppercase tracking-wider text-ink-subtle mb-1">{{ eyebrow }}</p>
            <h2 class="font-display text-2xl font-semibold text-ink leading-tight">{{ title }}</h2>
            <p v-if="subtitle" class="text-sm text-ink-muted mt-1">{{ subtitle }}</p>
        </div>
        <div v-if="$slots.actions" class="flex items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
