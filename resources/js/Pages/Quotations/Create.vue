<script setup>
import { computed, watch } from 'vue';
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
    vendors: { type: Array, default: () => [] },
    prefill: { type: Object, default: () => ({}) },
});

const p = props.prefill ?? {};

const form = useForm({
    client_id: p.client_id ?? '',
    venue_id: p.venue_id ?? '',
    package_id: p.package_id ?? '',
    event_date: p.event_date ?? '',
    guest_count: p.guest_count ?? '',
    inquiry_id: p.inquiry_id ?? null,
    // Items can be managed (venue/package), vendor-sourced, or custom.
    items: [],
    discount_amount: 0,
    tax_rate: 10,
    valid_until: '',
    notes: '',
    terms_and_conditions: '50% deposit required to confirm the booking. Balance due 14 days before the event.',
});

const customSuggestions = ['Surprise dance', 'Drama', 'Short video', 'Advertisement', 'MC / Host', 'Fireworks', 'Photo booth', 'Live band'];

const vendorGroups = computed(() => {
    const groups = {};
    for (const v of props.vendors) {
        (groups[v.category] ??= []).push(v);
    }
    return groups;
});

// ----- Managed venue/package rows (auto-priced from the selection) -----
const isWeekend = (dateStr) => {
    if (!dateStr) return false;
    const day = new Date(`${dateStr}T00:00:00`).getDay();
    return day === 0 || day === 6;
};

function buildManaged(type) {
    if (type === 'venue' && form.venue_id) {
        const v = props.venues.find((x) => x.id === Number(form.venue_id));
        if (!v) return null;
        const weekend = isWeekend(form.event_date) && Number(v.weekend_surcharge) > 0;
        const price = (Number(v.base_price) || 0) + (weekend ? Number(v.weekend_surcharge) || 0 : 0);
        return {
            name: `Venue Hire — ${v.name}`,
            description: weekend ? 'Includes weekend surcharge' : '',
            quantity: 1,
            unit_price: price,
            type: 'venue',
            vendor_id: null,
        };
    }
    if (type === 'package' && form.package_id) {
        const pk = props.packages.find((x) => x.id === Number(form.package_id));
        if (!pk) return null;
        return {
            name: `Package — ${pk.name}`,
            description: '',
            quantity: 1,
            unit_price: Number(pk.base_price) || 0,
            type: 'package',
            vendor_id: null,
        };
    }
    return null;
}

function syncManaged(type) {
    const existing = form.items.findIndex((it) => it.type === type);
    if (existing >= 0) form.items.splice(existing, 1);

    const payload = buildManaged(type);
    if (!payload) return;

    // Keep venue first, package right after it, everything else below.
    let pos = 0;
    if (type === 'package') {
        const vi = form.items.findIndex((it) => it.type === 'venue');
        pos = vi >= 0 ? vi + 1 : 0;
    }
    form.items.splice(pos, 0, payload);
}

watch(() => [form.venue_id, form.event_date], () => syncManaged('venue'), { immediate: true });
watch(() => form.package_id, () => syncManaged('package'), { immediate: true });

// ----- Manual line items -----
function addVendorItem() {
    form.items.push({ name: '', description: '', quantity: 1, unit_price: 0, type: 'vendor', vendor_id: '' });
}
function addCustomItem() {
    form.items.push({ name: '', description: '', quantity: 1, unit_price: 0, type: 'custom', vendor_id: null });
}
function onVendorSelect(item) {
    const v = props.vendors.find((x) => x.id === Number(item.vendor_id));
    if (!v) return;
    item.name = v.name;
    item.unit_price = Number(v.base_rate) || 0;
    item.description = [v.category, (v.rate_type || '').replace('_', ' ')].filter(Boolean).join(' · ');
}
function removeItem(i) {
    form.items.splice(i, 1);
}

const typeLabel = (t) => ({ venue: 'Venue', package: 'Package', vendor: 'Vendor', custom: 'Custom' }[t] ?? 'Custom');
const typeClass = (t) => ({
    venue: 'bg-primary/10 text-primary',
    package: 'bg-amber-100 text-amber-700',
    vendor: 'bg-emerald-100 text-emerald-700',
    custom: 'bg-surface-sunken text-ink-subtle',
}[t] ?? 'bg-surface-sunken text-ink-subtle');

const lineTotal = (item) => (Number(item.quantity) || 0) * (Number(item.unit_price) || 0);
const subtotal = computed(() => form.items.reduce((s, i) => s + lineTotal(i), 0));
const tax = computed(() => (subtotal.value - (Number(form.discount_amount) || 0)) * ((Number(form.tax_rate) || 0) / 100));
const grandTotal = computed(() => subtotal.value - (Number(form.discount_amount) || 0) + tax.value);
const money = (n) => 'LKR ' + Number(n ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 });

function submit() {
    form.post('/admin/quotations');
}
</script>

