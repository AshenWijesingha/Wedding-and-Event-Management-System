<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ bookings: Object, filters: Object });

const status = ref(props.filters?.status ?? '');

watch(status, () => {
    router.get('/portal/bookings', { status: status.value }, { preserveState: true, replace: true });
});

const statusColors = {
    pending:    'bg-gray-100 text-gray-600',
    tentative:  'bg-yellow-100 text-yellow-700',
    confirmed:  'bg-green-100 text-green-700',
    in_progress:'bg-blue-100 text-blue-700',
    completed:  'bg-gray-100 text-gray-600',
    cancelled:  'bg-red-100 text-red-600',
};
</script>

<template>
    <AppLayout title="My Bookings">
        <div class="max-w-3xl mx-auto space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-900">My Bookings</h2>
                <select v-model="status" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All statuses</option>
                    <option value="tentative">Tentative</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <div v-if="bookings.data?.length" class="space-y-3">
                <div v-for="b in bookings.data" :key="b.id" class="bg-white rounded-lg shadow-sm p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between">
                        <div>
                            <Link :href="`/portal/bookings/${b.id}`" class="font-semibold text-indigo-600 hover:underline">{{ b.booking_number }}</Link>
                            <p class="text-sm text-gray-600 mt-0.5">{{ b.venue?.name }}</p>
                        </div>
                        <span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', statusColors[b.status] ?? 'bg-gray-100 text-gray-600']">
                            {{ b.status?.replace('_', ' ') }}
                        </span>
                    </div>
                    <div class="mt-3 grid grid-cols-3 gap-3 text-sm">
                        <div>
                            <p class="text-xs text-gray-400">Event Date</p>
                            <p class="font-medium">{{ b.event?.date }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Guests</p>
                            <p class="font-medium">{{ b.event?.guest_count }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Balance Due</p>
                            <p :class="(b.financial?.balance ?? 0) > 0 ? 'font-semibold text-red-600' : 'font-semibold text-green-600'">
                                LKR {{ b.financial?.balance?.toLocaleString() ?? 0 }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="bg-white rounded-lg shadow-sm p-12 text-center text-gray-400">
                <p class="text-sm">No bookings found.</p>
                <Link href="/venues" class="mt-2 inline-block text-sm text-indigo-600 hover:underline">Browse venues â†’</Link>
            </div>

            <div v-if="bookings.meta?.last_page > 1" class="flex items-center justify-between text-sm text-gray-600">
                <span>Showing {{ bookings.meta.from }}â€“{{ bookings.meta.to }} of {{ bookings.meta.total }}</span>
                <div class="flex gap-2">
                    <Link v-if="bookings.links?.prev" :href="bookings.links.prev" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Previous</Link>
                    <Link v-if="bookings.links?.next" :href="bookings.links.next" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Next</Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
