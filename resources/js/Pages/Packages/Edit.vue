<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({ package: Object });

const form = useForm({
    name: props.package.name,
    slug: props.package.slug,
    description: props.package.description ?? '',
    base_price: props.package.base_price,
    min_guests: props.package.guests?.min ?? '',
    max_guests: props.package.guests?.max ?? '',
    included_services: props.package.included_services ? [...props.package.included_services] : [],
    status: props.package.status,
});

const serviceInput = ref('');

function addService() {
    const val = serviceInput.value.trim();
    if (val && !form.included_services.includes(val)) form.included_services.push(val);
    serviceInput.value = '';
}

function removeService(i) { form.included_services.splice(i, 1); }

function submit() { form.put(`/admin/packages/${props.package.id}`); }
</script>

<template>
    <AppLayout title="Edit Package">
        <div class="max-w-2xl mx-auto space-y-4">
            <div class="flex items-center gap-3">
                <Link href="/admin/packages" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <h2 class="text-xl font-semibold text-gray-900">Edit Package</h2>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                <div>
                    <InputLabel for="name" value="Package Name *" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.name" class="mt-1" />
                </div>
                <div>
                    <InputLabel for="slug" value="URL Slug" />
                    <TextInput id="slug" v-model="form.slug" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.slug" class="mt-1" />
                </div>
                <div>
                    <InputLabel for="description" value="Description" />
                    <textarea id="description" v-model="form.description" rows="4"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>
                <div>
                    <InputLabel for="base_price" value="Base Price ($) *" />
                    <TextInput id="base_price" v-model="form.base_price" type="number" min="0" step="0.01" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.base_price" class="mt-1" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="min_guests" value="Min Guests" />
                        <TextInput id="min_guests" v-model="form.min_guests" type="number" min="1" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel for="max_guests" value="Max Guests" />
                        <TextInput id="max_guests" v-model="form.max_guests" type="number" min="1" class="mt-1 block w-full" />
                    </div>
                </div>
                <div>
                    <InputLabel value="Included Services" />
                    <div class="flex gap-2 mt-1">
                        <input v-model="serviceInput" type="text" @keydown.enter.prevent="addService"
                            placeholder="Add a service..."
                            class="flex-1 border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <button type="button" @click="addService" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-md">Add</button>
                    </div>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <span v-for="(svc, i) in form.included_services" :key="i"
                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-50 text-indigo-700 text-sm rounded-full">
                            {{ svc }}
                            <button type="button" @click="removeService(i)" class="text-indigo-400 hover:text-indigo-700 ml-0.5">×</button>
                        </span>
                    </div>
                </div>
                <div>
                    <InputLabel for="status" value="Status" />
                    <select id="status" v-model="form.status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
                <div class="flex items-center justify-between pt-2">
                    <Link href="/admin/packages" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:text-gray-800">Cancel</Link>
                    <PrimaryButton :disabled="form.processing">{{ form.processing ? 'Saving...' : 'Save Changes' }}</PrimaryButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
