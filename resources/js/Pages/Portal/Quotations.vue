<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({ quotations: Object });

const statusColors = {
    draft:    'bg-gray-100 text-gray-600',
    sent:     'bg-blue-100 text-blue-700',
    viewed:   'bg-purple-100 text-purple-700',
    accepted: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-600',
    expired:  'bg-orange-100 text-orange-700',
};
</script>

<template>
    <AppLayout title="My Quotations">
        <div class="max-w-3xl mx-auto space-y-4">
            <h2 class="text-xl font-semibold text-gray-900">My Quotations</h2>

            <div v-if="quotations.data?.length" class="space-y-3">
                <div v-for="q in quotations.data" :key="q.id" class="bg-white rounded-lg shadow-sm p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-semibold text-gray-900">{{ q.quotation_number }}</p>
                            <p class="text-sm text-gray-500 mt-0.5">{{ q.venue?.name }}</p>
                        </div>
                        <span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', statusColors[q.status] ?? 'bg-gray-100 text-gray-600']">
                            {{ q.status }}
                        </span>
                    </div>
                    <div class="mt-3 grid grid-cols-3 gap-3 text-sm">
                        <div>
                            <p class="text-xs text-gray-400">Total</p>
                            <p class="font-semibold">${{ q.total?.toLocaleString() ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Event Date</p>
                            <p>{{ q.event_date ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Valid Until</p>
                            <p>{{ q.valid_until ?? '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="bg-white rounded-lg shadow-sm p-12 text-center text-gray-400">
                <p class="text-sm">No quotations yet.</p>
            </div>
        </div>
    </AppLayout>
</template>
