<script setup>
import { watch } from 'vue';
const props = defineProps({ show: Boolean, title: String, maxWidth: { type: String, default: 'md' } });
const emit = defineEmits(['close']);
const widths = { sm: 'max-w-sm', md: 'max-w-md', lg: 'max-w-lg', xl: 'max-w-2xl' };

watch(() => props.show, (v) => {
    document.body.style.overflow = v ? 'hidden' : '';
});
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-ink/40" @click="emit('close')" />
                <div :class="['relative bg-surface rounded-lg shadow-xl w-full', widths[maxWidth] ?? widths.md]">
                    <div v-if="title || $slots.header" class="px-5 py-4 border-b border-border flex items-center justify-between">
                        <h3 class="font-display text-lg font-semibold text-ink"><slot name="header">{{ title }}</slot></h3>
                        <button class="text-ink-subtle hover:text-ink" @click="emit('close')">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <div class="p-5"><slot /></div>
                    <div v-if="$slots.footer" class="px-5 py-4 border-t border-border bg-surface-muted rounded-b-lg flex justify-end gap-2">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
