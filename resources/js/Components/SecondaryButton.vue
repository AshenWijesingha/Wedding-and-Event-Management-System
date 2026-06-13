<script setup>
// Legacy shim — prefer importing Button from '@/Components/ui'.
import Button from '@/Components/ui/Button.vue';
defineProps({ disabled: Boolean });
</script>
<template>
    <Button variant="secondary" :disabled="disabled" v-bind="$attrs"><slot /></Button>
</template>
