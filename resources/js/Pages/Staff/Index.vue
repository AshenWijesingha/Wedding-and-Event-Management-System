<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ staff: Object, filters: Object });

const search = ref(props.filters?.search ?? '');
const status = ref(props.filters?.status ?? '');

let timer;
watch([search, status], () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get('/admin/staff', { search: search.value, status: status.value }, { preserveState: true, replace: true });
    }, 300);
});

function destroy(person) {
    if (!window.confirm(`Delete ${person.full_name}?`)) return;
    router.delete(`/admin/staff/${person.id}`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Staff">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-900">Staff</h2>
                <Link href="/admin/staff/create" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
                    Add Staff Member
                </Link>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-4 flex gap-3">
                <input v-model="search" type="text" placeholder="Search name..."
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 flex-1" />
                <select v-model="status" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="person in staff.data" :key="person.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <Link :href="`/admin/staff/${person.id}`" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                    {{ person.full_name }}
                                </Link>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 capitalize">{{ person.role }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <p>{{ person.email }}</p>
                                <p class="text-xs text-gray-400">{{ person.phone }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="person.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                    class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">
                                    {{ person.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <Link :href="`/admin/staff/${person.id}/edit`" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</Link>
                                <button @click="destroy(person)" class="text-red-500 hover:text-red-700">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="!staff.data?.length">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm">No staff members found.</td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="staff.meta?.last_page > 1" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600">
                    <span>Showing {{ staff.meta.from }}–{{ staff.meta.to }} of {{ staff.meta.total }}</span>
                    <div class="flex gap-2">
                        <Link v-if="staff.links?.prev" :href="staff.links.prev" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Previous</Link>
                        <Link v-if="staff.links?.next" :href="staff.links.next" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Next</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
