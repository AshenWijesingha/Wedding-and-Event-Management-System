<script setup>
import { useForm, router } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    hotel: Object,
    venues: Array,
    packages: Array,
});

const form = useForm({
    name: props.hotel.name,
    city: props.hotel.city ?? '',
    address: props.hotel.address ?? '',
    description: props.hotel.description ?? '',
    star_rating: props.hotel.star_rating ?? '',
    status: props.hotel.status ?? 'active',
});

function submit() {
    form.put(`/admin/hotels/${props.hotel.slug}`);
}

const badge = (s) => ({
    draft: 'bg-surface-sunken text-ink-subtle',
    pending: 'bg-amber-100 text-amber-700',
    approved: 'bg-emerald-100 text-emerald-700',
    rejected: 'bg-red-100 text-red-700',
}[s] ?? 'bg-surface-sunken text-ink-subtle');

const submitVenue = (v) => router.post(`/admin/venues/${v.slug}/submit`);
const submitPackage = (p) => router.post(`/admin/packages/${p.slug}/submit`);
</script>

<template>
    <AppLayout title="Edit Hotel">
        <div class="max-w-2xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-3">
                <Link href="/admin/hotels" class="text-ink-subtle hover:text-ink-muted">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <h2 class="text-xl font-semibold text-ink">Edit Hotel</h2>
                <span class="ml-auto px-2 py-0.5 rounded text-xs font-semibold" :class="badge(hotel.approval_status)">
                    {{ hotel.approval_status }}<span v-if="hotel.changes_pending_review"> · changes pending</span>
                </span>
            </div>

            <!-- Hotel details form -->
            <form @submit.prevent="submit" class="bg-surface rounded-lg shadow-sm p-6 space-y-5">
                <!-- Name -->
                <div>
                    <InputLabel for="name" value="Hotel Name *" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.name" class="mt-1" />
                </div>

                <!-- City -->
                <div>
                    <InputLabel for="city" value="City" />
                    <TextInput id="city" v-model="form.city" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.city" class="mt-1" />
                </div>

                <!-- Address -->
                <div>
                    <InputLabel for="address" value="Address" />
                    <TextInput id="address" v-model="form.address" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.address" class="mt-1" />
                </div>

                <!-- Description -->
                <div>
                    <InputLabel for="description" value="Description" />
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        class="mt-1 block w-full border-border rounded-md shadow-sm text-sm focus:border-primary focus:ring-primary"
                    />
                    <InputError :message="form.errors.description" class="mt-1" />
                </div>

                <!-- Star Rating -->
                <div>
                    <InputLabel for="star_rating" value="Star Rating" />
                    <select
                        id="star_rating"
                        v-model="form.star_rating"
                        class="mt-1 block w-full border-border rounded-md shadow-sm text-sm focus:border-primary focus:ring-primary"
                    >
                        <option value="">Select rating</option>
                        <option value="1">1 Star</option>
                        <option value="2">2 Stars</option>
                        <option value="3">3 Stars</option>
                        <option value="4">4 Stars</option>
                        <option value="5">5 Stars</option>
                    </select>
                    <InputError :message="form.errors.star_rating" class="mt-1" />
                </div>

                <!-- Status -->
                <div>
                    <InputLabel for="status" value="Status" />
                    <select
                        id="status"
                        v-model="form.status"
                        class="mt-1 block w-full border-border rounded-md shadow-sm text-sm focus:border-primary focus:ring-primary"
                    >
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <InputError :message="form.errors.status" class="mt-1" />
                </div>

                <!-- Rejection notes -->
                <div v-if="hotel.approval_status === 'rejected' && hotel.review_notes" class="p-3 bg-red-50 border border-red-200 rounded-md">
                    <p class="text-sm text-red-600"><span class="font-semibold">Rejection reason:</span> {{ hotel.review_notes }}</p>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between pt-2">
                    <Link href="/admin/hotels" class="px-4 py-2 text-sm text-ink-muted hover:text-ink border border-border rounded-lg">
                        Cancel
                    </Link>
                    <PrimaryButton :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </PrimaryButton>
                </div>
            </form>

            <!-- Venues -->
            <div class="bg-surface rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-border">
                    <h3 class="text-base font-semibold text-ink">Venues</h3>
                </div>
                <div class="divide-y divide-border">
                    <div v-for="v in venues" :key="v.id" class="flex items-center justify-between px-6 py-4">
                        <div>
                            <div class="font-medium text-ink">{{ v.name }}</div>
                            <p v-if="v.approval_status === 'rejected' && v.review_notes" class="mt-0.5 text-xs text-red-600">
                                Rejected: {{ v.review_notes }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold" :class="badge(v.approval_status)">
                                {{ v.approval_status }}
                            </span>
                            <button
                                v-if="['draft', 'rejected'].includes(v.approval_status)"
                                @click="submitVenue(v)"
                                class="text-sm font-medium text-amber-600 hover:text-amber-700"
                            >Submit</button>
                        </div>
                    </div>
                    <p v-if="!venues?.length" class="px-6 py-4 text-sm text-ink-subtle">No venues attached to this hotel.</p>
                </div>
            </div>

            <!-- Packages -->
            <div class="bg-surface rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-border">
                    <h3 class="text-base font-semibold text-ink">Packages</h3>
                </div>
                <div class="divide-y divide-border">
                    <div v-for="p in packages" :key="p.id" class="flex items-center justify-between px-6 py-4">
                        <div>
                            <div class="font-medium text-ink">{{ p.name }}</div>
                            <p v-if="p.approval_status === 'rejected' && p.review_notes" class="mt-0.5 text-xs text-red-600">
                                Rejected: {{ p.review_notes }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold" :class="badge(p.approval_status)">
                                {{ p.approval_status }}
                            </span>
                            <button
                                v-if="['draft', 'rejected'].includes(p.approval_status)"
                                @click="submitPackage(p)"
                                class="text-sm font-medium text-amber-600 hover:text-amber-700"
                            >Submit</button>
                        </div>
                    </div>
                    <p v-if="!packages?.length" class="px-6 py-4 text-sm text-ink-subtle">No packages attached to this hotel.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
