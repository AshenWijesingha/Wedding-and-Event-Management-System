<script setup>
import { Link } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    form: { type: Object, required: true },
    limitKeys: { type: Array, default: () => [] },
    submitLabel: { type: String, default: 'Save' },
});

const emit = defineEmits(['submit']);

function addFeature() {
    props.form.features.push('');
}
function removeFeature(i) {
    props.form.features.splice(i, 1);
}
</script>

<template>
    <form @submit.prevent="emit('submit')" class="space-y-5">
        <div class="bg-surface rounded-lg shadow-sm p-6 space-y-5">
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
                    <InputLabel value="Monthly price" :required="true" />
                    <TextInput v-model="form.price_monthly" type="number" step="0.01" min="0" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.price_monthly" class="mt-1" />
                </div>
                <div>
                    <InputLabel value="Yearly price" :required="true" />
                    <TextInput v-model="form.price_yearly" type="number" step="0.01" min="0" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.price_yearly" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <InputLabel value="Description" />
                    <textarea v-model="form.description" rows="2" class="mt-1 block w-full border border-border rounded-md px-3 py-2 text-sm resize-none" />
                    <InputError :message="form.errors.description" class="mt-1" />
                </div>
                <div>
                    <InputLabel value="Sort order" />
                    <TextInput v-model="form.sort_order" type="number" min="0" class="mt-1 block w-full" />
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm text-ink-muted">
                        <input v-model="form.is_active" type="checkbox" class="rounded border-border text-primary focus:ring-primary" />
                        Active (offered to tenants)
                    </label>
                </div>
            </div>
        </div>

        <div class="bg-surface rounded-lg shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-ink">Features</h3>
                <button type="button" @click="addFeature" class="text-sm text-primary hover:text-primary-dark">+ Add feature</button>
            </div>
            <div v-for="(f, i) in form.features" :key="i" class="flex items-center gap-2">
                <TextInput v-model="form.features[i]" type="text" class="block w-full" placeholder="e.g. Unlimited reports" />
                <button type="button" @click="removeFeature(i)" class="text-ink-subtle hover:text-red-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <p v-if="!form.features.length" class="text-sm text-ink-subtle">No features added.</p>
        </div>

        <div class="bg-surface rounded-lg shadow-sm p-6 space-y-4">
            <h3 class="text-sm font-semibold text-ink">Limits</h3>
            <p class="text-xs text-ink-subtle -mt-2">Leave blank for unlimited.</p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div v-for="key in limitKeys" :key="key">
                    <InputLabel :value="`Max ${key}`" class="capitalize" />
                    <TextInput v-model="form.limits[key]" type="number" min="0" class="mt-1 block w-full" placeholder="Unlimited" />
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <Link href="/admin/plans" class="px-4 py-2 text-sm text-ink-muted hover:text-ink">Cancel</Link>
            <button type="submit" :disabled="form.processing"
                class="px-5 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-semibold rounded-lg disabled:opacity-60">
                {{ form.processing ? 'Saving…' : submitLabel }}
            </button>
        </div>
    </form>
</template>
