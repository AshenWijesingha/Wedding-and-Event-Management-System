<script setup>
defineProps({ title: { type: String, default: 'Nothing here yet' }, description: String });
</script>

<template>
    <div class="text-center py-14 px-6">
        <div class="mx-auto w-11 h-11 rounded-full bg-surface-sunken flex items-center justify-center text-ink-subtle mb-3">
            <slot name="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
            </slot>
        </div>
        <p class="text-sm font-medium text-ink">{{ title }}</p>
        <p v-if="description" class="text-sm text-ink-muted mt-1">{{ description }}</p>
        <div v-if="$slots.action" class="mt-4 flex justify-center"><slot name="action" /></div>
    </div>
</template>
