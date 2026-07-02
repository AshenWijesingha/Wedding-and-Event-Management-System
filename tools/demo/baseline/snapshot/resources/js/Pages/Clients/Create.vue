<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    secondary_phone: '',
    company_name: '',
    address: '',
    city: '',
    state: '',
    postal_code: '',
    country: '',
    notes: '',
});

function submit() {
    form.post('/admin/clients');
}
</script>

<template>
    <AppLayout title="Add Client">
        <div class="max-w-2xl mx-auto space-y-4">
            <div class="flex items-center gap-3">
                <Link href="/admin/clients" class="text-ink-subtle hover:text-ink-muted">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <h2 class="font-display text-xl font-semibold text-ink">Add Client</h2>
            </div>

            <form @submit.prevent="submit" class="bg-surface rounded-lg shadow-sm p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-ink-muted mb-1">First Name *</label>
                        <input v-model="form.first_name" type="text" class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary" required />
                        <p v-if="form.errors.first_name" class="text-danger text-xs mt-1">{{ form.errors.first_name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-muted mb-1">Last Name *</label>
                        <input v-model="form.last_name" type="text" class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary" required />
                        <p v-if="form.errors.last_name" class="text-danger text-xs mt-1">{{ form.errors.last_name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ink-muted mb-1">Email</label>
                        <input v-model="form.email" type="email" class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary" />
                        <p v-if="form.errors.email" class="text-danger text-xs mt-1">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-muted mb-1">Phone</label>
                        <input v-model="form.phone" type="text" class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ink-muted mb-1">Secondary Phone</label>
                        <input v-model="form.secondary_phone" type="text" class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-muted mb-1">Company</label>
                        <input v-model="form.company_name" type="text" class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary" />
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-ink-muted mb-1">Address</label>
                        <input v-model="form.address" type="text" class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ink-muted mb-1">City</label>
                        <input v-model="form.city" type="text" class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-muted mb-1">State / Province</label>
                        <input v-model="form.state" type="text" class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-muted mb-1">Postal Code</label>
                        <input v-model="form.postal_code" type="text" class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-ink-muted mb-1">Country</label>
                        <input v-model="form.country" type="text" class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary" />
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-ink-muted mb-1">Notes</label>
                        <textarea v-model="form.notes" rows="3" class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary resize-none"></textarea>
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" :disabled="form.processing"
                        class="px-5 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-lg disabled:opacity-50">
                        Create Client
                    </button>
                    <Link href="/admin/clients" class="px-5 py-2 border border-border text-ink-muted text-sm rounded-lg hover:bg-surface-muted">Cancel</Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
