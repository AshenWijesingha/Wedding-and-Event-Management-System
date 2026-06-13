<script setup>
import { ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ fields: Array, filters: Object });

const entityType = ref(props.filters?.entity_type ?? '');

watch(entityType, () => {
    router.get('/admin/custom-fields', { entity_type: entityType.value }, { preserveState: true, replace: true });
});

const createForm = useForm({
    entity_type: '',
    name: '',
    type: 'text',
    placeholder: '',
    help_text: '',
    is_required: false,
    is_active: true,
    show_on_list: false,
    options: [],
});

const showCreate = ref(false);
const optionInput = ref('');

function addOption() {
    const s = optionInput.value.trim();
    if (s) createForm.options.push(s);
    optionInput.value = '';
}

function removeOption(i) { createForm.options.splice(i, 1); }

function submitCreate() {
    createForm.post('/admin/custom-fields', {
        onSuccess: () => { showCreate.value = false; createForm.reset(); },
    });
}

function destroy(field) {
    if (!window.confirm(`Delete "${field.name}"? All saved values will be lost.`)) return;
    router.delete(`/admin/custom-fields/${field.id}`, { preserveScroll: true });
}

function toggleActive(field) {
    router.patch(`/admin/custom-fields/${field.id}`, { is_active: !field.is_active }, { preserveScroll: true });
}

const entityTypes = ['booking', 'client', 'venue', 'inquiry', 'quotation'];
const fieldTypes = ['text', 'textarea', 'number', 'date', 'email', 'select', 'multiselect', 'checkbox', 'radio'];
const grouped = (type) => props.fields.filter(f => !type || f.entity_type === type);
</script>

<template>
    <AppLayout title="Custom Fields">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-ink">Custom Fields</h2>
                <button @click="showCreate = !showCreate"
                    class="px-4 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-lg">
                    + Add Field
                </button>
            </div>

            <!-- Create form -->
            <div v-if="showCreate" class="bg-surface rounded-lg shadow-sm p-5">
                <h3 class="text-sm font-semibold text-ink mb-3">New Custom Field</h3>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-ink-muted mb-1">Entity Type *</label>
                        <select v-model="createForm.entity_type" class="w-full border-border rounded-md text-sm">
                            <option value="">Select entity</option>
                            <option v-for="e in entityTypes" :key="e" :value="e" class="capitalize">{{ e }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink-muted mb-1">Field Name *</label>
                        <input v-model="createForm.name" type="text" class="w-full border-border rounded-md text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink-muted mb-1">Field Type *</label>
                        <select v-model="createForm.type" class="w-full border-border rounded-md text-sm">
                            <option v-for="t in fieldTypes" :key="t" :value="t" class="capitalize">{{ t }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink-muted mb-1">Placeholder</label>
                        <input v-model="createForm.placeholder" type="text" class="w-full border-border rounded-md text-sm" />
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-ink-muted mb-1">Help Text</label>
                        <input v-model="createForm.help_text" type="text" class="w-full border-border rounded-md text-sm" />
                    </div>

                    <!-- Options for select/checkbox/radio -->
                    <div v-if="['select','multiselect','checkbox','radio'].includes(createForm.type)" class="col-span-2">
                        <label class="block text-xs font-medium text-ink-muted mb-1">Options</label>
                        <div class="flex gap-2 mb-2">
                            <input v-model="optionInput" type="text" placeholder="Add option..." @keydown.enter.prevent="addOption"
                                class="flex-1 border-border rounded-md text-sm" />
                            <button type="button" @click="addOption" class="px-3 py-1.5 bg-surface-sunken text-sm rounded-md">Add</button>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="(o, i) in createForm.options" :key="i"
                                class="inline-flex items-center gap-1 px-2.5 py-1 bg-primary/5 text-primary-dark text-xs rounded-full">
                                {{ o }} <button type="button" @click="removeOption(i)">&times;</button>
                            </span>
                        </div>
                    </div>

                    <div class="col-span-2 flex gap-6">
                        <label class="flex items-center gap-2 text-sm text-ink-muted">
                            <input v-model="createForm.is_required" type="checkbox" class="rounded border-border text-primary" />
                            Required
                        </label>
                        <label class="flex items-center gap-2 text-sm text-ink-muted">
                            <input v-model="createForm.show_on_list" type="checkbox" class="rounded border-border text-primary" />
                            Show on list view
                        </label>
                    </div>
                </div>
                <div class="flex gap-2 mt-3">
                    <button @click="submitCreate" :disabled="createForm.processing || !createForm.name || !createForm.entity_type"
                        class="px-4 py-2 bg-primary hover:bg-primary-dark text-white text-sm rounded-lg disabled:opacity-50">Create</button>
                    <button @click="showCreate = false" class="px-4 py-2 border border-border text-ink-muted text-sm rounded-lg hover:bg-surface-muted">Cancel</button>
                </div>
            </div>

            <!-- Filter -->
            <div class="bg-surface rounded-lg shadow-sm p-4 flex gap-3">
                <select v-model="entityType" class="border border-border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All entities</option>
                    <option v-for="e in entityTypes" :key="e" :value="e" class="capitalize">{{ e }}</option>
                </select>
            </div>

            <!-- Fields table -->
            <div class="bg-surface rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-surface-muted">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-ink-subtle uppercase">Field</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-ink-subtle uppercase">Entity</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-ink-subtle uppercase">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-ink-subtle uppercase">Required</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-ink-subtle uppercase">Active</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface divide-y divide-border">
                        <tr v-for="field in fields" :key="field.id" class="hover:bg-surface-muted">
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium text-ink">{{ field.name }}</p>
                                <p class="text-xs text-ink-subtle">{{ field.slug }}</p>
                            </td>
                            <td class="px-4 py-3 text-sm text-ink-muted capitalize">{{ field.entity_type }}</td>
                            <td class="px-4 py-3 text-sm text-ink-muted capitalize">{{ field.type }}</td>
                            <td class="px-4 py-3">
                                <span :class="field.is_required ? 'text-red-600' : 'text-ink-subtle'" class="text-xs">{{ field.is_required ? 'Yes' : 'No' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <button @click="toggleActive(field)"
                                    :class="field.is_active ? 'bg-green-100 text-green-700' : 'bg-surface-sunken text-ink-subtle'"
                                    class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium">
                                    {{ field.is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button @click="destroy(field)" class="text-xs text-red-500 hover:text-red-700">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="!fields.length">
                            <td colspan="6" class="px-4 py-12 text-center text-ink-subtle text-sm">No custom fields defined.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
