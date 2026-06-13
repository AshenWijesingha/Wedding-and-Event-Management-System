<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    variant: { type: String, default: 'primary' }, // primary | secondary | ghost | danger | link
    size: { type: String, default: 'md' },          // sm | md
    href: { type: String, default: null },
    type: { type: String, default: 'button' },
    loading: Boolean,
    disabled: Boolean,
});

const variants = {
    primary:   'bg-primary text-white border border-transparent shadow-sm hover:bg-primary-dark focus:ring-primary',
    secondary: 'bg-surface text-ink-muted border border-border shadow-sm hover:bg-surface-sunken focus:ring-primary',
    ghost:     'bg-transparent text-ink-muted border border-transparent hover:bg-surface-sunken focus:ring-primary',
    danger:    'bg-danger text-white border border-transparent shadow-sm hover:opacity-90 focus:ring-danger',
    link:      'bg-transparent text-primary border-0 shadow-none hover:underline focus:ring-0',
};
const sizes = { sm: 'px-3 py-1.5 text-xs', md: 'px-4 py-2 text-sm' };

const classes = computed(() => [
    'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed',
    variants[props.variant] ?? variants.primary,
    props.variant === 'link' ? '' : (sizes[props.size] ?? sizes.md),
]);

const isDisabled = computed(() => props.disabled || props.loading);
</script>

<template>
    <Link v-if="href && !isDisabled" :href="href" :class="classes">
        <slot />
    </Link>
    <button v-else :type="type" :disabled="isDisabled" :class="classes">
        <svg v-if="loading" class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
        </svg>
        <slot />
    </button>
</template>
