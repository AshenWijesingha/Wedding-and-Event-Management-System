<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

const props = defineProps({
    venue: Object,
    events: Array,
});

const statusColors = {
    pending: { bg: '#F3F4F6', text: '#4B5563', border: '#6B7280' },
    tentative: { bg: '#FEF3C7', text: '#92400E', border: '#F59E0B' },
    confirmed: { bg: '#D1FAE5', text: '#065F46', border: '#10B981' },
    in_progress: { bg: '#DBEAFE', text: '#1E40AF', border: '#3B82F6' },
    completed: { bg: '#F3F4F6', text: '#4B5563', border: '#9CA3AF' },
    cancelled: { bg: '#FEE2E2', text: '#991B1B', border: '#EF4444' },
};

const calendarOptions = ref({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay',
    },
    events: (props.events ?? []).map(e => ({
        id: e.id,
        title: `${e.booking_number} â€” ${e.client_name}`,
        start: e.event_date,
        allDay: true,
        backgroundColor: statusColors[e.status]?.bg ?? '#F3F4F6',
        textColor: statusColors[e.status]?.text ?? '#4B5563',
        borderColor: statusColors[e.status]?.border ?? '#9CA3AF',
        extendedProps: {
            booking_number: e.booking_number,
            client_name: e.client_name,
            guest_count: e.guest_count,
            status: e.status,
            event_type: e.event_type,
        },
    })),
    eventClick: handleEventClick,
    dateClick: handleDateClick,
    height: 'auto',
    fixedWeekCount: false,
});

const selectedEvent = ref(null);
const selectedDate = ref(null);

function handleEventClick({ event }) {
    selectedEvent.value = event.extendedProps;
    selectedDate.value = null;
}

function handleDateClick({ dateStr }) {
    selectedDate.value = dateStr;
    selectedEvent.value = null;
}

const statusLabels = {
    pending: 'Pending',
    tentative: 'Tentative',
    confirmed: 'Confirmed',
    in_progress: 'In Progress',
    completed: 'Completed',
    cancelled: 'Cancelled',
};
</script>

<template>
    <AppLayout :title="`${venue.name} â€” Availability`">
        <div class="space-y-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link href="/admin/venues" class="text-ink-subtle hover:text-ink-muted">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </Link>
                    <div>
                        <h2 class="text-xl font-semibold text-ink">{{ venue.name }}</h2>
                        <p class="text-sm text-ink-subtle">Availability Calendar</p>
                    </div>
                </div>
                <Link :href="`/admin/venues/${venue.slug}/edit`" class="text-sm text-primary hover:text-primary-dark font-medium">
                    Edit Venue
                </Link>
            </div>

            <div class="flex gap-4">
                <!-- Calendar -->
                <div class="flex-1 bg-surface rounded-lg shadow-sm p-4">
                    <!-- Legend -->
                    <div class="flex flex-wrap gap-3 mb-4">
                        <div v-for="(color, key) in statusColors" :key="key" class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-sm inline-block" :style="{ backgroundColor: color.border }"></span>
                            <span class="text-xs text-ink-muted">{{ statusLabels[key] }}</span>
                        </div>
                    </div>

                    <FullCalendar :options="calendarOptions" />
                </div>

                <!-- Side panel -->
                <div class="w-64 flex-shrink-0 space-y-3">
                    <!-- Event detail -->
                    <div v-if="selectedEvent" class="bg-surface rounded-lg shadow-sm p-4">
                        <h3 class="text-sm font-semibold text-ink mb-3">Booking Details</h3>
                        <dl class="space-y-2 text-sm">
                            <div>
                                <dt class="text-ink-subtle">Booking #</dt>
                                <dd class="font-medium text-ink">{{ selectedEvent.booking_number }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-subtle">Client</dt>
                                <dd class="font-medium text-ink">{{ selectedEvent.client_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-subtle">Event Type</dt>
                                <dd class="font-medium text-ink capitalize">{{ selectedEvent.event_type }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-subtle">Guests</dt>
                                <dd class="font-medium text-ink">{{ selectedEvent.guest_count }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-subtle">Status</dt>
                                <dd>
                                    <span
                                        class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium capitalize"
                                        :style="{ backgroundColor: statusColors[selectedEvent.status]?.bg, color: statusColors[selectedEvent.status]?.text }"
                                    >
                                        {{ statusLabels[selectedEvent.status] ?? selectedEvent.status }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                        <button @click="selectedEvent = null" class="mt-3 text-xs text-ink-subtle hover:text-ink-muted">Close Ã—</button>
                    </div>

                    <!-- Date selected -->
                    <div v-else-if="selectedDate" class="bg-surface rounded-lg shadow-sm p-4">
                        <h3 class="text-sm font-semibold text-ink mb-2">{{ selectedDate }}</h3>
                        <p class="text-sm text-ink-subtle mb-3">No booking selected.</p>
                        <Link
                            :href="`/admin/bookings/create?date=${selectedDate}&venue=${venue.id}`"
                            class="inline-flex items-center gap-1 text-sm text-primary hover:text-primary-dark font-medium"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Create booking
                        </Link>
                        <button @click="selectedDate = null" class="block mt-2 text-xs text-ink-subtle hover:text-ink-muted">Close Ã—</button>
                    </div>

                    <!-- Venue stats -->
                    <div class="bg-surface rounded-lg shadow-sm p-4">
                        <h3 class="text-sm font-semibold text-ink mb-3">Venue Info</h3>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-ink-subtle">Capacity</dt>
                                <dd class="text-ink">{{ venue.capacity?.min ?? 'â€”' }}â€“{{ venue.capacity?.max ?? 'â€”' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-ink-subtle">Base Price</dt>
                                <dd class="text-ink">LKR {{ venue.pricing?.base_price?.toLocaleString() ?? 'â€”' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-ink-subtle">Status</dt>
                                <dd class="capitalize text-ink">{{ venue.status }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
.fc .fc-button {
    background-color: #4F46E5 !important;
    border-color: #4F46E5 !important;
}
.fc .fc-button:hover {
    background-color: #4338CA !important;
    border-color: #4338CA !important;
}
.fc .fc-button-active {
    background-color: #3730A3 !important;
    border-color: #3730A3 !important;
}
.fc .fc-today-button {
    background-color: #6B7280 !important;
    border-color: #6B7280 !important;
}
.fc .fc-daygrid-day.fc-day-today {
    background-color: #EEF2FF !important;
}
</style>
