<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({ profile: Object });

const profileForm = useForm({
    _method: 'post',
    name: props.profile.name ?? '',
    email: props.profile.email ?? '',
    phone: props.profile.phone ?? '',
    avatar: null,
    remove_avatar: false,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const showCurrent = ref(false);
const showNew = ref(false);

// Live preview: new local file if chosen, else the stored avatar (unless removed).
const previewUrl = ref(props.profile.avatar ?? null);

function onAvatarChange(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    profileForm.avatar = file;
    profileForm.remove_avatar = false;
    previewUrl.value = URL.createObjectURL(file);
}

function removeAvatar() {
    profileForm.avatar = null;
    profileForm.remove_avatar = true;
    previewUrl.value = null;
}

const initial = computed(() => profileForm.name?.charAt(0)?.toUpperCase() ?? '?');

function saveProfile() {
    profileForm.post('/admin/profile', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            profileForm.avatar = null;
            profileForm.remove_avatar = false;
        },
    });
}

function savePassword() {
    passwordForm.put('/admin/profile/password', {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}

function resendVerification() {
    router.post('/admin/profile/verification-notification', {}, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="My Profile">
        <div class="max-w-3xl mx-auto space-y-6">
            <div>
                <h2 class="text-xl font-semibold text-ink">My Profile</h2>
                <p class="text-sm text-ink-subtle mt-0.5">Manage your account details and password.</p>
            </div>

            <!-- Email verification banner -->
            <div v-if="!profile.email_verified" class="bg-amber-50 border border-amber-200 rounded-lg p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="flex-1">
                    <p class="text-sm font-medium text-amber-800">Your email address is not verified.</p>
                    <p class="text-xs text-amber-700 mt-0.5">Verify it to secure your account and receive notifications.</p>
                </div>
                <button type="button" @click="resendVerification"
                    class="text-sm font-semibold text-amber-800 underline hover:text-amber-900 whitespace-nowrap">
                    Resend link
                </button>
            </div>

            <!-- Profile information -->
            <form @submit.prevent="saveProfile" class="bg-surface rounded-lg shadow-sm p-6 space-y-5">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-full bg-primary flex items-center justify-center text-white text-2xl font-medium overflow-hidden">
                        <img v-if="previewUrl" :src="previewUrl" alt="" class="w-full h-full object-cover" />
                        <span v-else>{{ initial }}</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-ink">Profile information</h3>
                        <p class="text-xs text-ink-subtle capitalize mb-2">Role: {{ profile.role }}</p>
                        <div class="flex items-center gap-3">
                            <label class="cursor-pointer text-xs font-semibold text-primary hover:text-primary-dark">
                                <span>Change photo</span>
                                <input type="file" accept="image/*" class="hidden" @change="onAvatarChange" />
                            </label>
                            <button v-if="previewUrl" type="button" @click="removeAvatar"
                                class="text-xs font-semibold text-red-500 hover:text-red-700">Remove</button>
                        </div>
                        <InputError :message="profileForm.errors.avatar" class="mt-1" />
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
            <form @submit.prevent="savePassword" class="bg-surface rounded-lg shadow-sm p-6 space-y-5">
                <div>
                    <h3 class="text-sm font-semibold text-ink">Update password</h3>
                    <p class="text-xs text-ink-subtle mt-0.5">Use a strong password you don't reuse elsewhere.</p>
                </div>

                <div>
                    <InputLabel value="Current password" :required="true" />
                    <div class="relative">
                        <TextInput v-model="passwordForm.current_password" :type="showCurrent ? 'text' : 'password'" class="mt-1 block w-full pr-11" autocomplete="current-password" />
                        <button type="button" @click="showCurrent = !showCurrent" class="absolute inset-y-0 right-0 mt-1 flex items-center px-3 text-ink-subtle hover:text-ink-muted">
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
                            <button type="button" @click="showNew = !showNew" class="absolute inset-y-0 right-0 mt-1 flex items-center px-3 text-ink-subtle hover:text-ink-muted">
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
