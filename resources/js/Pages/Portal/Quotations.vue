<script setup>
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { StatusBadge, EmptyState } from '@/Components/ui';

defineProps({ quotations: Object });
</script>

<template>
    <PortalLayout title="My Quotations">
        <div class="max-w-3xl mx-auto space-y-4">
            <h2 class="font-display text-xl font-semibold text-ink">My Quotations</h2>

            <div v-if="quotations.data?.length" class="space-y-3">
                <div v-for="q in quotations.data" :key="q.id" class="bg-surface border border-border rounded-lg shadow-sm p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-semibold text-ink">{{ q.quotation_number }}</p>
                            <p class="text-sm text-ink-subtle mt-0.5">{{ q.venue?.name }}</p>
                        </div>
                        <StatusBadge :status="q.status" />
                    </div>
                    <div class="mt-3 grid grid-cols-3 gap-3 text-sm">
                        <div>
                            <p class="text-xs text-ink-subtle">Total</p>
                            <p class="font-semibold text-ink">LKR {{ q.total?.toLocaleString() ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-ink-subtle">Event Date</p>
                            <p class="text-ink">{{ q.event_date ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-ink-subtle">Valid Until</p>
                            <p class="text-ink">{{ q.valid_until ?? '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="bg-surface border border-border rounded-lg shadow-sm">
                <EmptyState title="No quotations yet" description="Quotations from your event company will appear here." />
            </div>
        </div>
    </PortalLayout>
</template>
