<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({ roles: Array, tenants: Array });

const form = useForm({
    name: '',
    email: '',
    role: 'staff',
    tenant_id: props.tenants[0]?.id ?? null,
    phone: '',
    is_active: true,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/admin/users');
}
</script>

<template>
    <AppLayout title="Add User">
        <div class="max-w-2xl mx-auto space-y-5">
            <div class="flex items-center gap-3">
                <Link href="/admin/users" class="text-ink-subtle hover:text-ink-muted">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <h2 class="text-xl font-semibold text-ink">Add User</h2>
            </div>

            <form @submit.prevent="submit" class="bg-surface rounded-lg shadow-sm p-6 space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Full name" :required="true" />
                        <TextInput v-model="form.name" type="text" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Email address" :required="true" />
                        <TextInput v-model="form.email" type="email" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.email" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Role" :required="true" />
                        <select v-model="form.role" class="mt-1 block w-full border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary capitalize">
                            <option v-for="r in roles" :key="r" :value="r">{{ r.replace('_', ' ') }}</option>
                        </select>
                        <InputError :message="form.errors.role" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Tenant" :required="true" />
                        <select v-if="tenants.length > 1" v-model="form.tenant_id" class="mt-1 block w-full border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                        <p v-else class="mt-1 block w-full border border-border bg-surface-muted rounded-md px-3 py-2 text-sm text-ink-muted">{{ tenants[0]?.name ?? '—' }}</p>
                        <InputError :message="form.errors.tenant_id" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Phone" />
                        <TextInput v-model="form.phone" type="text" class="mt-1 block w-full" />
                        <InputError :message="form.errors.phone" class="mt-1" />
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-sm text-ink-muted">
                            <input v-model="form.is_active" type="checkbox" class="rounded border-border text-primary focus:ring-primary" />
                            Active account
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-border pt-5">
                    <div>
                        <InputLabel value="Password" :required="true" />
                        <TextInput v-model="form.password" type="password" class="mt-1 block w-full" autocomplete="new-password" required />
                        <InputError :message="form.errors.password" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Confirm password" :required="true" />
                        <TextInput v-model="form.password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" required />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <Link href="/admin/users" class="px-4 py-2 text-sm text-ink-muted hover:text-ink">Cancel</Link>
                    <button type="submit" :disabled="form.processing"
                        class="px-5 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-semibold rounded-lg disabled:opacity-60">
                        {{ form.processing ? 'Creating…' : 'Create user' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
