<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({ profile: Object });

const profileForm = useForm({
    name: props.profile.name ?? '',
    email: props.profile.email ?? '',
    phone: props.profile.phone ?? '',
    avatar: props.profile.avatar ?? '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const showCurrent = ref(false);
const showNew = ref(false);

function saveProfile() {
    profileForm.patch('/admin/profile', { preserveScroll: true });
}

function savePassword() {
    passwordForm.put('/admin/profile/password', {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}
</script>

<template>
    <AppLayout title="My Profile">
        <div class="max-w-3xl mx-auto space-y-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">My Profile</h2>
                <p class="text-sm text-gray-500 mt-0.5">Manage your account details and password.</p>
            </div>

            <!-- Profile information -->
            <form @submit.prevent="saveProfile" class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-indigo-500 flex items-center justify-center text-white text-xl font-medium overflow-hidden">
                        <img v-if="profileForm.avatar" :src="profileForm.avatar" alt="" class="w-full h-full object-cover" />
                        <span v-else>{{ profileForm.name?.charAt(0)?.toUpperCase() }}</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Profile information</h3>
                        <p class="text-xs text-gray-500 capitalize">Role: {{ profile.role }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Full name" :required="true" />
                        <TextInput v-model="profileForm.name" type="text" class="mt-1 block w-full" required />
                        <InputError :message="profileForm.errors.name" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Email address" :required="true" />
                        <TextInput v-model="profileForm.email" type="email" class="mt-1 block w-full" required />
                        <InputError :message="profileForm.errors.email" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Phone" />
                        <TextInput v-model="profileForm.phone" type="text" class="mt-1 block w-full" />
                        <InputError :message="profileForm.errors.phone" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Avatar URL" />
                        <TextInput v-model="profileForm.avatar" type="url" class="mt-1 block w-full" placeholder="https://…" />
                        <InputError :message="profileForm.errors.avatar" class="mt-1" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <transition enter-active-class="transition" enter-from-class="opacity-0" leave-active-class="transition" leave-to-class="opacity-0">
                        <span v-if="profileForm.recentlySuccessful" class="text-sm text-green-600">Saved.</span>
                    </transition>
                    <button type="submit" :disabled="profileForm.processing"
                        class="px-5 py-2 rounded-lg text-sm font-semibold text-white disabled:opacity-60"
                        style="background-color: var(--color-primary);">
                        {{ profileForm.processing ? 'Saving…' : 'Save changes' }}
                    </button>
                </div>
            </form>

            <!-- Update password -->
            <form @submit.prevent="savePassword" class="bg-white rounded-lg shadow-sm p-6 space-y-5">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Update password</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Use a strong password you don't reuse elsewhere.</p>
                </div>

                <div>
                    <InputLabel value="Current password" :required="true" />
                    <div class="relative">
                        <TextInput v-model="passwordForm.current_password" :type="showCurrent ? 'text' : 'password'" class="mt-1 block w-full pr-11" autocomplete="current-password" />
                        <button type="button" @click="showCurrent = !showCurrent" class="absolute inset-y-0 right-0 mt-1 flex items-center px-3 text-gray-400 hover:text-gray-700">
                            <span class="text-xs">{{ showCurrent ? 'Hide' : 'Show' }}</span>
                        </button>
                    </div>
                    <InputError :message="passwordForm.errors.current_password" class="mt-1" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="New password" :required="true" />
                        <div class="relative">
                            <TextInput v-model="passwordForm.password" :type="showNew ? 'text' : 'password'" class="mt-1 block w-full pr-11" autocomplete="new-password" />
                            <button type="button" @click="showNew = !showNew" class="absolute inset-y-0 right-0 mt-1 flex items-center px-3 text-gray-400 hover:text-gray-700">
                                <span class="text-xs">{{ showNew ? 'Hide' : 'Show' }}</span>
                            </button>
                        </div>
                        <InputError :message="passwordForm.errors.password" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Confirm new password" :required="true" />
                        <TextInput v-model="passwordForm.password_confirmation" :type="showNew ? 'text' : 'password'" class="mt-1 block w-full" autocomplete="new-password" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <transition enter-active-class="transition" enter-from-class="opacity-0" leave-active-class="transition" leave-to-class="opacity-0">
                        <span v-if="passwordForm.recentlySuccessful" class="text-sm text-green-600">Password updated.</span>
                    </transition>
                    <button type="submit" :disabled="passwordForm.processing"
                        class="px-5 py-2 rounded-lg text-sm font-semibold text-white disabled:opacity-60"
                        style="background-color: var(--color-primary);">
                        {{ passwordForm.processing ? 'Updating…' : 'Update password' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
