<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ activities: Object, subjectTypes: Array, filters: Object });

const subjectType = ref(props.filters?.subject_type ?? '');
const event = ref(props.filters?.event ?? '');

let timer;
watch([subjectType, event], () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get('/admin/audit-log',
            { subject_type: subjectType.value, event: event.value },
            { preserveState: true, replace: true });
    }, 250);
});

const eventColors = {
    created: 'bg-green-100 text-green-700',
    updated: 'bg-blue-100 text-blue-700',
    deleted: 'bg-red-100 text-red-700',
};

const summarize = (changes) => {
    const attrs = changes?.attributes ?? {};
    const keys = Object.keys(attrs);
    if (!keys.length) return '—';
    return keys.map((k) => `${k}: ${attrs[k]}`).join(', ');
};
</script>

<template>
    <AppLayout title="Audit Log">
        <div class="space-y-5">
            <div>
                <h2 class="text-xl font-semibold text-ink">Audit Log</h2>
                <p class="text-sm text-ink-subtle mt-0.5">Platform-wide record of changes across all tenants.</p>
            </div>

            <div class="bg-surface rounded-lg shadow-sm p-4 flex flex-wrap gap-3">
                <select v-model="subjectType" class="border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All types</option>
                    <option v-for="st in subjectTypes" :key="st.value" :value="st.value">{{ st.label }}</option>
                </select>
                <select v-model="event" class="border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All events</option>
                    <option value="created">Created</option>
                    <option value="updated">Updated</option>
                    <option value="deleted">Deleted</option>
                </select>
            </div>

            <div class="bg-surface rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-surface-muted">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">When</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Causer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Event</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Changes</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface divide-y divide-border">
                        <tr v-for="a in activities.data" :key="a.id" class="hover:bg-surface-muted">
                            <td class="px-6 py-4 text-sm text-ink-muted whitespace-nowrap">{{ a.created_at }}</td>
                            <td class="px-6 py-4 text-sm text-ink">{{ a.causer }}</td>
                            <td class="px-6 py-4">
                                <span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', eventColors[a.event] ?? 'bg-surface-sunken text-ink-muted']">
                                    {{ a.event ?? a.description }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-ink-muted">{{ a.subject_type }}<span class="text-ink-subtle"> #{{ a.subject_id }}</span></td>
                            <td class="px-6 py-4 text-xs text-ink-subtle max-w-md truncate">{{ summarize(a.changes) }}</td>
                        </tr>
                        <tr v-if="!activities.data?.length">
                            <td colspan="5" class="px-6 py-12 text-center text-ink-subtle text-sm">No activity recorded yet.</td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="activities.last_page > 1" class="px-6 py-4 border-t border-border flex items-center justify-between text-sm text-ink-muted">
                    <span>Showing {{ activities.from }}–{{ activities.to }} of {{ activities.total }}</span>
                    <div class="flex gap-2">
                        <Link v-if="activities.prev_page_url" :href="activities.prev_page_url" class="px-3 py-1 border border-border rounded hover:bg-surface-muted">Previous</Link>
                        <Link v-if="activities.next_page_url" :href="activities.next_page_url" class="px-3 py-1 border border-border rounded hover:bg-surface-muted">Next</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
