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
    <AppLayout tour="clients" title="Clients">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-ink">Clients</h2>
                    <p class="text-sm text-ink-subtle mt-0.5">People who have inquired or booked with you</p>
                </div>
            </div>

            <div class="bg-surface rounded-lg shadow-sm p-4">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search by name, email or phone..."
                    class="w-full sm:max-w-sm border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                />
            </div>

            <div class="bg-surface rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-surface-muted">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Bookings</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Inquiries</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Since</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface divide-y divide-border">
                        <tr v-for="client in clients.data" :key="client.id" class="hover:bg-surface-muted">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-primary/10 text-primary-dark flex items-center justify-center text-sm font-medium">
                                        {{ client.name?.charAt(0)?.toUpperCase() }}
                                    </div>
                                    <span class="font-medium text-ink">{{ client.name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-ink-muted">
                                <div>{{ client.email }}</div>
                                <div class="text-ink-subtle">{{ client.phone }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-ink-muted">{{ client.bookings_count }}</td>
                            <td class="px-6 py-4 text-sm text-ink-muted">{{ client.inquiries_count }}</td>
                            <td class="px-6 py-4 text-sm text-ink-subtle">{{ client.created_at }}</td>
                        </tr>
                        <tr v-if="!clients.data?.length">
                            <td colspan="5" class="px-6 py-12 text-center text-ink-subtle text-sm">No clients found.</td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="clients.last_page > 1" class="px-6 py-4 border-t border-border flex items-center justify-between text-sm text-ink-muted">
                    <span>Showing {{ clients.from }}–{{ clients.to }} of {{ clients.total }}</span>
                    <div class="flex gap-2">
                        <Link v-if="clients.prev_page_url" :href="clients.prev_page_url" class="px-3 py-1 border border-border rounded hover:bg-surface-muted">Previous</Link>
                        <Link v-if="clients.next_page_url" :href="clients.next_page_url" class="px-3 py-1 border border-border rounded hover:bg-surface-muted">Next</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
