<script setup>
import { onMounted, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';
import { tours } from '@/tours/registry';

const props = defineProps({ tourKey: { type: String, required: true } });

const page = usePage();
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

// Keep only steps that have no anchor or whose anchor is currently in the DOM,
// so incomplete anchoring never produces a broken/empty spotlight.
function resolvedSteps() {
    const def = tours[props.tourKey];
    if (! def) return [];
    return def.steps.filter((s) => ! s.element || document.querySelector(s.element));
}

function markComplete() {
    fetch('/tours/complete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ key: props.tourKey }),
        keepalive: true,
    }).catch(() => {});
}

function run({ persist }) {
    const steps = resolvedSteps();
    if (! steps.length) return;

    const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    let finished = false;

    const d = driver({
        showProgress: steps.length > 1,
        popoverClass: 'driverjs-theme',
        animate: ! reduce,
        overlayColor: 'rgba(31,29,26,0.55)',
        nextBtnText: 'Next',
        prevBtnText: 'Back',
        doneBtnText: 'Done',
        steps,
        onDestroyed: () => {
            if (persist && ! finished) {
                finished = true;
                markComplete();
            }
        },
    });
    d.drive();
}

onMounted(async () => {
    await nextTick();
    const completed = page.props.completedTours ?? [];
    if (! completed.includes(props.tourKey)) {
        run({ persist: true });
    }
});

const replay = () => run({ persist: false });
</script>

<template>
    <button type="button" @click="replay" title="Tour this page"
        class="fixed bottom-5 right-5 z-30 w-11 h-11 rounded-full bg-surface border border-border shadow-lg text-ink-muted hover:text-primary hover:border-primary transition flex items-center justify-center">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </button>
</template>
