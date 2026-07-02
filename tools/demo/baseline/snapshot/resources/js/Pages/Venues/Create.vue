<script setup>
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const form = useForm({
    name: '',
    slug: '',
    description: '',
    capacity_min: '',
    capacity_max: '',
    base_price: '',
    weekend_surcharge: '',
    amenities: [],
    status: 'active',
});

const amenityInput = ref('');
import { ref } from 'vue';

function addAmenity() {
    const val = amenityInput.value.trim();
    if (val && !form.amenities.includes(val)) {
        form.amenities.push(val);
    }
    amenityInput.value = '';
}

function removeAmenity(index) {
    form.amenities.splice(index, 1);
}

function autoSlug() {
    if (!form.slug) {
        form.slug = form.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    }
}

function submit() {
    form.post('/admin/venues', {
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <AppLayout title="Add Venue">
        <div class="max-w-2xl mx-auto space-y-4">
            <!-- Header -->
            <div class="flex items-center gap-3">
                <Link href="/admin/venues" class="text-ink-subtle hover:text-ink-muted">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <h2 class="text-xl font-semibold text-ink">Add Venue</h2>
            </div>

            <form @submit.prevent="submit" class="bg-surface rounded-lg shadow-sm p-6 space-y-5">
                <!-- Name -->
                <div>
                    <InputLabel for="name" value="Venue Name *" />
                    <TextInput
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="Grand Ballroom"
                        @blur="autoSlug"
                        required
                    />
                    <InputError :message="form.errors.name" class="mt-1" />
                </div>

                <!-- Slug -->
                <div>
                    <InputLabel for="slug" value="URL Slug" />
                    <TextInput
                        id="slug"
                        v-model="form.slug"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="grand-ballroom"
                    />
                    <p class="text-xs text-ink-subtle mt-1">Auto-generated from name if left blank.</p>
                    <InputError :message="form.errors.slug" class="mt-1" />
                </div>

                <!-- Description -->
                <div>
                    <InputLabel for="description" value="Description" />
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        class="mt-1 block w-full border-border rounded-md shadow-sm text-sm focus:border-primary focus:ring-primary"
                        placeholder="Describe the venue..."
                    />
                    <InputError :message="form.errors.description" class="mt-1" />
                </div>

                <!-- Capacity -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="capacity_min" value="Min Capacity" />
                        <TextInput id="capacity_min" v-model="form.capacity_min" type="number" min="1" class="mt-1 block w-full" placeholder="50" />
                        <InputError :message="form.errors.capacity_min" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="capacity_max" value="Max Capacity" />
                        <TextInput id="capacity_max" v-model="form.capacity_max" type="number" min="1" class="mt-1 block w-full" placeholder="500" />
                        <InputError :message="form.errors.capacity_max" class="mt-1" />
                    </div>
                </div>

                <!-- Pricing -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="base_price" value="Base Price ($)" />
                        <TextInput id="base_price" v-model="form.base_price" type="number" min="0" step="0.01" class="mt-1 block w-full" placeholder="5000" />
                        <InputError :message="form.errors.base_price" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel for="weekend_surcharge" value="Weekend Surcharge ($)" />
                        <TextInput id="weekend_surcharge" v-model="form.weekend_surcharge" type="number" min="0" step="0.01" class="mt-1 block w-full" placeholder="500" />
                        <InputError :message="form.errors.weekend_surcharge" class="mt-1" />
                    </div>
                </div>

                <!-- Amenities -->
                <div>
                    <InputLabel value="Amenities" />
                    <div class="flex gap-2 mt-1">
                        <input
                            v-model="amenityInput"
                            type="text"
                            @keydown.enter.prevent="addAmenity"
                            placeholder="e.g. Parking, WiFi, Stage..."
                            class="flex-1 border-border rounded-md shadow-sm text-sm focus:border-primary focus:ring-primary"
                        />
                        <button type="button" @click="addAmenity" class="px-3 py-2 bg-surface-sunken hover:bg-surface-sunken text-ink-muted text-sm rounded-md">Add</button>
                    </div>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <span
                            v-for="(amenity, i) in form.amenities"
                            :key="i"
                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/5 text-primary-dark text-sm rounded-full"
                        >
                            {{ amenity }}
                            <button type="button" @click="removeAmenity(i)" class="text-primary hover:text-primary-dark ml-0.5">×</button>
                        </span>
                    </div>
                    <InputError :message="form.errors.amenities" class="mt-1" />
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
                        <option value="maintenance">Maintenance</option>
                    </select>
                    <InputError :message="form.errors.status" class="mt-1" />
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <Link href="/admin/venues" class="px-4 py-2 text-sm text-ink-muted hover:text-ink border border-border rounded-lg">
                        Cancel
                    </Link>
                    <PrimaryButton :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Create Venue' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
