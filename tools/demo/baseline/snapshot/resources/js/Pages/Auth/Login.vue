<script setup>
import { ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    canResetPassword: Boolean,
    status: String,
    demoEmail: String,
    demoPassword: String,
    superAdminEmail: String,
    superAdminPassword: String,
    canUseDemo: { type: Boolean, default: true },
});

const form = useForm({ email: '', password: '', remember: false });

const showPassword = ref(false);

const submit = () => form.post('/login', { onFinish: () => form.reset('password') });

const fillDemo = () => {
    form.email = props.demoEmail || 'admin@demo.eventpro.test';
    form.password = props.demoPassword || 'password';
    form.remember = true;
};

const fillSuperAdminDemo = () => {
    form.email = props.superAdminEmail || 'admin@eventpro.io';
    form.password = props.superAdminPassword || 'password';
    form.remember = true;
};
</script>

<template>
    <GuestLayout>
        <Head title="Sign In" />

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">{{ status }}</div>

        <p class="text-[0.68rem] font-medium uppercase tracking-[0.3em] text-[#c8a96a]">Welcome back</p>
        <h2 class="font-display mt-2 mb-7 text-3xl font-light text-[#1a1512]">Sign in to your account</h2>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel value="Email address" :required="true" />
                <TextInput v-model="form.email" type="email" class="mt-1 block w-full" autofocus autocomplete="username" />
                <InputError :message="form.errors.email" />
            </div>

            <div>
                <InputLabel value="Password" :required="true" />
                <div class="relative">
                    <TextInput v-model="form.password" :type="showPassword ? 'text' : 'password'" class="mt-1 block w-full pr-11" autocomplete="current-password" />
                    <button type="button" @click="showPassword = !showPassword"
                        :aria-label="showPassword ? 'Hide password' : 'Show password'"
                        class="absolute inset-y-0 right-0 mt-1 flex items-center px-3 text-[#9a8f80] transition-colors hover:text-[#1a1512]">
                        <svg v-if="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </button>
                </div>
                <InputError :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <input v-model="form.remember" type="checkbox" class="rounded border-border shadow-sm focus:ring-2" style="color: var(--color-primary);" />
                    <span class="ml-2 text-sm text-[#5c5246]">Remember me</span>
                </label>
                <Link v-if="canResetPassword" href="/forgot-password" class="text-sm font-medium text-[#9a7b3f] hover:text-[#1a1512]">
                    Forgot password?
                </Link>
            </div>

            <button type="submit" :disabled="form.processing"
                class="inline-flex w-full items-center justify-center gap-2 rounded-full py-3 text-sm font-semibold text-white shadow-lg shadow-black/10 transition-transform duration-300 hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-70"
                style="background-color: var(--color-primary);">
                <svg v-if="form.processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                {{ form.processing ? 'Signing in…' : 'Sign in' }}
                <svg v-if="!form.processing" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </button>
        </form>

        <!-- Quick demo access -->
        <div v-if="canUseDemo" class="mt-6 rounded-xl border border-dashed border-[#c8a96a]/50 bg-[#c8a96a]/5 px-4 py-3">
            <p class="text-xs leading-relaxed text-[#7a6f61]">
                <span class="font-medium text-[#1a1512]">Reviewing the demo?</span><br>
                Prefill a sample account, then press Sign in.
            </p>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <button type="button" @click="fillDemo"
                    class="rounded-full border border-[#9a7b3f]/40 px-4 py-1.5 text-xs font-semibold text-[#9a7b3f] transition-colors hover:bg-[#9a7b3f] hover:text-white">
                    Tenant admin
                </button>
                <button type="button" @click="fillSuperAdminDemo"
                    class="rounded-full border border-[#1a1512]/40 px-4 py-1.5 text-xs font-semibold text-[#1a1512] transition-colors hover:bg-[#1a1512] hover:text-white">
                    Super admin
                </button>
            </div>
        </div>

        <p class="mt-7 text-center text-sm text-[#5c5246]">
            No account?
            <Link href="/register" class="font-medium text-[#9a7b3f] hover:text-[#1a1512]">Register here</Link>
        </p>
    </GuestLayout>
</template>
