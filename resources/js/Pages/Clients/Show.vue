<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import { usePermissions } from '@/composables/usePermissions';

const props = defineProps({
    client: Object,
    bookings: { type: Array, default: () => [] },
    inquiries: { type: Array, default: () => [] },
    quotations: { type: Array, default: () => [] },
    payments: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
});

const { can } = usePermissions();
const c = props.client;

const money = (v) => 'LKR ' + Number(v ?? 0).toLocaleString();
const fmt = (d) => d ? new Date(d).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : '—';
const titleCase = (s) => (s ?? '').replace(/_/g, ' ').replace(/\b\w/g, (x) => x.toUpperCase());

function destroy() {
    if (confirm('Delete this client? This cannot be undone.')) {
        router.delete(`/admin/clients/${c.id}`);
    }
}
</script>

<template>
    <AppLayout :title="c.full_name">
        <div class="max-w-5xl mx-auto space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <Link href="/admin/clients" class="text-ink-subtle hover:text-ink-muted shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </Link>
                    <div class="w-11 h-11 rounded-full bg-primary/10 text-primary flex items-center justify-center text-base font-semibold shrink-0">
                        {{ c.full_name?.charAt(0)?.toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                        <h2 class="font-display text-xl font-semibold text-ink truncate">{{ c.full_name }}</h2>
                        <p v-if="c.company_name" class="text-sm text-ink-subtle truncate">{{ c.company_name }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Link v-if="can('clients.edit')" :href="`/admin/clients/${c.id}/edit`"
                        class="px-4 py-2 border border-border text-ink-muted text-sm font-medium rounded-lg hover:bg-surface-muted">Edit</Link>
                    <button v-if="can('clients.delete')" @click="destroy"
                        class="px-4 py-2 border border-danger/30 text-danger text-sm font-medium rounded-lg hover:bg-danger/5">Delete</button>
                </div>
            </div>

            <!-- Stat cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <Card body-class="p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-subtle">Lifetime Value</p>
                    <p class="mt-0.5 text-2xl font-bold text-ink">{{ money(stats.lifetime_value) }}</p>
                </Card>
                <Card body-class="p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-subtle">Open Balance</p>
                    <p class="mt-0.5 text-2xl font-bold text-ink">{{ money(stats.open_balance) }}</p>
                </Card>
                <Card body-class="p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-ink-subtle">Bookings</p>
                    <p class="mt-0.5 text-2xl font-bold text-ink">{{ stats.bookings_count ?? 0 }}</p>
                </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Contact details -->
                <Card body-class="p-5" class="lg:col-span-1">
                    <h3 class="text-sm font-semibold text-ink mb-3">Contact</h3>
                    <dl class="space-y-2 text-sm">
                        <div><dt class="text-ink-subtle">Email</dt><dd class="text-ink">{{ c.email ?? '—' }}</dd></div>
                        <div><dt class="text-ink-subtle">Phone</dt><dd class="text-ink">{{ c.phone ?? '—' }}</dd></div>
                        <div v-if="c.secondary_phone"><dt class="text-ink-subtle">Secondary</dt><dd class="text-ink">{{ c.secondary_phone }}</dd></div>
                        <div>
                            <dt class="text-ink-subtle">Address</dt>
                            <dd class="text-ink">
                                <template v-if="c.address?.line1 || c.address?.city">
                                    {{ [c.address.line1, c.address.city, c.address.state, c.address.postal_code, c.address.country].filter(Boolean).join(', ') }}
                                </template>
                                <template v-else>—</template>
                            </dd>
                        </div>
                    </dl>
                </Card>

                <!-- History -->
                <div class="lg:col-span-2 space-y-4">
                    <!-- Bookings -->
                    <Card body-class="p-0">
                        <div class="px-5 py-3 border-b border-border"><h3 class="font-display font-semibold text-ink">Bookings</h3></div>
                        <ul v-if="bookings.length" class="divide-y divide-border">
                            <li v-for="b in bookings" :key="b.id" class="px-5 py-3 flex items-center justify-between gap-3">
                                <Link :href="`/admin/bookings/${b.id}`" class="min-w-0 hover:underline">
                                    <p class="text-sm font-medium text-ink truncate">{{ titleCase(b.event_type) }} <span class="text-ink-subtle">· {{ b.venue || 'No venue' }}</span></p>
                                    <p class="text-xs text-ink-subtle">{{ fmt(b.event_date) }} · {{ money(b.total) }}</p>
                                </Link>
                                <StatusBadge :status="b.status" />
                            </li>
                        </ul>
                        <EmptyState v-else title="No bookings" />
                    </Card>

                    <!-- Quotations -->
                    <Card body-class="p-0">
                        <div class="px-5 py-3 border-b border-border"><h3 class="font-display font-semibold text-ink">Quotations</h3></div>
                        <ul v-if="quotations.length" class="divide-y divide-border">
                            <li v-for="q in quotations" :key="q.id" class="px-5 py-3 flex items-center justify-between gap-3">
                                <Link :href="`/admin/quotations/${q.id}`" class="min-w-0 hover:underline">
                                    <p class="text-sm font-medium text-ink truncate">{{ q.number }}</p>
                                    <p class="text-xs text-ink-subtle">{{ fmt(q.created_at) }} · {{ money(q.total) }}</p>
                                </Link>
                                <StatusBadge :status="q.status" />
                            </li>
                        </ul>
                        <EmptyState v-else title="No quotations" />
                    </Card>

                    <!-- Inquiries -->
                    <Card body-class="p-0">
                        <div class="px-5 py-3 border-b border-border"><h3 class="font-display font-semibold text-ink">Inquiries</h3></div>
                        <ul v-if="inquiries.length" class="divide-y divide-border">
                            <li v-for="i in inquiries" :key="i.id" class="px-5 py-3 flex items-center justify-between gap-3">
                                <Link :href="`/admin/inquiries/${i.id}`" class="min-w-0 hover:underline">
                                    <p class="text-sm font-medium text-ink truncate">{{ titleCase(i.event_type) }}</p>
                                    <p class="text-xs text-ink-subtle">{{ fmt(i.created_at) }}</p>
                                </Link>
                                <StatusBadge :status="i.status" />
                            </li>
                        </ul>
                        <EmptyState v-else title="No inquiries" />
                    </Card>

                    <!-- Payments -->
                    <Card body-class="p-0">
                        <div class="px-5 py-3 border-b border-border"><h3 class="font-display font-semibold text-ink">Payments</h3></div>
                        <ul v-if="payments.length" class="divide-y divide-border">
                            <li v-for="p in payments" :key="p.id" class="px-5 py-3 flex items-center justify-between gap-3">
                                <Link :href="`/admin/payments/${p.id}`" class="min-w-0 hover:underline">
                                    <p class="text-sm font-medium text-ink truncate">{{ p.number }}</p>
                                    <p class="text-xs text-ink-subtle">{{ fmt(p.payment_date) }} · {{ money(p.amount) }}</p>
                                </Link>
                                <StatusBadge :status="p.status" />
                            </li>
                        </ul>
                        <EmptyState v-else title="No payments" />
                    </Card>

                    <Card v-if="c.notes" body-class="p-5">
                        <h3 class="text-sm font-semibold text-ink mb-2">Notes</h3>
                        <p class="text-sm text-ink-muted whitespace-pre-line">{{ c.notes }}</p>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