<template>
    <AppLayout title="New Quotation">
        <div class="max-w-3xl mx-auto space-y-4">
            <div class="flex items-center gap-3">
                <Link href="/admin/quotations" class="text-ink-subtle hover:text-ink-muted">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <h2 class="text-xl font-semibold text-ink">New Quotation</h2>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Details -->
                <div class="bg-surface rounded-lg shadow-sm p-6 space-y-5">
                    <div>
                        <InputLabel for="client_id" value="Client *" />
                        <select id="client_id" v-model="form.client_id" required
                            class="mt-1 block w-full border-border rounded-md shadow-sm text-sm focus:border-primary focus:ring-primary">
                            <option value="" disabled>Select a client</option>
                            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <InputError :message="form.errors.client_id" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="venue_id" value="Venue" />
                            <select id="venue_id" v-model="form.venue_id"
                                class="mt-1 block w-full border-border rounded-md shadow-sm text-sm focus:border-primary focus:ring-primary">
                                <option value="">No venue</option>
                                <option v-for="v in venues" :key="v.id" :value="v.id">{{ v.name }}</option>
                            </select>
                            <p class="mt-1 text-xs text-ink-subtle">Adds an editable "Venue Hire" line at the venue's base price.</p>
                        </div>
                        <div>
                            <InputLabel for="package_id" value="Package" />
                            <select id="package_id" v-model="form.package_id"
                                class="mt-1 block w-full border-border rounded-md shadow-sm text-sm focus:border-primary focus:ring-primary">
                                <option value="">No package</option>
                                <option v-for="pk in packages" :key="pk.id" :value="pk.id">{{ pk.name }}</option>
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
                <div class="bg-surface rounded-lg shadow-sm p-6 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-ink">Line Items</h3>
                        <div class="flex gap-2">
                            <button type="button" @click="addVendorItem" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium">+ Add from vendor</button>
                            <button type="button" @click="addCustomItem" class="text-sm text-primary hover:text-primary-dark font-medium">+ Add custom item</button>
                        </div>
                    </div>
                    <InputError :message="form.errors.items" class="mt-1" />

                    <p v-if="!form.items.length" class="text-sm text-ink-subtle py-4 text-center">
                        Select a venue or package above, or add a vendor / custom item to begin.
                    </p>

                    <div v-for="(item, i) in form.items" :key="i" class="grid grid-cols-12 gap-2 items-start">
                        <div class="col-span-12 sm:col-span-5 space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="shrink-0 inline-flex px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wide" :class="typeClass(item.type)">{{ typeLabel(item.type) }}</span>
                                <select v-if="item.type === 'vendor'" v-model.number="item.vendor_id" @change="onVendorSelect(item)"
                                    class="flex-1 border-border rounded-md text-sm focus:border-primary focus:ring-primary">
                                    <option value="" disabled>Select a vendor…</option>
                                    <optgroup v-for="(list, cat) in vendorGroups" :key="cat" :label="cat">
                                        <option v-for="v in list" :key="v.id" :value="v.id">{{ v.name }}</option>
                                    </optgroup>
                                </select>
                            </div>
                            <input v-model="item.name" type="text" placeholder="Item name *" :list="item.type === 'custom' ? 'customSuggestions' : undefined"
                                class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary" />
                            <input v-model="item.description" type="text" placeholder="Description (optional)"
                                class="w-full border-border rounded-md text-xs text-ink-muted focus:border-primary focus:ring-primary" />
                        </div>
                        <div class="col-span-4 sm:col-span-2">
                            <input v-model.number="item.quantity" type="number" min="0" placeholder="Qty"
                                class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary" />
                        </div>
                        <div class="col-span-4 sm:col-span-2">
                            <input v-model.number="item.unit_price" type="number" min="0" step="0.01" placeholder="Price"
                                class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary" />
                        </div>
                        <div class="col-span-3 sm:col-span-2 text-right text-sm text-ink-muted pt-2">{{ money(lineTotal(item)) }}</div>
                        <div class="col-span-1 pt-1.5">
                            <button type="button" @click="removeItem(i)" class="text-red-400 hover:text-red-600" title="Remove">&times;</button>
                        </div>
                    </div>

                    <datalist id="customSuggestions">
                        <option v-for="s in customSuggestions" :key="s" :value="s" />
                    </datalist>

                    <!-- Totals -->
                    <div class="border-t border-border pt-3 flex justify-end">
                        <div class="w-full max-w-xs space-y-2 text-sm">
                            <div class="flex justify-between items-center">
                                <span class="text-ink-subtle">Subtotal</span>
                                <span class="text-ink">{{ money(subtotal) }}</span>
                            </div>
                            <div class="flex justify-between items-center gap-2">
                                <span class="text-ink-subtle">Discount (Rs)</span>
                                <input v-model.number="form.discount_amount" type="number" min="0" step="0.01"
                                    class="w-24 border-border rounded-md text-sm text-right focus:border-primary focus:ring-primary" />
                            </div>
                            <div class="flex justify-between items-center gap-2">
                                <span class="text-ink-subtle">Tax (%)</span>
                                <input v-model.number="form.tax_rate" type="number" min="0" max="100" step="0.1"
                                    class="w-24 border-border rounded-md text-sm text-right focus:border-primary focus:ring-primary" />
                            </div>
                            <div class="flex justify-between"><span class="text-ink-subtle">Tax amount</span><span class="text-ink">{{ money(tax) }}</span></div>
                            <div class="flex justify-between border-t border-border pt-2 font-semibold">
                                <span class="text-ink">Total</span><span class="text-primary">{{ money(grandTotal) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes / terms -->
                <div class="bg-surface rounded-lg shadow-sm p-6 space-y-4">
                    <div>
                        <InputLabel for="notes" value="Notes" />
                        <textarea id="notes" v-model="form.notes" rows="2"
                            class="mt-1 block w-full border-border rounded-md shadow-sm text-sm focus:border-primary focus:ring-primary" />
                    </div>
                    <div>
                        <InputLabel for="terms" value="Terms &amp; Conditions" />
                        <textarea id="terms" v-model="form.terms_and_conditions" rows="3"
                            class="mt-1 block w-full border-border rounded-md shadow-sm text-sm focus:border-primary focus:ring-primary" />
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <Link href="/admin/quotations" class="px-4 py-2 text-sm text-ink-muted border border-border rounded-lg hover:text-ink">Cancel</Link>
                    <PrimaryButton :disabled="form.processing">{{ form.processing ? 'Saving...' : 'Create Quotation' }}</PrimaryButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
