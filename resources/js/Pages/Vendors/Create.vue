<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const form = useForm({
    name: '',
    category: '',
    contact_name: '',
    email: '',
    phone: '',
    website: '',
    description: '',
    base_rate: '',
    rate_type: 'fixed',
    services: [],
    status: 'active',
    notes: '',
});

const serviceInput = ref('');

function addService() {
    const s = serviceInput.value.trim();
    if (s && !form.services.includes(s)) {
        form.services.push(s);
    }
    serviceInput.value = '';
}

function removeService(i) {
    form.services.splice(i, 1);
}

function submit() {
    form.post('/admin/vendors');
}

const categories = ['photographer', 'caterer', 'florist', 'music', 'decor', 'transport', 'videographer', 'other'];
</script>

<template>
    <AppLayout title="Add Vendor">
        <div class="max-w-2xl mx-auto space-y-4">
            <div class="flex items-center gap-3">
                <Link href="/admin/vendors" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <h2 class="text-xl font-semibold text-gray-900">Add Vendor</h2>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-lg shadow-sm p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vendor Name *</label>
                        <input v-model="form.name" type="text" class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                        <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                        <select v-model="form.category" class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="">Select category</option>
                            <option v-for="cat in categories" :key="cat" :value="cat" class="capitalize">{{ cat }}</option>
                        </select>
                        <p v-if="form.errors.category" class="text-red-500 text-xs mt-1">{{ form.errors.category }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select v-model="form.status" class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contact Name</label>
                        <input v-model="form.contact_name" type="text" class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input v-model="form.email" type="email" class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input v-model="form.phone" type="text" class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                        <input v-model="form.website" type="url" class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Base Rate ($)</label>
                        <input v-model="form.base_rate" type="number" min="0" step="0.01" class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rate Type</label>
                        <select v-model="form.rate_type" class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="fixed">Fixed</option>
                            <option value="hourly">Hourly</option>
                            <option value="per_event">Per Event</option>
                        </select>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea v-model="form.description" rows="3" class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500 resize-none"></textarea>
                    </div>

                    <!-- Services -->
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Services Offered</label>
                        <div class="flex gap-2 mb-2">
                            <input v-model="serviceInput" type="text" placeholder="Add a service..." @keydown.enter.prevent="addService"
                                class="flex-1 border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <button type="button" @click="addService" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-sm rounded-md">Add</button>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="(s, i) in form.services" :key="i"
                                class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-50 text-indigo-700 text-xs rounded-full">
                                {{ s }}
                                <button type="button" @click="removeService(i)" class="hover:text-indigo-900">&times;</button>
                            </span>
                        </div>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea v-model="form.notes" rows="2" class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500 resize-none"></textarea>
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" :disabled="form.processing"
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg disabled:opacity-50">
                        Create Vendor
                    </button>
                    <Link href="/admin/vendors" class="px-5 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50">Cancel</Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
