<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ staff: Object, tasks: Object });

const priorityColors = {
    low:    'bg-gray-100 text-gray-600',
    medium: 'bg-blue-100 text-blue-700',
    high:   'bg-orange-100 text-orange-700',
    urgent: 'bg-red-100 text-red-600',
};

const statusColors = {
    pending:     'text-yellow-600',
    in_progress: 'text-blue-600',
    completed:   'text-green-600',
    cancelled:   'text-gray-400',
};
</script>

<template>
    <AppLayout :title="staff.full_name">
        <div class="max-w-3xl mx-auto space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link href="/admin/staff" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </Link>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">{{ staff.full_name }}</h2>
                        <p class="text-sm text-gray-500 capitalize">{{ staff.role }}</p>
                    </div>
                </div>
                <Link :href="`/admin/staff/${staff.id}/edit`" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">Edit</Link>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Contact</h3>
                    <dl class="space-y-2 text-sm">
                        <div><dt class="text-gray-500">Email</dt><dd>{{ staff.email ?? '—' }}</dd></div>
                        <div><dt class="text-gray-500">Phone</dt><dd>{{ staff.phone ?? '—' }}</dd></div>
                        <div>
                            <dt class="text-gray-500">Status</dt>
                            <dd>
                                <span :class="staff.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                    class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium capitalize">
                                    {{ staff.status }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2">Notes</h3>
                    <p class="text-sm text-gray-600 whitespace-pre-line">{{ staff.notes ?? 'No notes.' }}</p>
                </div>
            </div>

            <!-- Tasks -->
            <div class="bg-white rounded-lg shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-900">Assigned Tasks</h3>
                    <Link href="/admin/tasks" class="text-xs text-indigo-600 hover:underline">View all tasks</Link>
                </div>
                <div v-if="tasks?.length" class="space-y-2">
                    <div v-for="task in tasks" :key="task.id" class="flex items-start justify-between py-2 border-t border-gray-100 text-sm">
                        <div>
                            <p class="font-medium text-gray-900">{{ task.title }}</p>
                            <p v-if="task.booking" class="text-xs text-gray-500">Booking: {{ task.booking.booking_number }}</p>
                            <p v-if="task.due_date" class="text-xs text-gray-400">Due: {{ task.due_date }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <span :class="['inline-flex px-2 py-0.5 rounded-full text-xs font-medium', priorityColors[task.priority] ?? 'bg-gray-100 text-gray-600']">
                                {{ task.priority }}
                            </span>
                            <span :class="['text-xs font-medium capitalize', statusColors[task.status] ?? 'text-gray-500']">
                                {{ task.status?.replace('_', ' ') }}
                            </span>
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-400">No tasks assigned.</p>
            </div>
        </div>
    </AppLayout>
</template>
