<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({ tenant: Object, plans: Array, statuses: Array, owners: Array });

const form = useForm({
    name: props.tenant.name,
    slug: props.tenant.slug,
    domain: props.tenant.domain ?? '',
    plan_id: props.tenant.plan_id,
    status: props.tenant.status,
    email: props.tenant.email ?? '',
    phone: props.tenant.phone ?? '',
    primary_color: props.tenant.primary_color ?? '#6366f1',
    trial_ends_at: props.tenant.trial_ends_at ?? '',
});

function submit() {
    form.put(`/admin/tenants/${props.tenant.id}`);
}
</script>

<template>
    <AppLayout title="Edit Tenant">
        <div class="max-w-2xl mx-auto space-y-5">
            <div class="flex items-center gap-3">
                <Link href="/admin/tenants" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <h2 class="text-xl font-semibold text-gray-900">Edit {{ tenant.name }}</h2>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Name" :required="true" />
                        <TextInput v-model="form.name" type="text" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Slug" :required="true" />
                        <TextInput v-model="form.slug" type="text" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.slug" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Custom domain" />
                        <TextInput v-model="form.domain" type="text" class="mt-1 block w-full" />
                        <InputError :message="form.errors.domain" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Plan" />
                        <select v-model="form.plan_id" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            <option :value="null">No plan</option>
                            <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                        <InputError :message="form.errors.plan_id" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Status" :required="true" />
                        <select v-model="form.status" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm capitalize">
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

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
                    <Link href="/admin/tenants" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</Link>
                    <button type="submit" :disabled="form.processing"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg disabled:opacity-60">
                        {{ form.processing ? 'Saving…' : 'Save changes' }}
                    </button>
                </div>
            </form>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Tenant Owners</h3>
                <ul v-if="owners?.length" class="divide-y divide-gray-100">
                    <li v-for="o in owners" :key="o.id" class="py-2 flex justify-between text-sm">
                        <span class="font-medium text-gray-800">{{ o.name }}</span>
                        <span class="text-gray-500">{{ o.email }}</span>
                    </li>
                </ul>
                <p v-else class="text-sm text-gray-400">No owner assigned.</p>
            </div>
        </div>
    </AppLayout>
</template>
