<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    venues: Array,
    packages: Array,
    clients: Array,
});

const form = useForm({
    client_id: '',
    venue_id: '',
    package_id: '',
    event_type: 'wedding',
    event_date: '',
    setup_time: '',
    event_start_time: '',
    event_end_time: '',
    guest_count: '',
    total_amount: '',
    paid_amount: '',
    status: 'tentative',
    notes: '',
});

const eventTypes = ['wedding', 'corporate', 'birthday', 'anniversary', 'conference', 'other'];

function onPackageChange() {
    const pkg = props.packages.find(p => p.id === Number(form.package_id));
    if (pkg && !form.total_amount) form.total_amount = pkg.base_price;
}

function submit() {
    form.post('/admin/bookings');
}
</script>

<template>
    <AppLayout title="New Booking">
        <div class="max-w-2xl mx-auto space-y-4">
            <div class="flex items-center gap-3">
                <Link href="/admin/bookings" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <h2 class="text-xl font-semibold text-gray-900">New Booking</h2>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                <!-- Client -->
                <div>
                    <InputLabel for="client_id" value="Client *" />
                    <select id="client_id" v-model="form.client_id" required
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="" disabled>Select a client</option>
                        <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                    <InputError :message="form.errors.client_id" class="mt-1" />
                </div>

                <!-- Venue + Package -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="venue_id" value="Venue *" />
                        <select id="venue_id" v-model="form.venue_id" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="" disabled>Select a venue</option>
                            <option v-for="v in venues" :key="v.id" :value="v.id">{{ v.name }}</option>
                        </select>
                        <InputError :message="form.errors.venue_id" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="package_id" value="Package" />
                        <select id="package_id" v-model="form.package_id" @change="onPackageChange"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">No package</option>
                            <option v-for="p in packages" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                        <InputError :message="form.errors.package_id" class="mt-1" />
                    </div>
                </div>

                <!-- Event type + date -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="event_type" value="Event Type *" />
                        <select id="event_type" v-model="form.event_type"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm capitalize focus:border-indigo-500 focus:ring-indigo-500">
                            <option v-for="t in eventTypes" :key="t" :value="t">{{ t }}</option>
                        </select>
                        <InputError :message="form.errors.event_type" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="event_date" value="Event Date *" />
                        <TextInput id="event_date" v-model="form.event_date" type="date" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.event_date" class="mt-1" />
                    </div>
                </div>

                <!-- Times -->
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <InputLabel for="setup_time" value="Setup" />
                        <TextInput id="setup_time" v-model="form.setup_time" type="time" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel for="event_start_time" value="Start" />
                        <TextInput id="event_start_time" v-model="form.event_start_time" type="time" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel for="event_end_time" value="End" />
                        <TextInput id="event_end_time" v-model="form.event_end_time" type="time" class="mt-1 block w-full" />
                    </div>
                </div>

                <!-- Guests + amounts -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <InputLabel for="guest_count" value="Guests *" />
                        <TextInput id="guest_count" v-model="form.guest_count" type="number" min="1" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.guest_count" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="total_amount" value="Total ($) *" />
                        <TextInput id="total_amount" v-model="form.total_amount" type="number" min="0" step="0.01" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.total_amount" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="paid_amount" value="Paid ($)" />
                        <TextInput id="paid_amount" v-model="form.paid_amount" type="number" min="0" step="0.01" class="mt-1 block w-full" />
                        <InputError :message="form.errors.paid_amount" class="mt-1" />
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <InputLabel for="status" value="Status" />
                    <select id="status" v-model="form.status"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="tentative">Tentative</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                    </select>
                </div>

                <!-- Notes -->
                <div>
                    <InputLabel for="notes" value="Notes" />
                    <textarea id="notes" v-model="form.notes" rows="3"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>

                <div class="flex items-center justify-between pt-2">
                    <Link href="/admin/bookings" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:text-gray-800">Cancel</Link>
                    <PrimaryButton :disabled="form.processing">{{ form.processing ? 'Saving...' : 'Create Booking' }}</PrimaryButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
