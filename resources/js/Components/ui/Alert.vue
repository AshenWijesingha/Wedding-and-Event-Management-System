<script setup>
defineProps({ variant: { type: String, default: 'info' } });
const styles = {
    success: 'bg-success-soft text-success border-success/20',
    error:   'bg-danger-soft text-danger border-danger/20',
    warning: 'bg-warning-soft text-warning border-warning/20',
    info:    'bg-info-soft text-info border-info/20',
};
</script>

<template>
    <div :class="['rounded-lg px-4 py-3 text-sm border', styles[variant] ?? styles.info]">
        <slot />
    </div>
</template>
