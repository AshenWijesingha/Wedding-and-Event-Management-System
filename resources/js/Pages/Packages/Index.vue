<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermissions } from '@/composables/usePermissions';

const { can } = usePermissions();

const props = defineProps({
    packages: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? '');
const status = ref(props.filters?.status ?? '');

let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});
watch(status, applyFilters);

function applyFilters() {
    router.get('/admin/packages', { search: search.value, status: status.value }, {
        preserveState: true, replace: true,
    });
}

function deletePackage(slug) {
    if (!confirm('Delete this package?')) return;
    router.delete(`/admin/packages/${slug}`, { preserveScroll: true });
}

const statusColors = {
    active: 'bg-green-100 text-green-700',
    inactive: 'bg-surface-sunken text-ink-muted',
    archived: 'bg-red-100 text-red-600',
};
</script>

<template>
    <AppLayout tour="packages" title="Packages">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-ink">Packages</h2>
                    <p class="text-sm text-ink-subtle mt-0.5">Manage your event packages</p>
                </div>
                <Link v-if="can('packages.create')" href="/admin/packages/create" class="inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Package
                </Link>
            </div>

            <div class="bg-surface rounded-lg shadow-sm p-4 flex flex-col sm:flex-row gap-3">
                <input v-model="search" type="text" placeholder="Search packages..." class="flex-1 border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                <select v-model="status" class="border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="archived">Archived</option>
                </select>
            </div>

            <div class="bg-surface rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-surface-muted">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Package</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Guests</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Base Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-ink-subtle uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface divide-y divide-border">
                        <tr v-for="pkg in packages.data" :key="pkg.id" class="hover:bg-surface-muted">
                            <td class="px-6 py-4">
                                <div class="font-medium text-ink">{{ pkg.name }}</div>
                                <div class="text-sm text-ink-subtle">{{ pkg.slug }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-ink-muted">
                                {{ pkg.guests?.min ?? 'â€”' }}â€“{{ pkg.guests?.max ?? 'â€”' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-ink font-medium">
                                LKR {{ pkg.base_price?.toLocaleString() ?? 'â€”' }}
                            </td>
                            <td class="px-6 py-4">
                                <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', statusColors[pkg.status] ?? 'bg-surface-sunken text-ink-muted']">
                                    {{ pkg.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <Link v-if="can('packages.edit')" :href="`/admin/packages/${pkg.slug}/edit`" class="text-primary hover:text-primary-dark text-sm font-medium">Edit</Link>
                                    <button v-if="can('packages.delete')" @click="deletePackage(pkg.slug)" class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!packages.data?.length">
                            <td colspan="5" class="px-6 py-12 text-center text-ink-subtle text-sm">
                                No packages found.
                                <Link v-if="can('packages.create')" href="/admin/packages/create" class="text-primary hover:underline ml-1">Add one now.</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="packages.meta?.last_page > 1" class="px-6 py-4 border-t border-border flex items-center justify-between text-sm text-ink-muted">
                    <span>Showing {{ packages.meta.from }}â€“{{ packages.meta.to }} of {{ packages.meta.total }}</span>
                    <div class="flex gap-2">
                        <Link v-if="packages.links?.prev" :href="packages.links.prev" class="px-3 py-1 border border-border rounded hover:bg-surface-muted">Previous</Link>
                        <Link v-if="packages.links?.next" :href="packages.links.next" class="px-3 py-1 border border-border rounded hover:bg-surface-muted">Next</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
