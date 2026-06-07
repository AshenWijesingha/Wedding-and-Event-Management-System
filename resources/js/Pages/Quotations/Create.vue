<script setup>
import { computed } from 'vue';
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
    event_date: '',
    guest_count: '',
    items: [
        { name: '', description: '', quantity: 1, unit_price: 0 },
    ],
    discount_amount: 0,
    tax_rate: 10,
    valid_until: '',
    notes: '',
    terms_and_conditions: '50% deposit required to confirm the booking. Balance due 14 days before the event.',
});

function addItem() {
    form.items.push({ name: '', description: '', quantity: 1, unit_price: 0 });
}
function removeItem(i) {
    if (form.items.length > 1) form.items.splice(i, 1);
}

const lineTotal = (item) => (Number(item.quantity) || 0) * (Number(item.unit_price) || 0);
const subtotal = computed(() => form.items.reduce((s, i) => s + lineTotal(i), 0));
const tax = computed(() => (subtotal.value - (Number(form.discount_amount) || 0)) * ((Number(form.tax_rate) || 0) / 100));
const grandTotal = computed(() => subtotal.value - (Number(form.discount_amount) || 0) + tax.value);
const money = (n) => '$' + Number(n ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 });

function submit() {
    form.post('/admin/quotations');
}
</script>

<template>
    <AppLayout title="New Quotation">
        <div class="max-w-3xl mx-auto space-y-4">
            <div class="flex items-center gap-3">
                <Link href="/admin/quotations" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <h2 class="text-xl font-semibold text-gray-900">New Quotation</h2>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Details -->
                <div class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                    <div>
                        <InputLabel for="client_id" value="Client *" />
                        <select id="client_id" v-model="form.client_id" required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="" disabled>Select a client</option>
                            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <InputError :message="form.errors.client_id" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="venue_id" value="Venue" />
                            <select id="venue_id" v-model="form.venue_id"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">No venue</option>
                                <option v-for="v in venues" :key="v.id" :value="v.id">{{ v.name }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="package_id" value="Package" />
                            <select id="package_id" v-model="form.package_id"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">No package</option>
                                <option v-for="p in packages" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <InputLabel for="event_date" value="Event Date" />
                            <TextInput id="event_date" v-model="form.event_date" type="date" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel for="guest_count" value="Guests" />
                            <TextInput id="guest_count" v-model="form.guest_count" type="number" min="1" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel for="valid_until" value="Valid Until" />
                            <TextInput id="valid_until" v-model="form.valid_until" type="date" class="mt-1 block w-full" />
                        </div>
                    </div>
                </div>

                <!-- Line items -->
                <div class="bg-white rounded-lg shadow-sm p-6 space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900">Line Items</h3>
                        <button type="button" @click="addItem" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">+ Add item</button>
                    </div>
                    <InputError :message="form.errors.items" class="mt-1" />

                    <div v-for="(item, i) in form.items" :key="i" class="grid grid-cols-12 gap-2 items-start">
                        <div class="col-span-12 sm:col-span-5">
                            <input v-model="item.name" type="text" placeholder="Item name *"
                                class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div class="col-span-4 sm:col-span-2">
                            <input v-model.number="item.quantity" type="number" min="0" placeholder="Qty"
                                class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div class="col-span-4 sm:col-span-2">
                            <input v-model.number="item.unit_price" type="number" min="0" step="0.01" placeholder="Price"
                                class="w-full border-gray-300 rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div class="col-span-3 sm:col-span-2 text-right text-sm text-gray-700 pt-2">{{ money(lineTotal(item)) }}</div>
                        <div class="col-span-1 pt-1.5">
                            <button type="button" @click="removeItem(i)" class="text-red-400 hover:text-red-600" title="Remove">&times;</button>
                        </div>
                    </div>

                    <!-- Totals -->
                    <div class="border-t border-gray-200 pt-3 flex justify-end">
                        <div class="w-full max-w-xs space-y-2 text-sm">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500">Subtotal</span>
                                <span class="text-gray-900">{{ money(subtotal) }}</span>
                            </div>
                            <div class="flex justify-between items-center gap-2">
                                <span class="text-gray-500">Discount ($)</span>
                                <input v-model.number="form.discount_amount" type="number" min="0" step="0.01"
                                    class="w-24 border-gray-300 rounded-md text-sm text-right focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            <div class="flex justify-between items-center gap-2">
                                <span class="text-gray-500">Tax (%)</span>
                                <input v-model.number="form.tax_rate" type="number" min="0" max="100" step="0.1"
                                    class="w-24 border-gray-300 rounded-md text-sm text-right focus:border-indigo-500 focus:ring-indigo-500" />
                            </div>
                            <div class="flex justify-between"><span class="text-gray-500">Tax amount</span><span class="text-gray-900">{{ money(tax) }}</span></div>
                            <div class="flex justify-between border-t border-gray-200 pt-2 font-semibold">
                                <span class="text-gray-900">Total</span><span class="text-indigo-600">{{ money(grandTotal) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes / terms -->
                <div class="bg-white rounded-lg shadow-sm p-6 space-y-4">
                    <div>
                        <InputLabel for="notes" value="Notes" />
                        <textarea id="notes" v-model="form.notes" rows="2"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <InputLabel for="terms" value="Terms &amp; Conditions" />
                        <textarea id="terms" v-model="form.terms_and_conditions" rows="3"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <Link href="/admin/quotations" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:text-gray-800">Cancel</Link>
                    <PrimaryButton :disabled="form.processing">{{ form.processing ? 'Saving...' : 'Create Quotation' }}</PrimaryButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
