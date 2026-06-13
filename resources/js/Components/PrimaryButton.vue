<script setup>
// Legacy shim — prefer importing Button from '@/Components/ui'.
import Button from '@/Components/ui/Button.vue';
defineProps({ disabled: Boolean });
</script>
<template>
    <Button variant="primary" :disabled="disabled" v-bind="$attrs"><slot /></Button>
</template>
