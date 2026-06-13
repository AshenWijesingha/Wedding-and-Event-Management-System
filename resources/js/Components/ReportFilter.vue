<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    path: { type: String, required: true },
    filters: { type: Object, required: true },
    years: { type: Array, required: true },
});

const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');
const year = ref(props.filters.year);

function applyRange() {
    if (!from.value || !to.value) return;
    router.get(props.path, { from: from.value, to: to.value }, { preserveState: true, replace: true });
}

function applyYear() {
    from.value = '';
    to.value = '';
    router.get(props.path, { year: year.value }, { preserveState: true, replace: true });
}

function clearRange() {
    from.value = '';
    to.value = '';
    router.get(props.path, { year: year.value }, { preserveState: true, replace: true });
}
</script>

<template>
    <div class="flex flex-wrap items-end gap-2">
        <div>
            <label class="block text-xs text-ink-subtle mb-1">From</label>
            <input type="date" v-model="from" class="border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
        </div>
        <div>
            <label class="block text-xs text-ink-subtle mb-1">To</label>
            <input type="date" v-model="to" class="border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
        </div>
        <button type="button" @click="applyRange"
            class="px-3 py-2 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary-dark">Apply</button>
        <button v-if="filters.from" type="button" @click="clearRange"
            class="px-3 py-2 text-sm text-ink-subtle hover:text-ink-muted">Clear</button>
        <div class="ml-1">
            <label class="block text-xs text-ink-subtle mb-1">Year</label>
            <select v-model="year" @change="applyYear"
                class="border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
        </div>
    </div>
</template>
