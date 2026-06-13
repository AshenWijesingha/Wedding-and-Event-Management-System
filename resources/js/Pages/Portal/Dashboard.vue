<script setup>
import { Link, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { Card, StatCard, StatusBadge } from '@/Components/ui';

defineProps({
    recentBookings: Object,
    upcomingEvents: Object,
    financialSummary: Object,
    unreadNotifications: Array,
});

function dismissNotification(id) {
    router.post('/portal/notifications/read', { id }, { preserveScroll: true });
}
</script>

<template>
    <PortalLayout title="My Portal">
        <div class="max-w-4xl mx-auto space-y-6">
            <div>
                <h2 class="font-display text-2xl font-semibold text-ink">Welcome back</h2>
                <p class="text-ink-muted text-sm mt-1">Here's a summary of your events and payments.</p>
            </div>

            <!-- Notifications -->
            <div v-if="unreadNotifications?.length" class="space-y-2">
                <div v-for="n in unreadNotifications" :key="n.id"
                    class="bg-info-soft border border-info/20 rounded-lg p-3 flex items-start justify-between text-sm">
                    <div>
                        <p class="font-medium text-info">Payment Reminder</p>
                        <p class="text-info text-xs mt-0.5">
                            {{ n.data?.payment_number }} — LKR {{ n.data?.amount }} due {{ n.data?.due_date }}
                        </p>
                    </div>
                    <button @click="dismissNotification(n.id)" class="text-info/60 hover:text-info ml-3">&times;</button>
                </div>
            </div>

            <!-- Financial summary -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <StatCard label="Total Booked" :value="`LKR ${financialSummary?.total_booked?.toLocaleString() ?? 0}`" />
                <StatCard label="Total Paid" accent="success" :value="`LKR ${financialSummary?.total_paid?.toLocaleString() ?? 0}`" />
                <StatCard label="Balance Due" accent="danger" :value="`LKR ${financialSummary?.total_balance?.toLocaleString() ?? 0}`" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <!-- Upcoming events -->
                <Card>
                    <template #header>
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-ink">Upcoming Events</h3>
                            <Link href="/portal/bookings" class="text-xs text-primary hover:underline">View all</Link>
                        </div>
                    </template>
                    <div v-if="upcomingEvents?.length" class="space-y-3">
                        <div v-for="b in upcomingEvents" :key="b.id" class="flex items-center justify-between text-sm border-b border-border pb-2 last:border-0">
                            <div>
                                <p class="font-medium text-ink">{{ b.event?.date }}</p>
                                <p class="text-xs text-ink-subtle">{{ b.venue?.name }}</p>
                            </div>
                            <StatusBadge :status="b.status" />
                        </div>
                    </div>
                    <p v-else class="text-sm text-ink-subtle">No upcoming events.</p>
                </Card>

                <!-- Recent bookings -->
                <Card>
                    <template #header>
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-ink">Recent Bookings</h3>
                            <Link href="/portal/bookings" class="text-xs text-primary hover:underline">View all</Link>
                        </div>
                    </template>
                    <div v-if="recentBookings?.length" class="space-y-3">
                        <div v-for="b in recentBookings" :key="b.id" class="text-sm border-b border-border pb-2 last:border-0">
                            <div class="flex items-center justify-between">
                                <Link :href="`/portal/bookings/${b.id}`" class="font-medium text-primary hover:underline">{{ b.booking_number }}</Link>
                                <StatusBadge :status="b.status" />
                            </div>
                            <p class="text-xs text-ink-subtle mt-0.5">{{ b.event?.date }} · {{ b.venue?.name }}</p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-ink-subtle">No bookings yet.</p>
                </Card>
            </div>

            <!-- Quick links -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <Link href="/portal/bookings" class="bg-surface border border-border rounded-lg p-4 text-center hover:shadow-md transition-shadow text-primary font-medium text-sm">My Bookings</Link>
                <Link href="/portal/quotations" class="bg-surface border border-border rounded-lg p-4 text-center hover:shadow-md transition-shadow text-primary font-medium text-sm">Quotations</Link>
                <Link href="/portal/payments" class="bg-surface border border-border rounded-lg p-4 text-center hover:shadow-md transition-shadow text-primary font-medium text-sm">Payments</Link>
                <Link href="/venues" class="bg-surface border border-border rounded-lg p-4 text-center hover:shadow-md transition-shadow text-primary font-medium text-sm">Browse Venues</Link>
            </div>
        </div>
    </PortalLayout>
</template>
