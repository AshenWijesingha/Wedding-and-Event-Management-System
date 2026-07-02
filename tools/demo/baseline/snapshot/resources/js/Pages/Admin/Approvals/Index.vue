<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({ items: Array });

const approve = (i) => router.post(`/admin/approvals/${i.type}/${i.id}/approve`);
const reject = (i) => {
    const notes = window.prompt('Reason for rejection:');
    if (notes) router.post(`/admin/approvals/${i.type}/${i.id}/reject`, { notes });
};
</script>

<template>
    <AppLayout title="Approvals">
        <div class="max-w-5xl mx-auto space-y-4">
            <h2 class="text-xl font-semibold text-ink">Pending Approvals</h2>
            <div class="bg-surface rounded-lg shadow-sm divide-y divide-border">
                <div v-for="i in items" :key="i.type + '-' + i.id" class="flex items-center justify-between p-4">
                    <div>
                        <span class="text-xs uppercase tracking-wide text-ink-subtle">{{ i.type }}</span>
                        <div class="font-medium text-ink">{{ i.name }}</div>
                        <div class="text-sm text-ink-subtle">
                            by {{ i.submitted_by ?? '—' }}
                            <span v-if="i.changes_pending_review" class="text-amber-600">· changes pending review</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button @click="approve(i)" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">Approve</button>
                        <button @click="reject(i)" class="text-sm font-medium text-red-600 hover:text-red-700">Reject</button>
                    </div>
                </div>
                <p v-if="!items.length" class="p-6 text-center text-ink-subtle text-sm">Nothing awaiting review.</p>
            </div>
        </div>
    </AppLayout>
</template>
