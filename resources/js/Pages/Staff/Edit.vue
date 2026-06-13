<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ staff: Object });

const form = useForm({
    first_name: props.staff.first_name,
    last_name:  props.staff.last_name,
    email:      props.staff.email ?? '',
    phone:      props.staff.phone ?? '',
    role:       props.staff.role ?? '',
    status:     props.staff.status,
    notes:      props.staff.notes ?? '',
});

const roles = ['coordinator', 'manager', 'assistant', 'photographer', 'decorator', 'caterer', 'driver'];
</script>

<template>
    <AppLayout :title="`Edit — ${staff.full_name}`">
        <div class="max-w-xl mx-auto space-y-4">
            <div class="flex items-center gap-3">
                <Link href="/admin/staff" class="text-ink-subtle hover:text-ink-muted">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <h2 class="text-xl font-semibold text-ink">Edit Staff Member</h2>
            </div>

            <form @submit.prevent="form.put(`/admin/staff/${staff.id}`)" class="bg-surface rounded-lg shadow-sm p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-ink-muted mb-1">First Name *</label>
                        <input v-model="form.first_name" type="text" class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-muted mb-1">Last Name *</label>
                        <input v-model="form.last_name" type="text" class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-muted mb-1">Email</label>
                        <input v-model="form.email" type="email" class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-muted mb-1">Phone</label>
                        <input v-model="form.phone" type="text" class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-muted mb-1">Role</label>
                        <select v-model="form.role" class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary">
                            <option value="">Select role</option>
                            <option v-for="r in roles" :key="r" :value="r" class="capitalize">{{ r }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-muted mb-1">Status</label>
                        <select v-model="form.status" class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-ink-muted mb-1">Notes</label>
                        <textarea v-model="form.notes" rows="3" class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary resize-none"></textarea>
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" :disabled="form.processing"
                        class="px-5 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-lg disabled:opacity-50">
                        Save Changes
                    </button>
                    <Link href="/admin/staff" class="px-5 py-2 border border-border text-ink-muted text-sm rounded-lg hover:bg-surface-muted">Cancel</Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
