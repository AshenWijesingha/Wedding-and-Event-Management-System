<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermissions } from '@/composables/usePermissions';

const { can } = usePermissions();

const props = defineProps({
    quotations: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? '');
const status = ref(props.filters?.status ?? '');

let t;
watch(search, () => { clearTimeout(t); t = setTimeout(applyFilters, 400); });
watch(status, applyFilters);

function applyFilters() {
    router.get('/admin/quotations', { search: search.value, status: status.value }, { preserveState: true, replace: true });
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
</script>

<template>
    <AppLayout tour="quotations" title="Quotations">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-ink">Quotations</h2>
                    <p class="text-sm text-ink-subtle mt-0.5">Manage quotations and proposals</p>
                </div>
                <Link v-if="can('quotations.create')" href="/admin/quotations/create" class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Quotation
                </Link>
            </div>

            <div class="bg-surface rounded-lg shadow-sm p-4 flex flex-col sm:flex-row gap-3">
                <input v-model="search" type="text" placeholder="Search by client or quotation number..." class="flex-1 border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                <select v-model="status" class="border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All statuses</option>
                    <option value="draft">Draft</option>
                    <option value="sent">Sent</option>
                    <option value="viewed">Viewed</option>
                    <option value="accepted">Accepted</option>
                    <option value="rejected">Rejected</option>
                    <option value="expired">Expired</option>
                </select>
            </div>

            <div class="bg-surface rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-surface-muted">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Quotation</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Event Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Valid Until</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-ink-subtle uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface divide-y divide-border">
                        <tr v-for="q in quotations.data" :key="q.id" class="hover:bg-surface-muted">
                            <td class="px-6 py-4">
                                <div class="font-medium text-ink text-sm">{{ q.quotation_number }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-ink-muted">{{ q.client?.full_name }}</td>
                            <td class="px-6 py-4 text-sm text-ink-muted">{{ q.event_date }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-ink">LKR {{ q.financial?.total?.toLocaleString() }}</td>
                            <td class="px-6 py-4 text-sm text-ink-muted">{{ q.valid_until }}</td>
                            <td class="px-6 py-4">
                                <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', statusColors[q.status] ?? 'bg-surface-sunken text-ink-muted']">
                                    {{ q.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <Link :href="`/admin/quotations/${q.id}`" class="text-primary hover:text-primary-dark text-sm font-medium">View</Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!quotations.data?.length">
                            <td colspan="7" class="px-6 py-12 text-center text-ink-subtle text-sm">No quotations found.</td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="quotations.meta?.last_page > 1" class="px-6 py-4 border-t border-border flex items-center justify-between text-sm text-ink-muted">
                    <span>Showing {{ quotations.meta.from }}–{{ quotations.meta.to }} of {{ quotations.meta.total }}</span>
                    <div class="flex gap-2">
                        <Link v-if="quotations.links?.prev" :href="quotations.links.prev" class="px-3 py-1 border border-border rounded hover:bg-surface-muted">Previous</Link>
                        <Link v-if="quotations.links?.next" :href="quotations.links.next" class="px-3 py-1 border border-border rounded hover:bg-surface-muted">Next</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
