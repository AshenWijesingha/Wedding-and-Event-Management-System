<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
import AppLayout from '@/Layouts/AppLayout.vue';
import { statusClass } from '@/utils/status';

const props = defineProps({
    events: Array,
});

// Distinct statuses present, for the legend.
const legend = [...new Set(props.events.map(e => e.status))];

function eventClassNames(arg) {
    return ['fc-booking', ...statusClass(arg.event.extendedProps.status).split(' ')];
}

function onEventClick(info) {
    info.jsEvent.preventDefault();
    if (info.event.url) router.visit(info.event.url);
}

const options = ref({
    plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,listMonth',
    },
    buttonText: { today: 'Today', month: 'Month', week: 'Week', list: 'List' },
    height: 'auto',
    events: props.events,
    eventClassNames,
    eventClick: onEventClick,
    displayEventTime: false,
    dayMaxEvents: 3,
});
</script>

<template>
    <AppLayout title="Calendar">
        <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-display text-xl font-semibold text-ink">Calendar</h2>
                    <p class="text-sm text-ink-subtle">All bookings across venues, by event date.</p>
                </div>
                <div v-if="legend.length" class="flex flex-wrap items-center gap-2">
                    <span v-for="s in legend" :key="s"
                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                        :class="statusClass(s)">{{ s }}</span>
                </div>
            </div>

            <div class="bg-surface rounded-lg shadow-sm p-4">
                <FullCalendar :options="options" />
            </div>
        </div>
    </AppLayout>
</template>

<style>
/* Booking events: pill-shaped, token-colored via statusClass (bg-*-soft text-*). */
.fc-booking {
    border: none;
    border-radius: 0.375rem;
    padding: 0 0.25rem;
    font-weight: 500;
    cursor: pointer;
}
.fc .fc-toolbar-title {
    font-size: 1.1rem;
}
.fc .fc-button {
    text-transform: capitalize;
}
</style>
