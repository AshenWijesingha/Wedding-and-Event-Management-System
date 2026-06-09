<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    recentBookings: Object,
    upcomingEvents: Object,
    financialSummary: Object,
    unreadNotifications: Array,
});

const statusColors = {
    pending:    'bg-gray-100 text-gray-600',
    tentative:  'bg-yellow-100 text-yellow-700',
    confirmed:  'bg-green-100 text-green-700',
    in_progress:'bg-blue-100 text-blue-700',
    completed:  'bg-gray-100 text-gray-600',
    cancelled:  'bg-red-100 text-red-600',
};

function dismissNotification(id) {
    router.post('/portal/notifications/read', { id }, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="My Portal">
        <div class="max-w-4xl mx-auto space-y-6">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900">Welcome back</h2>
                <p class="text-gray-500 text-sm mt-1">Here's a summary of your events and payments.</p>
            </div>

            <!-- Notifications -->
            <div v-if="unreadNotifications?.length" class="space-y-2">
                <div v-for="n in unreadNotifications" :key="n.id"
                    class="bg-blue-50 border border-blue-100 rounded-lg p-3 flex items-start justify-between text-sm">
                    <div>
                        <p class="font-medium text-blue-800">Payment Reminder</p>
                        <p class="text-blue-600 text-xs mt-0.5">
                            {{ n.data?.payment_number }} â€” LKR {{ n.data?.amount }} due {{ n.data?.due_date }}
                        </p>
                    </div>
                    <button @click="dismissNotification(n.id)" class="text-blue-400 hover:text-blue-600 ml-3">&times;</button>
                </div>
            </div>

            <!-- Financial summary -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase">Total Booked</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">LKR {{ financialSummary?.total_booked?.toLocaleString() ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase">Total Paid</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">LKR {{ financialSummary?.total_paid?.toLocaleString() ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase">Balance Due</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">LKR {{ financialSummary?.total_balance?.toLocaleString() ?? 0 }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <!-- Upcoming events -->
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-900">Upcoming Events</h3>
                        <Link href="/portal/bookings" class="text-xs text-indigo-600 hover:underline">View all</Link>
                    </div>
                    <div v-if="upcomingEvents?.length" class="space-y-3">
                        <div v-for="b in upcomingEvents" :key="b.id" class="flex items-center justify-between text-sm border-b border-gray-100 pb-2">
                            <div>
                                <p class="font-medium text-gray-900">{{ b.event?.date }}</p>
                                <p class="text-xs text-gray-500">{{ b.venue?.name }}</p>
                            </div>
                            <span :class="['inline-flex px-2 py-0.5 rounded-full text-xs font-medium capitalize', statusColors[b.status] ?? 'bg-gray-100 text-gray-600']">
                                {{ b.status }}
                            </span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400">No upcoming events.</p>
                </div>

                <!-- Recent bookings -->
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-900">Recent Bookings</h3>
                        <Link href="/portal/bookings" class="text-xs text-indigo-600 hover:underline">View all</Link>
                    </div>
                    <div v-if="recentBookings?.length" class="space-y-3">
                        <div v-for="b in recentBookings" :key="b.id" class="text-sm border-b border-gray-100 pb-2">
                            <div class="flex items-center justify-between">
                                <Link :href="`/portal/bookings/${b.id}`" class="font-medium text-indigo-600 hover:underline">{{ b.booking_number }}</Link>
                                <span :class="['inline-flex px-2 py-0.5 rounded-full text-xs font-medium capitalize', statusColors[b.status] ?? 'bg-gray-100 text-gray-600']">
                                    {{ b.status }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">{{ b.event?.date }} Â· {{ b.venue?.name }}</p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400">No bookings yet.</p>
                </div>
            </div>

            <!-- Quick links -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <Link href="/portal/bookings" class="bg-white rounded-lg shadow-sm p-4 text-center hover:shadow-md transition-shadow">
                    <div class="text-indigo-600 font-medium text-sm">My Bookings</div>
                </Link>
                <Link href="/portal/quotations" class="bg-white rounded-lg shadow-sm p-4 text-center hover:shadow-md transition-shadow">
                    <div class="text-indigo-600 font-medium text-sm">Quotations</div>
                </Link>
                <Link href="/portal/payments" class="bg-white rounded-lg shadow-sm p-4 text-center hover:shadow-md transition-shadow">
                    <div class="text-indigo-600 font-medium text-sm">Payments</div>
                </Link>
                <Link href="/venues" class="bg-white rounded-lg shadow-sm p-4 text-center hover:shadow-md transition-shadow">
                    <div class="text-indigo-600 font-medium text-sm">Browse Venues</div>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
