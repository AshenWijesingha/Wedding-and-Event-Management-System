<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ quotation: Object });

function act(action) {
    router.post(`/admin/quotations/${props.quotation.id}/${action}`, {}, { preserveScroll: true });
}

const statusColors = {
    draft: 'bg-surface-sunken text-ink-muted',
    sent: 'bg-blue-100 text-blue-700',
    viewed: 'bg-primary/10 text-primary-dark',
    accepted: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-600',
    expired: 'bg-orange-100 text-orange-700',
    cancelled: 'bg-surface-sunken text-ink-subtle',
};

const money = (n) => 'LKR ' + Number(n ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 });
</script>

<template>
    <AppLayout tour="quotation-show" title="Quotation">
        <div class="max-w-4xl mx-auto space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <Link href="/admin/quotations" class="text-ink-subtle hover:text-ink-muted">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </Link>
                    <div>
                        <h2 class="text-xl font-semibold text-ink">{{ quotation.quotation_number }}</h2>
                        <span :class="['inline-flex mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', statusColors[quotation.status] ?? 'bg-surface-sunken text-ink-muted']">
                            {{ quotation.status }}
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Status workflow actions -->
                    <button v-if="quotation.status === 'draft'" @click="act('send')"
                        class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-3 py-2 rounded-lg">
                        Send
                    </button>
                    <button v-if="['sent', 'viewed'].includes(quotation.status)" @click="act('accept')"
                        class="inline-flex items-center gap-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-3 py-2 rounded-lg">
                        Accept
                    </button>
                    <button v-if="['sent', 'viewed'].includes(quotation.status)" @click="act('reject')"
                        class="inline-flex items-center gap-1.5 bg-surface border border-red-300 text-red-600 hover:bg-red-50 text-sm font-medium px-3 py-2 rounded-lg">
                        Reject
                    </button>
                    <a :href="`/admin/quotations/${quotation.id}/print`" target="_blank" rel="noopener"
                        class="inline-flex items-center gap-2 bg-surface border border-border text-ink-muted hover:bg-surface-muted text-sm font-medium px-4 py-2 rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print
                    </a>
                    <a :href="`/admin/quotations/${quotation.id}/pdf`"
                        class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Download PDF
                    </a>
                </div>
            </div>

            <!-- Meta cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-surface rounded-lg shadow-sm p-4">
                    <p class="text-xs font-medium text-ink-subtle uppercase">Client</p>
                    <p class="text-sm font-medium text-ink mt-1">{{ quotation.client?.full_name ?? 'â€”' }}</p>
                    <p class="text-xs text-ink-subtle">{{ quotation.client?.email }}</p>
                </div>
                <div class="bg-surface rounded-lg shadow-sm p-4">
                    <p class="text-xs font-medium text-ink-subtle uppercase">Venue</p>
                    <p class="text-sm font-medium text-ink mt-1">{{ quotation.venue?.name ?? 'â€”' }}</p>
                    <p class="text-xs text-ink-subtle">{{ quotation.guest_count ? quotation.guest_count + ' guests' : '' }}</p>
                </div>
                <div class="bg-surface rounded-lg shadow-sm p-4">
                    <p class="text-xs font-medium text-ink-subtle uppercase">Dates</p>
                    <p class="text-sm font-medium text-ink mt-1">Event: {{ quotation.event_date ?? 'â€”' }}</p>
                    <p class="text-xs text-ink-subtle">Valid until: {{ quotation.valid_until ?? 'â€”' }}</p>
                </div>
            </div>

            <!-- Items -->
            <div class="bg-surface rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-surface-muted">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase">Item</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-ink-subtle uppercase">Qty</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-ink-subtle uppercase">Unit Price</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-ink-subtle uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="(item, i) in quotation.items" :key="i">
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-ink">{{ item.name }}</p>
                                <p v-if="item.description" class="text-xs text-ink-subtle">{{ item.description }}</p>
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-ink-muted">{{ item.quantity }}</td>
                            <td class="px-6 py-4 text-right text-sm text-ink-muted">{{ money(item.unit_price) }}</td>
                            <td class="px-6 py-4 text-right text-sm font-medium text-ink">{{ money(item.total) }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Totals -->
                <div class="border-t border-border px-6 py-4 flex justify-end">
                    <dl class="w-full max-w-xs space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-ink-subtle">Subtotal</dt><dd class="text-ink">{{ money(quotation.financial?.subtotal) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-ink-subtle">Discount</dt><dd class="text-ink">-{{ money(quotation.financial?.discount) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-ink-subtle">Tax</dt><dd class="text-ink">{{ money(quotation.financial?.tax) }}</dd></div>
                        <div class="flex justify-between border-t border-border pt-1 font-semibold"><dt class="text-ink">Total</dt><dd class="text-primary">{{ money(quotation.financial?.total) }}</dd></div>
                    </dl>
                </div>
            </div>

            <!-- Notes / Terms -->
            <div v-if="quotation.notes || quotation.terms_and_conditions" class="bg-surface rounded-lg shadow-sm p-6 space-y-4">
                <div v-if="quotation.notes">
                    <h3 class="text-sm font-semibold text-ink">Notes</h3>
                    <p class="text-sm text-ink-muted mt-1 whitespace-pre-line">{{ quotation.notes }}</p>
                </div>
                <div v-if="quotation.terms_and_conditions">
                    <h3 class="text-sm font-semibold text-ink">Terms &amp; Conditions</h3>
                    <p class="text-sm text-ink-muted mt-1 whitespace-pre-line">{{ quotation.terms_and_conditions }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
