<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import OnboardingChecklist from '@/Components/OnboardingChecklist.vue';
import Card from '@/Components/ui/Card.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    upcomingEvents: { type: Array, default: () => [] },
    recentInquiries: { type: Array, default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const money = (v) => 'LKR ' + Number(v ?? 0).toLocaleString();

const statCards = computed(() => [
    {
        label: 'Total Bookings', value: props.stats.bookings_total ?? 0, accent: 'primary',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    },
    {
        label: 'Open Inquiries', value: props.stats.inquiries_open ?? 0, accent: 'info',
        icon: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z',
    },
    {
        label: 'Revenue (Month)', value: money(props.stats.revenue_month), accent: 'success',
        icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        label: 'Total Clients', value: props.stats.clients_total ?? 0, accent: 'primary',
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
    },
]);

const iconAccent = {
    primary: 'bg-primary/10 text-primary',
    info: 'bg-info-soft text-info',
    success: 'bg-success-soft text-success',
};

const quickActions = [
    { name: 'Manage Bookings', href: '/admin/bookings', desc: 'View and manage all event bookings' },
    { name: 'New Inquiries', href: '/admin/inquiries', desc: 'Respond to incoming event inquiries' },
    { name: 'Venue Setup', href: '/admin/venues', desc: 'Configure venues and availability' },
];

const formatDate = (d) => d ? new Date(d).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : '—';
const titleCase = (s) => (s ?? '').replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
</script>

<template>
    <AppLayout title="Dashboard" tour="dashboard">
        <div class="space-y-6">
            <!-- First-run setup checklist (auto-hides when complete or dismissed) -->
            <OnboardingChecklist />

            <!-- Welcome banner -->
            <div class="bg-surface rounded-lg shadow-sm p-6">
                <h2 class="font-display text-xl font-semibold text-ink">
                    Welcome back, {{ user?.name }} 👋
                </h2>
                <p class="text-ink-subtle mt-1 text-sm">Here's what's happening with your events today.</p>
            </div>

            <!-- Stats grid -->
            <div data-tour="dashboard.stats" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card v-for="card in statCards" :key="card.label" body-class="p-5">
                    <div class="flex items-center gap-4">
                        <div :class="['p-3 rounded-full shrink-0', iconAccent[card.accent] ?? iconAccent.primary]">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="card.icon"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium uppercase tracking-wide text-ink-subtle">{{ card.label }}</p>
                            <p class="mt-0.5 text-2xl font-bold text-ink truncate">{{ card.value }}</p>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Upcoming events + recent inquiries -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <Card class="lg:col-span-2" body-class="p-0">
                    <div class="px-5 py-4 border-b border-border flex items-center justify-between">
                        <h3 class="font-display font-semibold text-ink">Upcoming Events</h3>
                        <Link href="/admin/bookings" class="text-sm text-primary hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-primary rounded">View all</Link>
                    </div>
                    <ul v-if="upcomingEvents.length" class="divide-y divide-border">
                        <li v-for="ev in upcomingEvents" :key="ev.id" class="px-5 py-3 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-ink truncate">{{ ev.client || 'Unknown client' }}</p>
                                <p class="text-xs text-ink-subtle truncate">{{ titleCase(ev.event_type) }} · {{ ev.venue || 'No venue' }} · {{ ev.guest_count }} guests</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm text-ink">{{ formatDate(ev.event_date) }}</p>
                                <StatusBadge :status="ev.status" class="mt-1" />
                            </div>
                        </li>
                    </ul>
                    <EmptyState v-else title="No upcoming events" description="Confirmed and tentative bookings will appear here." />
                </Card>

                <Card body-class="p-0">
                    <div class="px-5 py-4 border-b border-border flex items-center justify-between">
                        <h3 class="font-display font-semibold text-ink">Recent Inquiries</h3>
                        <Link href="/admin/inquiries" class="text-sm text-primary hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-primary rounded">View all</Link>
                    </div>
                    <ul v-if="recentInquiries.length" class="divide-y divide-border">
                        <li v-for="inq in recentInquiries" :key="inq.id" class="px-5 py-3">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-medium text-ink truncate">{{ inq.client || 'Unknown' }}</p>
                                <StatusBadge :status="inq.status" />
                            </div>
                            <p class="text-xs text-ink-subtle mt-0.5">{{ titleCase(inq.event_type) }} · {{ inq.created_at }}</p>
                        </li>
                    </ul>
                    <EmptyState v-else title="No inquiries yet" />
                </Card>
            </div>

            <!-- Quick actions -->
            <div data-tour="dashboard.actions" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Link v-for="action in quickActions" :key="action.href" :href="action.href"
                      class="bg-surface rounded-lg shadow-sm p-5 hover:shadow-md transition-shadow group focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-ink">{{ action.name }}</h3>
                        <svg class="w-5 h-5 text-ink-subtle group-hover:text-primary group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <p class="text-sm text-ink-subtle">{{ action.desc }}</p>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
