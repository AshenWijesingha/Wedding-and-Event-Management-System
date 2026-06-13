<script setup>
defineProps({
    modelValue: String,
    // [{ id, label }]
    tabs: { type: Array, required: true },
});
defineEmits(['update:modelValue']);
</script>

<template>
    <div class="border-b border-border flex gap-6">
        <button v-for="tab in tabs" :key="tab.id" type="button"
                @click="$emit('update:modelValue', tab.id)"
                :class="[
                    'pb-3 text-sm font-medium border-b-2 -mb-px transition-colors',
                    modelValue === tab.id ? 'border-primary text-primary' : 'border-transparent text-ink-muted hover:text-ink'
                ]">
            {{ tab.label }}
        </button>
    </div>
</template>
