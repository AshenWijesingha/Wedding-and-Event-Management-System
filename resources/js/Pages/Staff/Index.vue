<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermissions } from '@/composables/usePermissions';

const { can } = usePermissions();

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
                <h2 class="text-xl font-semibold text-ink">Staff</h2>
                <Link v-if="can('staff.create')" href="/admin/staff/create" class="px-4 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-lg">
                    Add Staff Member
                </Link>
            </div>

            <div class="bg-surface rounded-lg shadow-sm p-4 flex gap-3">
                <input v-model="search" type="text" placeholder="Search name..."
                    class="border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary flex-1" />
                <select v-model="status" class="border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="bg-surface rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-surface-muted">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-ink-subtle uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface divide-y divide-border">
                        <tr v-for="person in staff.data" :key="person.id" class="hover:bg-surface-muted">
                            <td class="px-6 py-4">
                                <Link :href="`/admin/staff/${person.id}`" class="text-sm font-medium text-primary hover:text-primary-dark">
                                    {{ person.full_name }}
                                </Link>
                            </td>
                            <td class="px-6 py-4 text-sm text-ink-muted capitalize">{{ person.role }}</td>
                            <td class="px-6 py-4 text-sm text-ink-muted">
                                <p>{{ person.email }}</p>
                                <p class="text-xs text-ink-subtle">{{ person.phone }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="person.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-surface-sunken text-ink-subtle'"
                                    class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">
                                    {{ person.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <Link v-if="can('staff.edit')" :href="`/admin/staff/${person.id}/edit`" class="text-primary hover:text-primary-dark mr-3">Edit</Link>
                                <button v-if="can('staff.delete')" @click="destroy(person)" class="text-red-500 hover:text-red-700">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="!staff.data?.length">
                            <td colspan="5" class="px-6 py-12 text-center text-ink-subtle text-sm">No staff members found.</td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="staff.meta?.last_page > 1" class="px-6 py-4 border-t border-border flex items-center justify-between text-sm text-ink-muted">
                    <span>Showing {{ staff.meta.from }}–{{ staff.meta.to }} of {{ staff.meta.total }}</span>
                    <div class="flex gap-2">
                        <Link v-if="staff.links?.prev" :href="staff.links.prev" class="px-3 py-1 border border-border rounded hover:bg-surface-muted">Previous</Link>
                        <Link v-if="staff.links?.next" :href="staff.links.next" class="px-3 py-1 border border-border rounded hover:bg-surface-muted">Next</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
