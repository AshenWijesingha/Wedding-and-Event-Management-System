<script setup>
import { ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ tasks: Object, filters: Object, allStaff: Array });

const status      = ref(props.filters?.status ?? '');
const priority    = ref(props.filters?.priority ?? '');
const assignedTo  = ref(props.filters?.assigned_to ?? '');

watch([status, priority, assignedTo], () => {
    router.get('/admin/tasks', { status: status.value, priority: priority.value, assigned_to: assignedTo.value }, { preserveState: true, replace: true });
});

const createForm = useForm({ title: '', priority: 'medium', due_date: '', assigned_to: '', description: '' });
const showCreate = ref(false);

function submitCreate() {
    createForm.post('/admin/tasks', {
        onSuccess: () => { showCreate.value = false; createForm.reset(); },
    });
}

function markComplete(task) {
    router.patch(`/admin/tasks/${task.id}`, { status: 'completed' }, { preserveScroll: true });
}

function destroy(task) {
    if (!window.confirm(`Delete task "${task.title}"?`)) return;
    router.delete(`/admin/tasks/${task.id}`, { preserveScroll: true });
}

const priorityColors = {
    low:    'bg-surface-sunken text-ink-muted',
    medium: 'bg-blue-100 text-blue-700',
    high:   'bg-orange-100 text-orange-700',
    urgent: 'bg-red-100 text-red-600',
};

const statusColors = {
    pending:     'bg-yellow-50 border-yellow-200',
    in_progress: 'bg-blue-50 border-blue-200',
    completed:   'bg-green-50 border-green-200',
    cancelled:   'bg-surface-muted border-border',
};
</script>

<template>
    <AppLayout title="Tasks">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-ink">Tasks</h2>
                <button @click="showCreate = !showCreate"
                    class="px-4 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-lg">
                    + New Task
                </button>
            </div>

            <!-- Create form -->
            <div v-if="showCreate" class="bg-surface rounded-lg shadow-sm p-5">
                <h3 class="text-sm font-semibold text-ink mb-3">New Task</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="sm:col-span-2">
                        <input v-model="createForm.title" type="text" placeholder="Task title *"
                            class="w-full border-border rounded-md text-sm focus:border-primary focus:ring-primary" />
                    </div>
                    <select v-model="createForm.priority" class="border-border rounded-md text-sm focus:border-primary focus:ring-primary">
                        <option value="low">Low priority</option>
                        <option value="medium">Medium priority</option>
                        <option value="high">High priority</option>
                        <option value="urgent">Urgent</option>
                    </select>
                    <input v-model="createForm.due_date" type="date"
                        class="border-border rounded-md text-sm focus:border-primary focus:ring-primary" />
                    <select v-model="createForm.assigned_to" class="border-border rounded-md text-sm focus:border-primary focus:ring-primary">
                        <option value="">Unassigned</option>
                        <option v-for="s in allStaff" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                    <textarea v-model="createForm.description" placeholder="Description (optional)" rows="2"
                        class="sm:col-span-2 border-border rounded-md text-sm focus:border-primary focus:ring-primary resize-none"></textarea>
                </div>
                <div class="flex gap-2 mt-3">
                    <button @click="submitCreate" :disabled="createForm.processing || !createForm.title"
                        class="px-4 py-2 bg-primary hover:bg-primary-dark text-white text-sm rounded-lg disabled:opacity-50">Create</button>
                    <button @click="showCreate = false" class="px-4 py-2 border border-border text-ink-muted text-sm rounded-lg hover:bg-surface-muted">Cancel</button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-surface rounded-lg shadow-sm p-4 flex flex-wrap gap-3">
                <select v-model="status" class="border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <select v-model="priority" class="border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All priorities</option>
                    <option value="urgent">Urgent</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
                <select v-model="assignedTo" class="border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All staff</option>
                    <option v-for="s in allStaff" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
            </div>

            <!-- Task cards (mobile-friendly) -->
            <div class="space-y-3">
                <div v-for="task in tasks.data" :key="task.id"
                    :class="['rounded-lg border p-4 bg-surface', statusColors[task.status] ?? 'bg-surface border-border']">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span :class="['inline-flex px-2 py-0.5 rounded-full text-xs font-medium', priorityColors[task.priority] ?? 'bg-surface-sunken text-ink-muted']">
                                    {{ task.priority }}
                                </span>
                                <p class="text-sm font-medium text-ink truncate">{{ task.title }}</p>
                            </div>
                            <p v-if="task.description" class="text-xs text-ink-subtle mt-1 line-clamp-2">{{ task.description }}</p>
                            <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-xs text-ink-subtle">
                                <span v-if="task.assignee">{{ task.assignee.full_name }}</span>
                                <span v-if="task.due_date">Due: {{ task.due_date }}</span>
                                <span v-if="task.booking">{{ task.booking.booking_number }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-1 shrink-0">
                            <span class="text-xs text-ink-subtle capitalize">{{ task.status?.replace('_', ' ') }}</span>
                            <div class="flex gap-2">
                                <button v-if="task.status !== 'completed'" @click="markComplete(task)"
                                    class="text-xs text-green-600 hover:text-green-800">Done</button>
                                <button @click="destroy(task)" class="text-xs text-red-500 hover:text-red-700">Delete</button>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-if="!tasks.data?.length" class="text-center text-ink-subtle text-sm py-8">No tasks found.</p>
            </div>

            <div v-if="tasks.meta?.last_page > 1" class="flex items-center justify-between text-sm text-ink-muted">
                <span>Showing {{ tasks.meta.from }}–{{ tasks.meta.to }} of {{ tasks.meta.total }}</span>
                <div class="flex gap-2">
                    <a v-if="tasks.links?.prev" :href="tasks.links.prev" class="px-3 py-1 border border-border rounded hover:bg-surface-muted">Previous</a>
                    <a v-if="tasks.links?.next" :href="tasks.links.next" class="px-3 py-1 border border-border rounded hover:bg-surface-muted">Next</a>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
