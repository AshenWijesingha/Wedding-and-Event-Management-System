<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ tenant: Object, settings: Object });

const activeTab = ref('general');

// General form
const generalForm = useForm({
    name:          props.tenant?.name ?? '',
    email:         props.tenant?.email ?? '',
    phone:         props.tenant?.phone ?? '',
    primary_color: props.tenant?.primary_color ?? '#6366f1',
});

// Branding form
const branding = props.settings?.branding ?? {};
const brandingForm = useForm({
    company_name:  branding.company_name ?? '',
    tagline:       branding.tagline ?? '',
    primary_color: branding.primary_color ?? '#6366f1',
    font_family:   branding.font_family ?? 'Inter',
    logo_url:      branding.logo_url ?? '',
});

// Email templates form
const defaultTemplates = [
    { key: 'booking_confirmed', subject: 'Your Booking is Confirmed', body: 'Dear {client_name},\n\nYour booking {booking_number} has been confirmed.\n\nEvent Date: {event_date}\nVenue: {venue_name}\n\nThank you for choosing us!' },
    { key: 'payment_received', subject: 'Payment Received', body: 'Dear {client_name},\n\nWe have received your payment of {amount} for booking {booking_number}.\n\nThank you!' },
    { key: 'payment_reminder', subject: 'Payment Reminder for {booking_number}', body: 'Dear {client_name},\n\nThis is a reminder that a payment of {amount} is due on {due_date}.\n\nPlease contact us if you have any questions.' },
];

const savedTemplates = props.settings?.email_templates ?? {};
const emailTemplates = ref(defaultTemplates.map(t => ({
    ...t,
    subject: savedTemplates[t.key]?.subject ?? t.subject,
    body:    savedTemplates[t.key]?.body ?? t.body,
})));

function saveEmailTemplates() {
    useForm({ templates: emailTemplates.value }).post('/admin/settings/email-templates', {
        preserveScroll: true,
    });
}

const tabs = [
    { id: 'general', label: 'General' },
    { id: 'branding', label: 'Branding' },
    { id: 'email', label: 'Email Templates' },
];
</script>

<template>
    <AppLayout title="Settings">
        <div class="max-w-3xl mx-auto space-y-4">
            <h2 class="text-xl font-semibold text-gray-900">Settings</h2>

            <!-- Tab nav -->
            <div class="border-b border-gray-200 flex gap-4">
                <button v-for="tab in tabs" :key="tab.id"
                    @click="activeTab = tab.id"
                    :class="[
                        'pb-3 text-sm font-medium border-b-2 -mb-px transition-colors',
                        activeTab === tab.id ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'
                    ]">
                    {{ tab.label }}
                </button>
            </div>

            <!-- General -->
            <form v-if="activeTab === 'general'" @submit.prevent="generalForm.post('/admin/settings/general', { preserveScroll: true })"
                class="bg-white rounded-lg shadow-sm p-6 space-y-4">
                <h3 class="text-sm font-semibold text-gray-900">General Settings</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Business Name *</label>
                        <input v-model="generalForm.name" type="text" class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input v-model="generalForm.email" type="email" class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input v-model="generalForm.phone" type="text" class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Brand Color</label>
                        <div class="flex items-center gap-2">
                            <input v-model="generalForm.primary_color" type="color" class="h-9 w-12 border-gray-300 rounded cursor-pointer" />
                            <input v-model="generalForm.primary_color" type="text" class="flex-1 border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                    </div>
                </div>
                <button type="submit" :disabled="generalForm.processing"
                    class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg disabled:opacity-50">
                    Save General Settings
                </button>
            </form>

            <!-- Branding -->
            <form v-if="activeTab === 'branding'" @submit.prevent="brandingForm.post('/admin/settings/branding', { preserveScroll: true })"
                class="bg-white rounded-lg shadow-sm p-6 space-y-4">
                <h3 class="text-sm font-semibold text-gray-900">Branding Settings</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
                        <input v-model="brandingForm.company_name" type="text" class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tagline</label>
                        <input v-model="brandingForm.tagline" type="text" class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Primary Color</label>
                        <div class="flex items-center gap-2">
                            <input v-model="brandingForm.primary_color" type="color" class="h-9 w-12 border-gray-300 rounded cursor-pointer" />
                            <input v-model="brandingForm.primary_color" type="text" class="flex-1 border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Font Family</label>
                        <select v-model="brandingForm.font_family" class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="Inter">Inter</option>
                            <option value="Roboto">Roboto</option>
                            <option value="Open Sans">Open Sans</option>
                            <option value="Lato">Lato</option>
                            <option value="Poppins">Poppins</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Logo URL</label>
                        <input v-model="brandingForm.logo_url" type="url" placeholder="https://..."
                            class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div v-if="brandingForm.logo_url" class="col-span-2">
                        <p class="text-xs text-gray-500 mb-1">Preview:</p>
                        <img :src="brandingForm.logo_url" alt="Logo preview" class="h-12 object-contain rounded border border-gray-200" />
                    </div>
                </div>
                <button type="submit" :disabled="brandingForm.processing"
                    class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg disabled:opacity-50">
                    Save Branding
                </button>
            </form>

            <!-- Email Templates -->
            <div v-if="activeTab === 'email'" class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                <h3 class="text-sm font-semibold text-gray-900">Email Templates</h3>
                <p class="text-xs text-gray-500">Available variables: {client_name}, {booking_number}, {event_date}, {venue_name}, {amount}, {due_date}</p>
                <div v-for="(tpl, i) in emailTemplates" :key="tpl.key" class="border border-gray-200 rounded-lg p-4 space-y-3">
                    <p class="text-xs font-semibold text-gray-500 uppercase">{{ tpl.key.replace('_', ' ') }}</p>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Subject</label>
                        <input v-model="emailTemplates[i].subject" type="text"
                            class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Body</label>
                        <textarea v-model="emailTemplates[i].body" rows="4"
                            class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500 resize-none font-mono text-xs"></textarea>
                    </div>
                </div>
                <button @click="saveEmailTemplates"
                    class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg">
                    Save Email Templates
                </button>
            </div>
        </div>
    </AppLayout>
</template>
