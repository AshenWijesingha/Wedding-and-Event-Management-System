<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({ settings: Object, plans: Array });

const form = useForm({
    platform_name: props.settings.platform_name ?? '',
    default_plan_id: props.settings.default_plan_id ?? '',
    signups_enabled: !!props.settings.signups_enabled,
    support_email: props.settings.support_email ?? '',
});

function submit() {
    form.put('/admin/platform-settings', { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Platform Settings">
        <div class="max-w-2xl mx-auto space-y-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Platform Settings</h2>
                <p class="text-sm text-gray-500 mt-0.5">Global configuration applied across the whole platform.</p>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                <div>
                    <InputLabel for="platform_name" value="Platform Name" />
                    <TextInput id="platform_name" v-model="form.platform_name" type="text" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.platform_name" class="mt-1" />
                </div>

                <div>
                    <InputLabel for="default_plan_id" value="Default Plan for New Tenants" />
                    <select id="default_plan_id" v-model="form.default_plan_id"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">None</option>
                        <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                    <InputError :message="form.errors.default_plan_id" class="mt-1" />
                </div>

                <div>
                    <InputLabel for="support_email" value="Support Email" />
                    <TextInput id="support_email" v-model="form.support_email" type="email" class="mt-1 block w-full" />
                    <InputError :message="form.errors.support_email" class="mt-1" />
                </div>

                <label class="flex items-center gap-2">
                    <input type="checkbox" v-model="form.signups_enabled"
                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                    <span class="text-sm text-gray-700">Allow public sign-ups</span>
                </label>

                <div class="flex justify-end">
                    <PrimaryButton :disabled="form.processing">{{ form.processing ? 'Saving...' : 'Save Settings' }}</PrimaryButton>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
