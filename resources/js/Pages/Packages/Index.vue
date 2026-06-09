<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

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
    inactive: 'bg-gray-100 text-gray-600',
    archived: 'bg-red-100 text-red-600',
};
</script>

<template>
    <AppLayout title="Packages">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Packages</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Manage your event packages</p>
                </div>
                <Link href="/admin/packages/create" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Package
                </Link>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-col sm:flex-row gap-3">
                <input v-model="search" type="text" placeholder="Search packages..." class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                <select v-model="status" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="archived">Archived</option>
                </select>
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Package</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guests</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Base Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="pkg in packages.data" :key="pkg.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ pkg.name }}</div>
                                <div class="text-sm text-gray-500">{{ pkg.slug }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ pkg.guests?.min ?? 'â€”' }}â€“{{ pkg.guests?.max ?? 'â€”' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                                LKR {{ pkg.base_price?.toLocaleString() ?? 'â€”' }}
                            </td>
                            <td class="px-6 py-4">
                                <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', statusColors[pkg.status] ?? 'bg-gray-100 text-gray-600']">
                                    {{ pkg.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <Link :href="`/admin/packages/${pkg.slug}/edit`" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</Link>
                                    <button @click="deletePackage(pkg.slug)" class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!packages.data?.length">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm">
                                No packages found.
                                <Link href="/admin/packages/create" class="text-indigo-600 hover:underline ml-1">Add one now.</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="packages.meta?.last_page > 1" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600">
                    <span>Showing {{ packages.meta.from }}â€“{{ packages.meta.to }} of {{ packages.meta.total }}</span>
                    <div class="flex gap-2">
                        <Link v-if="packages.links?.prev" :href="packages.links.prev" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Previous</Link>
                        <Link v-if="packages.links?.next" :href="packages.links.next" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Next</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
