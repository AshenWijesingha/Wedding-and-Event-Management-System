<script setup>
// Legacy shim — prefer importing Button from '@/Components/ui'.
// Defaults to type="submit" (matching Breeze's PrimaryButton) so it submits the
// surrounding form; ui/Button defaults to "button". Pass :type="'button'" for a
// non-submit primary action.
import Button from '@/Components/ui/Button.vue';
defineProps({
    disabled: Boolean,
    type: { type: String, default: 'submit' },
});
</script>
<template>
    <Button variant="primary" :type="type" :disabled="disabled" v-bind="$attrs"><slot /></Button>
</template>
