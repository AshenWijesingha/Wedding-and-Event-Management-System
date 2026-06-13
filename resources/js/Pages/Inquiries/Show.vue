<script setup>
import { ref } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    inquiry: Object,
    venues: Array,
    packages: Array,
    users: Array,
});

const statusForm = useForm({
    status: props.inquiry.status,
    assigned_to: props.inquiry.assigned_to,
    notes: props.inquiry.notes ?? '',
});

function updateStatus() {
    statusForm.patch(`/admin/inquiries/${props.inquiry.id}`, {
        preserveScroll: true,
    });
}

const statusColors = {
    pending: 'bg-yellow-100 text-yellow-700',
    contacted: 'bg-blue-100 text-blue-700',
    qualified: 'bg-primary/10 text-primary-dark',
    proposal_sent: 'bg-purple-100 text-purple-700',
    converted: 'bg-green-100 text-green-700',
    closed: 'bg-surface-sunken text-ink-muted',
};
</script>

<template>
    <AppLayout :title="`Inquiry ${inquiry.inquiry_number}`">
        <div class="max-w-4xl mx-auto space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link href="/admin/inquiries" class="text-ink-subtle hover:text-ink-muted">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </Link>
                    <div>
                        <h2 class="text-xl font-semibold text-ink">{{ inquiry.inquiry_number }}</h2>
                        <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize', statusColors[inquiry.status] ?? 'bg-surface-sunken']">
                            {{ inquiry.status?.replace('_', ' ') }}
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="`/admin/inquiries/${inquiry.id}/print`" target="_blank" rel="noopener"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-surface border border-border text-ink-muted text-sm font-medium rounded-lg hover:bg-surface-muted">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print
                    </a>
                    <a :href="`/admin/inquiries/${inquiry.id}/pdf`"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-surface border border-border text-ink-muted text-sm font-medium rounded-lg hover:bg-surface-muted">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Download PDF
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Inquiry details -->
                <div class="lg:col-span-2 space-y-4">
                    <!-- Client info -->
                    <div class="bg-surface rounded-lg shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-ink mb-3">Client Information</h3>
                        <dl class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-ink-subtle">Name</dt>
                                <dd class="font-medium">{{ inquiry.client?.full_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-subtle">Email</dt>
                                <dd>{{ inquiry.client?.email }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-subtle">Phone</dt>
                                <dd>{{ inquiry.client?.phone ?? 'â€”' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Event details -->
                    <div class="bg-surface rounded-lg shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-ink mb-3">Event Details</h3>
                        <dl class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-ink-subtle">Event Type</dt>
                                <dd class="font-medium capitalize">{{ inquiry.event_type }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-subtle">Preferred Date</dt>
                                <dd>{{ inquiry.preferred_date }}</dd>
                            </div>
                            <div v-if="inquiry.alternate_date">
                                <dt class="text-ink-subtle">Alternate Date</dt>
                                <dd>{{ inquiry.alternate_date }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-subtle">Guest Count</dt>
                                <dd>{{ inquiry.guest_count }}</dd>
                            </div>
                            <div v-if="inquiry.budget?.min || inquiry.budget?.max">
                                <dt class="text-ink-subtle">Budget Range</dt>
                                <dd>LKR {{ inquiry.budget.min?.toLocaleString() }} â€“ LKR {{ inquiry.budget.max?.toLocaleString() }}</dd>
                            </div>
                            <div v-if="inquiry.venue">
                                <dt class="text-ink-subtle">Venue Interest</dt>
                                <dd>{{ inquiry.venue?.name }}</dd>
                            </div>
                        </dl>
                        <div v-if="inquiry.message" class="mt-4 pt-4 border-t border-border">
                            <dt class="text-ink-subtle text-sm mb-1">Message</dt>
                            <dd class="text-sm text-ink-muted">{{ inquiry.message }}</dd>
                        </div>
                    </div>
                </div>

                <!-- Actions panel -->
                <div class="space-y-4">
                    <form @submit.prevent="updateStatus" class="bg-surface rounded-lg shadow-sm p-5 space-y-4">
                        <h3 class="text-sm font-semibold text-ink">Update Status</h3>

                        <div>
                            <label class="block text-xs text-ink-subtle mb-1">Status</label>
                            <select v-model="statusForm.status" class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary">
                                <option value="pending">Pending</option>
                                <option value="contacted">Contacted</option>
                                <option value="qualified">Qualified</option>
                                <option value="proposal_sent">Proposal Sent</option>
                                <option value="converted">Converted</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>

                        <div v-if="users?.length">
                            <label class="block text-xs text-ink-subtle mb-1">Assigned To</label>
                            <select v-model="statusForm.assigned_to" class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary">
                                <option :value="null">Unassigned</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs text-ink-subtle mb-1">Notes</label>
                            <textarea v-model="statusForm.notes" rows="3" class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary resize-none" />
                        </div>

                        <button type="submit" :disabled="statusForm.processing"
                            class="w-full py-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-lg disabled:opacity-50">
                            Save Changes
                        </button>
                    </form>

                    <!-- Quick actions -->
                    <div class="bg-surface rounded-lg shadow-sm p-5 space-y-2">
                        <h3 class="text-sm font-semibold text-ink mb-3">Quick Actions</h3>
                        <Link :href="`/admin/quotations/create?inquiry_id=${inquiry.id}`" class="block w-full text-center py-2 border border-primary text-primary text-sm font-medium rounded-lg hover:bg-primary/5">
                            Create Quotation
                        </Link>
                        <Link href="/admin/bookings/create" class="block w-full text-center py-2 border border-border text-ink-muted text-sm font-medium rounded-lg hover:bg-surface-muted">
                            Create Booking
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
