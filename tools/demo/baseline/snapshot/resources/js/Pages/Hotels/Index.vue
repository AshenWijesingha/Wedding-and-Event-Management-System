<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermissions } from '@/composables/usePermissions';

const { can } = usePermissions();

const props = defineProps({
    hotels: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? '');
const status = ref(props.filters?.status ?? '');

let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
});
watch(status, () => applyFilters());

function applyFilters() {
    router.get('/admin/hotels', { search: search.value, status: status.value }, {
        preserveState: true,
        replace: true,
    });
}

const badge = (s) => ({
    draft: 'bg-surface-sunken text-ink-subtle',
    pending: 'bg-amber-100 text-amber-700',
    approved: 'bg-emerald-100 text-emerald-700',
    rejected: 'bg-red-100 text-red-700',
}[s] ?? 'bg-surface-sunken text-ink-subtle');

const submit = (h) => router.post(`/admin/hotels/${h.slug}/submit`);
</script>

<template>
    <AppLayout title="Hotels">
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-ink">Hotels</h2>
                    <p class="text-sm text-ink-subtle mt-0.5">Manage hotel properties</p>
                </div>
                <Link
                    v-if="can('hotels.create')"
                    href="/admin/hotels/create"
                    class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Hotel
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-surface rounded-lg shadow-sm p-4 flex flex-col sm:flex-row gap-3">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search hotels..."
                    class="flex-1 border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                />
                <select
                    v-model="status"
                    class="border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                >
                    <option value="">All statuses</option>
                    <option value="draft">Draft</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>

            <!-- List -->
            <div class="bg-surface rounded-lg shadow-sm divide-y divide-border">
                <div v-for="h in hotels.data" :key="h.id" class="flex items-center justify-between p-4">
                    <div>
                        <Link :href="`/admin/hotels/${h.slug}/edit`" class="font-medium text-ink hover:underline">{{ h.name }}</Link>
                        <div class="text-sm text-ink-subtle mt-0.5">
                            {{ h.city }} · {{ h.venues_count }} venues · {{ h.packages_count }} packages
                        </div>
                        <p v-if="h.approval_status === 'rejected' && h.review_notes" class="mt-1 text-xs text-red-600">
                            Rejected: {{ h.review_notes }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 rounded text-xs font-semibold" :class="badge(h.approval_status)">
                            {{ h.approval_status }}<span v-if="h.changes_pending_review"> · changes pending</span>
                        </span>
                        <Link v-if="can('hotels.edit')" :href="`/admin/hotels/${h.slug}/edit`" class="text-primary hover:text-primary-dark text-sm font-medium">Edit</Link>
                        <button
                            v-if="['draft', 'rejected'].includes(h.approval_status)"
                            @click="submit(h)"
                            class="text-sm font-medium text-amber-600 hover:text-amber-700"
                        >Submit</button>
                    </div>
                </div>
                <p v-if="!hotels.data?.length" class="p-6 text-center text-ink-subtle text-sm">
                    No hotels yet.
                    <Link v-if="can('hotels.create')" href="/admin/hotels/create" class="text-primary hover:underline ml-1">Add one now.</Link>
                </p>
            </div>

            <!-- Pagination -->
            <div v-if="hotels.meta?.last_page > 1" class="px-2 py-3 flex items-center justify-between text-sm text-ink-muted">
                <span>Showing {{ hotels.meta.from }}–{{ hotels.meta.to }} of {{ hotels.meta.total }}</span>
                <div class="flex gap-2">
                    <Link
                        v-if="hotels.links?.prev"
                        :href="hotels.links.prev"
                        class="px-3 py-1 border border-border rounded hover:bg-surface-muted"
                    >Previous</Link>
                    <Link
                        v-if="hotels.links?.next"
                        :href="hotels.links.next"
                        class="px-3 py-1 border border-border rounded hover:bg-surface-muted"
                    >Next</Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
