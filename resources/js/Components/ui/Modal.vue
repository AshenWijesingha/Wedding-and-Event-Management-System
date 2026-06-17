<script setup>
import { watch, ref, nextTick, onBeforeUnmount } from 'vue';

const props = defineProps({ show: Boolean, title: String, maxWidth: { type: String, default: 'md' } });
const emit = defineEmits(['close']);
const widths = { sm: 'max-w-sm', md: 'max-w-md', lg: 'max-w-lg', xl: 'max-w-2xl' };

const panel = ref(null);
let lastFocused = null;
const titleId = `modal-${Math.random().toString(36).slice(2, 9)}`;

const close = () => emit('close');

const onKeydown = (e) => {
    if (!props.show) return;
    if (e.key === 'Escape') {
        close();
        return;
    }
    if (e.key === 'Tab') {
        // Trap focus within the panel.
        const focusable = panel.value?.querySelectorAll(
            'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'
        );
        if (!focusable?.length) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    }
};

watch(() => props.show, async (v) => {
    document.body.style.overflow = v ? 'hidden' : '';
    if (v) {
        lastFocused = document.activeElement;
        document.addEventListener('keydown', onKeydown);
        await nextTick();
        // Focus the first focusable element, else the panel itself.
        const focusable = panel.value?.querySelector(
            'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'
        );
        (focusable ?? panel.value)?.focus();
    } else {
        document.removeEventListener('keydown', onKeydown);
        lastFocused?.focus?.();
    }
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-ink/40" @click="close" />
                <div ref="panel" role="dialog" aria-modal="true" :aria-labelledby="title || $slots.header ? titleId : undefined"
                     tabindex="-1"
                     :class="['relative bg-surface rounded-lg shadow-xl w-full focus:outline-none', widths[maxWidth] ?? widths.md]">
                    <div v-if="title || $slots.header" class="px-5 py-4 border-b border-border flex items-center justify-between">
                        <h3 :id="titleId" class="font-display text-lg font-semibold text-ink"><slot name="header">{{ title }}</slot></h3>
                        <button type="button" aria-label="Close dialog" class="text-ink-subtle hover:text-ink rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-primary" @click="close">
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
