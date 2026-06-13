<script setup>
defineProps({ bodyClass: { type: String, default: 'p-5' } });
</script>

<template>
    <div class="bg-surface border border-border rounded-lg shadow-sm">
        <div v-if="$slots.header" class="px-5 py-4 border-b border-border">
            <slot name="header" />
        </div>
        <div :class="bodyClass">
            <slot />
        </div>
        <div v-if="$slots.footer" class="px-5 py-4 border-t border-border bg-surface-muted rounded-b-lg">
            <slot name="footer" />
        </div>
    </div>
</template>
