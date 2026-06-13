<script setup>
import { watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({ plans: Array, statuses: Array });

const form = useForm({
    name: '',
    slug: '',
    domain: '',
    plan_id: props.plans[0]?.id ?? null,
    status: 'trial',
    email: '',
    phone: '',
    primary_color: '#6366f1',
    trial_ends_at: '',
    owner_name: '',
    owner_email: '',
    owner_password: '',
    owner_password_confirmation: '',
});

// Auto-suggest slug from name until the user edits slug directly.
let slugTouched = false;
watch(() => form.name, (name) => {
    if (!slugTouched) {
        form.slug = name.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    }
});

function submit() {
    form.post('/admin/tenants');
}
</script>

<template>
    <AppLayout title="Add Tenant">
        <div class="max-w-2xl mx-auto space-y-5">
            <div class="flex items-center gap-3">
                <Link href="/admin/tenants" class="text-ink-subtle hover:text-ink-muted">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <h2 class="text-xl font-semibold text-ink">Add Tenant</h2>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div class="bg-surface rounded-lg shadow-sm p-6 space-y-5">
                    <h3 class="text-sm font-semibold text-ink">Organization</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Name" :required="true" />
                            <TextInput v-model="form.name" type="text" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.name" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Slug" :required="true" />
                            <TextInput v-model="form.slug" type="text" class="mt-1 block w-full" required @input="slugTouched = true" />
                            <InputError :message="form.errors.slug" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Custom domain" />
                            <TextInput v-model="form.domain" type="text" class="mt-1 block w-full" placeholder="events.hotel.com" />
                            <InputError :message="form.errors.domain" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Plan" />
                            <select v-model="form.plan_id" class="mt-1 block w-full border border-border rounded-md px-3 py-2 text-sm">
                                <option :value="null">No plan</option>
                                <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                            <InputError :message="form.errors.plan_id" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Status" :required="true" />
                            <select v-model="form.status" class="mt-1 block w-full border border-border rounded-md px-3 py-2 text-sm capitalize">
                                <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                            </select>
                            <InputError :message="form.errors.status" class="mt-1" />
                        </div>
                        <div v-if="form.status === 'trial'">
                            <InputLabel value="Trial ends" />
                            <TextInput v-model="form.trial_ends_at" type="date" class="mt-1 block w-full" />
                            <InputError :message="form.errors.trial_ends_at" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Contact email" />
                            <TextInput v-model="form.email" type="email" class="mt-1 block w-full" />
                            <InputError :message="form.errors.email" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Contact phone" />
                            <TextInput v-model="form.phone" type="text" class="mt-1 block w-full" />
                            <InputError :message="form.errors.phone" class="mt-1" />
                        </div>
                    </div>
                </div>

                <div class="bg-surface rounded-lg shadow-sm p-6 space-y-5">
                    <div>
                        <h3 class="text-sm font-semibold text-ink">Tenant Owner</h3>
                        <p class="text-xs text-ink-subtle mt-0.5">This account has full control of the tenant.</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Owner name" :required="true" />
                            <TextInput v-model="form.owner_name" type="text" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.owner_name" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Owner email" :required="true" />
                            <TextInput v-model="form.owner_email" type="email" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.owner_email" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Password" :required="true" />
                            <TextInput v-model="form.owner_password" type="password" class="mt-1 block w-full" autocomplete="new-password" required />
                            <InputError :message="form.errors.owner_password" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Confirm password" :required="true" />
                            <TextInput v-model="form.owner_password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" required />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <Link href="/admin/tenants" class="px-4 py-2 text-sm text-ink-muted hover:text-ink">Cancel</Link>
                    <button type="submit" :disabled="form.processing"
                        class="px-5 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-semibold rounded-lg disabled:opacity-60">
                        {{ form.processing ? 'Creating…' : 'Create tenant' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
