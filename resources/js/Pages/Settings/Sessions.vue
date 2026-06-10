<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    supported: Boolean,
    sessions: { type: Array, default: () => [] },
    basePath: { type: String, required: true },
});

const revoke = (id) => {
    if (!confirm('Sign out this session? That device will need to log in again.')) return;
    router.delete(`${props.basePath}/${id}`, { preserveScroll: true });
};

const revokeOthers = () => {
    if (!confirm('Sign out of all other devices? Only this session will remain.')) return;
    router.delete(props.basePath, { preserveScroll: true });
};
</script>

<template>
    <AppLayout title="Active Sessions">
        <div class="max-w-3xl space-y-6">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Active Sessions</h2>
                        <p class="text-sm text-gray-500 mt-1">
                            Devices currently signed in to your account. If you see something you
                            don't recognise, sign it out.
                        </p>
                    </div>
                    <button
                        v-if="supported && sessions.length > 1"
                        @click="revokeOthers"
                        class="shrink-0 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-100"
                    >
                        Log out other devices
                    </button>
                </div>
            </div>

            <div v-if="!supported" class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800">
                Session listing is unavailable because the app is not using the database session
                driver. Set <code>SESSION_DRIVER=database</code> to enable device management.
            </div>

            <div v-else class="bg-white rounded-lg shadow-sm divide-y divide-gray-100">
                <div
                    v-for="s in sessions"
                    :key="s.id"
                    class="flex items-center justify-between gap-4 px-6 py-4"
                >
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900">
                            {{ s.device }}
                            <span v-if="s.is_current" class="ml-2 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">
                                This device
                            </span>
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ s.ip || 'Unknown IP' }} · Last active {{ s.last_active }}
                        </p>
                    </div>
                    <button
                        v-if="!s.is_current"
                        @click="revoke(s.id)"
                        class="shrink-0 text-sm font-medium text-red-500 hover:text-red-700"
                    >
                        Sign out
                    </button>
                </div>

                <div v-if="!sessions.length" class="px-6 py-10 text-center text-sm text-gray-400">
                    No active sessions found.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
