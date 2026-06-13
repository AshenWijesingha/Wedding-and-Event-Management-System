<script setup>
defineProps({ modelValue: Boolean, label: String, disabled: Boolean });
defineEmits(['update:modelValue']);
</script>

<template>
    <label class="inline-flex items-center gap-2 cursor-pointer" :class="{ 'opacity-50 cursor-not-allowed': disabled }">
        <button type="button" role="switch" :aria-checked="modelValue" :disabled="disabled"
                @click="$emit('update:modelValue', !modelValue)"
                :class="['relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2', modelValue ? 'bg-primary' : 'bg-surface-sunken border border-border']">
            <span :class="['inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform', modelValue ? 'translate-x-6' : 'translate-x-1']" />
        </button>
        <span v-if="label" class="text-sm text-ink">{{ label }}</span>
    </label>
</template>
