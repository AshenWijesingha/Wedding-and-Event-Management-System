<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermissions } from '@/composables/usePermissions';

const { can } = usePermissions();

const props = defineProps({
    vendors: Object,
    filters: Object,
});

const search   = ref(props.filters?.search ?? '');
const category = ref(props.filters?.category ?? '');
const status   = ref(props.filters?.status ?? '');

let timer;
watch([search, category, status], () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get('/admin/vendors', { search: search.value, category: category.value, status: status.value }, { preserveState: true, replace: true });
    }, 300);
});

function destroy(vendor) {
    if (!window.confirm(`Delete vendor "${vendor.name}"?`)) return;
    router.delete(`/admin/vendors/${vendor.id}`, { preserveScroll: true });
}

const categories = ['photographer', 'caterer', 'florist', 'music', 'decor', 'transport', 'videographer'];
</script>

<template>
    <AppLayout tour="vendors" title="Vendors">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-ink">Vendors</h2>
                <Link v-if="can('vendors.create')" href="/admin/vendors/create" class="px-4 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-lg">
                    Add Vendor
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-surface rounded-lg shadow-sm p-4 flex flex-wrap gap-3">
                <input v-model="search" type="text" placeholder="Search name or contact..."
                    class="border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary flex-1 min-w-48" />
                <select v-model="category" class="border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All categories</option>
                    <option v-for="cat in categories" :key="cat" :value="cat" class="capitalize">{{ cat }}</option>
                </select>
                <select v-model="status" class="border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <!-- Table -->
            <div class="bg-surface rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-surface-muted">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Vendor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Rate</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface divide-y divide-border">
                        <tr v-for="vendor in vendors.data" :key="vendor.id" class="hover:bg-surface-muted">
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-ink">{{ vendor.name }}</p>
                                <p class="text-xs text-ink-subtle">{{ vendor.email }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-ink-muted capitalize">{{ vendor.category }}</td>
                            <td class="px-6 py-4 text-sm text-ink-muted">
                                <p>{{ vendor.contact_name }}</p>
                                <p class="text-xs">{{ vendor.phone }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-ink">
                                <span v-if="vendor.base_rate">LKR {{ vendor.base_rate?.toLocaleString() }}</span>
                                <span class="text-xs text-ink-subtle ml-1 capitalize">{{ vendor.rate_type?.replace('_', ' ') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="vendor.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-surface-sunken text-ink-subtle'"
                                    class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">
                                    {{ vendor.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <Link v-if="can('vendors.edit')" :href="`/admin/vendors/${vendor.id}/edit`" class="text-primary hover:text-primary-dark mr-3">Edit</Link>
                                <button v-if="can('vendors.delete')" @click="destroy(vendor)" class="text-red-500 hover:text-red-700">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="!vendors.data?.length">
                            <td colspan="6" class="px-6 py-12 text-center text-ink-subtle text-sm">No vendors found.</td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="vendors.meta?.last_page > 1" class="px-6 py-4 border-t border-border flex items-center justify-between text-sm text-ink-muted">
                    <span>Showing {{ vendors.meta.from }}â€“{{ vendors.meta.to }} of {{ vendors.meta.total }}</span>
                    <div class="flex gap-2">
                        <Link v-if="vendors.links?.prev" :href="vendors.links.prev" class="px-3 py-1 border border-border rounded hover:bg-surface-muted">Previous</Link>
                        <Link v-if="vendors.links?.next" :href="vendors.links.next" class="px-3 py-1 border border-border rounded hover:bg-surface-muted">Next</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
