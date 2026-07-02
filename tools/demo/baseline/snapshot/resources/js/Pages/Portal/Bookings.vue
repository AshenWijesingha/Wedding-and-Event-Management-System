<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { StatusBadge, EmptyState, Button } from '@/Components/ui';

const props = defineProps({ bookings: Object, filters: Object });

const status = ref(props.filters?.status ?? '');

watch(status, () => {
    router.get('/portal/bookings', { status: status.value }, { preserveState: true, replace: true });
});
</script>

<template>
    <PortalLayout title="My Bookings">
        <div class="max-w-3xl mx-auto space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-xl font-semibold text-ink">My Bookings</h2>
                <select v-model="status" class="rounded-lg border-border px-3 py-2 text-sm focus:border-primary focus:ring-primary">
                    <option value="">All statuses</option>
                    <option value="tentative">Tentative</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <div v-if="bookings.data?.length" class="space-y-3">
                <div v-for="b in bookings.data" :key="b.id" class="bg-surface border border-border rounded-lg shadow-sm p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between">
                        <div>
                            <Link :href="`/portal/bookings/${b.id}`" class="font-semibold text-primary hover:underline">{{ b.booking_number }}</Link>
                            <p class="text-sm text-ink-muted mt-0.5">{{ b.venue?.name }}</p>
                        </div>
                        <StatusBadge :status="b.status" />
                    </div>
                    <div class="mt-3 grid grid-cols-3 gap-3 text-sm">
                        <div>
                            <p class="text-xs text-ink-subtle">Event Date</p>
                            <p class="font-medium text-ink">{{ b.event?.date }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-ink-subtle">Guests</p>
                            <p class="font-medium text-ink">{{ b.event?.guest_count }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-ink-subtle">Balance Due</p>
                            <p :class="(b.financial?.balance ?? 0) > 0 ? 'font-semibold text-danger' : 'font-semibold text-success'">
                                LKR {{ b.financial?.balance?.toLocaleString() ?? 0 }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="bg-surface border border-border rounded-lg shadow-sm">
                <EmptyState title="No bookings found" description="Browse venues to start your first booking.">
                    <template #action>
                        <Button href="/venues" variant="secondary" size="sm">Browse venues →</Button>
                    </template>
                </EmptyState>
            </div>

            <div v-if="bookings.meta?.last_page > 1" class="flex items-center justify-between text-sm text-ink-muted">
                <span>Showing {{ bookings.meta.from }}–{{ bookings.meta.to }} of {{ bookings.meta.total }}</span>
                <div class="flex gap-2">
                    <Link v-if="bookings.links?.prev" :href="bookings.links.prev" class="px-3 py-1 border border-border rounded-lg hover:bg-surface-sunken">Previous</Link>
                    <Link v-if="bookings.links?.next" :href="bookings.links.next" class="px-3 py-1 border border-border rounded-lg hover:bg-surface-sunken">Next</Link>
                </div>
            </div>
        </div>
    </PortalLayout>
</template>
