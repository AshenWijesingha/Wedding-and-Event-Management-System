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
    city: '',
    address: '',
    description: '',
    star_rating: '',
    status: 'active',
});

function submit() {
    form.post('/admin/hotels', {
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <AppLayout title="Add Hotel">
        <div class="max-w-2xl mx-auto space-y-4">
            <!-- Header -->
            <div class="flex items-center gap-3">
                <Link href="/admin/hotels" class="text-ink-subtle hover:text-ink-muted">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <h2 class="text-xl font-semibold text-ink">Add Hotel</h2>
            </div>

            <form @submit.prevent="submit" class="bg-surface rounded-lg shadow-sm p-6 space-y-5">
                <!-- Name -->
                <div>
                    <InputLabel for="name" value="Hotel Name *" />
                    <TextInput
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="Grand Colombo Hotel"
                        required
                    />
                    <InputError :message="form.errors.name" class="mt-1" />
                </div>

                <!-- City -->
                <div>
                    <InputLabel for="city" value="City" />
                    <TextInput
                        id="city"
                        v-model="form.city"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="Colombo"
                    />
                    <InputError :message="form.errors.city" class="mt-1" />
                </div>

                <!-- Address -->
                <div>
                    <InputLabel for="address" value="Address" />
                    <TextInput
                        id="address"
                        v-model="form.address"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="123 Galle Road, Colombo 03"
                    />
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
                        placeholder="Describe the hotel..."
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

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <Link href="/admin/hotels" class="px-4 py-2 text-sm text-ink-muted hover:text-ink border border-border rounded-lg">
                        Cancel
                    </Link>
                    <PrimaryButton :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Create Hotel' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
