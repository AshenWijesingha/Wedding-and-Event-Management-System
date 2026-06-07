<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    clients: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? '');

let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/clients', { search: search.value }, { preserveState: true, replace: true });
    }, 400);
});
</script>

<template>
    <AppLayout title="Clients">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Clients</h2>
                    <p class="text-sm text-gray-500 mt-0.5">People who have inquired or booked with you</p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-4">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search by name, email or phone..."
                    class="w-full sm:max-w-sm border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bookings</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inquiries</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Since</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="client in clients.data" :key="client.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-medium">
                                        {{ client.name?.charAt(0)?.toUpperCase() }}
                                    </div>
                                    <span class="font-medium text-gray-900">{{ client.name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <div>{{ client.email }}</div>
                                <div class="text-gray-400">{{ client.phone }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ client.bookings_count }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ client.inquiries_count }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ client.created_at }}</td>
                        </tr>
                        <tr v-if="!clients.data?.length">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm">No clients found.</td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="clients.last_page > 1" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600">
                    <span>Showing {{ clients.from }}–{{ clients.to }} of {{ clients.total }}</span>
                    <div class="flex gap-2">
                        <Link v-if="clients.prev_page_url" :href="clients.prev_page_url" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Previous</Link>
                        <Link v-if="clients.next_page_url" :href="clients.next_page_url" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Next</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
