<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

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
    <AppLayout title="Vendors">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-900">Vendors</h2>
                <Link href="/admin/vendors/create" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
                    Add Vendor
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-3">
                <input v-model="search" type="text" placeholder="Search name or contact..."
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 flex-1 min-w-48" />
                <select v-model="category" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All categories</option>
                    <option v-for="cat in categories" :key="cat" :value="cat" class="capitalize">{{ cat }}</option>
                </select>
                <select v-model="status" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="vendor in vendors.data" :key="vendor.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-gray-900">{{ vendor.name }}</p>
                                <p class="text-xs text-gray-500">{{ vendor.email }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 capitalize">{{ vendor.category }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <p>{{ vendor.contact_name }}</p>
                                <p class="text-xs">{{ vendor.phone }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <span v-if="vendor.base_rate">${{ vendor.base_rate?.toLocaleString() }}</span>
                                <span class="text-xs text-gray-500 ml-1 capitalize">{{ vendor.rate_type?.replace('_', ' ') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="vendor.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                    class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">
                                    {{ vendor.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <Link :href="`/admin/vendors/${vendor.id}/edit`" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</Link>
                                <button @click="destroy(vendor)" class="text-red-500 hover:text-red-700">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="!vendors.data?.length">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400 text-sm">No vendors found.</td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="vendors.meta?.last_page > 1" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600">
                    <span>Showing {{ vendors.meta.from }}–{{ vendors.meta.to }} of {{ vendors.meta.total }}</span>
                    <div class="flex gap-2">
                        <Link v-if="vendors.links?.prev" :href="vendors.links.prev" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Previous</Link>
                        <Link v-if="vendors.links?.next" :href="vendors.links.next" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Next</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
