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
                <h2 class="text-xl font-semibold text-gray-900">Audit Log</h2>
                <p class="text-sm text-gray-500 mt-0.5">Platform-wide record of changes across all tenants.</p>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-3">
                <select v-model="subjectType" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All types</option>
                    <option v-for="st in subjectTypes" :key="st.value" :value="st.value">{{ st.label }}</option>
                </select>
                <select v-model="event" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All events</option>
                    <option value="created">Created</option>
                    <option value="updated">Updated</option>
                    <option value="deleted">Deleted</option>
                </select>
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">When</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Causer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Changes</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="a in activities.data" :key="a.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">{{ a.created_at }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ a.causer }}</td>
                            <td class="px-6 py-4">
                                <span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', eventColors[a.event] ?? 'bg-gray-100 text-gray-600']">
                                    {{ a.event ?? a.description }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ a.subject_type }}<span class="text-gray-400"> #{{ a.subject_id }}</span></td>
                            <td class="px-6 py-4 text-xs text-gray-500 max-w-md truncate">{{ summarize(a.changes) }}</td>
                        </tr>
                        <tr v-if="!activities.data?.length">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm">No activity recorded yet.</td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="activities.last_page > 1" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600">
                    <span>Showing {{ activities.from }}–{{ activities.to }} of {{ activities.total }}</span>
                    <div class="flex gap-2">
                        <Link v-if="activities.prev_page_url" :href="activities.prev_page_url" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Previous</Link>
                        <Link v-if="activities.next_page_url" :href="activities.next_page_url" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Next</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
