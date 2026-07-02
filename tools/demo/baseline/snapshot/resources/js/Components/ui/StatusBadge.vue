<script setup>
import { statusClass } from '@/utils/status';
defineProps({ status: String, label: String });
</script>

<template>
    <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', statusClass(status)]">
        <slot>{{ label ?? status?.replace('_', ' ') }}</slot>
    </span>
</template>
