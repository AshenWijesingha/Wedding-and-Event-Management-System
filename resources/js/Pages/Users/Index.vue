<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ users: Object, filters: Object, roles: Array, stats: Object, systemWide: Boolean });

const search = ref(props.filters?.search ?? '');
const role = ref(props.filters?.role ?? '');
const status = ref(props.filters?.status ?? '');

let timer;
watch([search, role, status], () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get('/admin/users',
            { search: search.value, role: role.value, status: status.value },
            { preserveState: true, replace: true });
    }, 300);
});

function destroy(user) {
    if (!window.confirm(`Delete ${user.name}? This cannot be undone.`)) return;
    router.delete(`/admin/users/${user.id}`, { preserveScroll: true });
}

const roleColors = {
    super_admin: 'bg-purple-100 text-purple-700',
    admin:       'bg-indigo-100 text-indigo-700',
    manager:     'bg-blue-100 text-blue-700',
    staff:       'bg-gray-100 text-gray-700',
    client:      'bg-green-100 text-green-700',
};
</script>

<template>
    <AppLayout title="User Management">
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">User Management</h2>
                    <p class="text-sm text-gray-500 mt-0.5">{{ systemWide ? 'System-wide accounts across all tenants.' : 'Accounts in your organization.' }}</p>
                </div>
                <Link href="/admin/users/create" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
                    Add User
                </Link>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Total Users</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.total }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Active</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">{{ stats.active }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Inactive</p>
                    <p class="text-2xl font-bold text-gray-400 mt-1">{{ stats.inactive }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Admins</p>
                    <p class="text-2xl font-bold text-indigo-600 mt-1">
                        {{ (stats.byRole?.super_admin ?? 0) + (stats.byRole?.admin ?? 0) }}
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-3">
                <input v-model="search" type="text" placeholder="Search name or email..."
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 flex-1 min-w-[180px]" />
                <select v-model="role" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All roles</option>
                    <option v-for="r in roles" :key="r" :value="r" class="capitalize">{{ r.replace('_', ' ') }}</option>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th v-if="systemWide" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tenant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="u in users.data" :key="u.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-indigo-500 flex items-center justify-center text-white text-sm font-medium overflow-hidden">
                                        <img v-if="u.avatar_url" :src="u.avatar_url" alt="" class="w-full h-full object-cover" />
                                        <span v-else>{{ u.name?.charAt(0)?.toUpperCase() }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ u.name }}</p>
                                        <p class="text-xs text-gray-500">
                                            {{ u.email }}
                                            <span v-if="!u.verified" class="ml-1 text-amber-500">· unverified</span>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="['inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', roleColors[u.role] ?? 'bg-gray-100 text-gray-600']">
                                    {{ u.role?.replace('_', ' ') }}
                                </span>
                            </td>
                            <td v-if="systemWide" class="px-6 py-4 text-sm text-gray-600">{{ u.tenant ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <span :class="u.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                    class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium">
                                    {{ u.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <Link :href="`/admin/users/${u.id}/edit`" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</Link>
                                <button @click="destroy(u)" class="text-red-500 hover:text-red-700">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="!users.data?.length">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400 text-sm">No users found.</td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="users.last_page > 1" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600">
                    <span>Showing {{ users.from }}–{{ users.to }} of {{ users.total }}</span>
                    <div class="flex gap-2">
                        <Link v-if="users.prev_page_url" :href="users.prev_page_url" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Previous</Link>
                        <Link v-if="users.next_page_url" :href="users.next_page_url" class="px-3 py-1 border border-gray-300 rounded hover:bg-gray-50">Next</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
