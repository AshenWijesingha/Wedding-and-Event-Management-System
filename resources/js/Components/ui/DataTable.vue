<script setup>
import { Link } from '@inertiajs/vue3';
import EmptyState from './EmptyState.vue';

defineProps({
    // [{ key, label, align: 'left'|'right', class }]
    columns: { type: Array, required: true },
    rows: { type: Array, default: () => [] },
    rowKey: { type: String, default: 'id' },
    // { meta: {from,to,total,last_page}, links: {prev,next} }
    pagination: { type: Object, default: null },
    emptyTitle: { type: String, default: 'No records found' },
    loading: { type: Boolean, default: false },
});
</script>

<template>
    <div class="bg-surface border border-border rounded-lg shadow-sm overflow-hidden">
        <!-- Loading skeleton -->
        <div v-if="loading" class="p-5 space-y-3" aria-busy="true" aria-live="polite">
            <div v-for="n in 6" :key="n" class="h-10 rounded-lg bg-surface-sunken animate-pulse" />
        </div>

        <template v-else>
            <!-- Desktop / tablet: table -->
            <div class="hidden sm:block overflow-x-auto">
                <table v-if="rows.length" class="min-w-full divide-y divide-border">
                    <thead class="bg-surface-muted">
                        <tr>
                            <th v-for="col in columns" :key="col.key" scope="col"
                                :class="['sticky top-0 z-10 bg-surface-muted px-5 py-3 text-xs font-semibold uppercase tracking-wider text-ink-subtle', col.align === 'right' ? 'text-right' : 'text-left']">
                                {{ col.label }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="row in rows" :key="row[rowKey]" class="hover:bg-surface-muted transition-colors">
                            <td v-for="col in columns" :key="col.key"
                                :class="['px-5 py-3.5 text-sm text-ink', col.align === 'right' ? 'text-right' : 'text-left', col.class]">
                                <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">{{ row[col.key] }}</slot>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <slot v-else name="empty">
                    <EmptyState :title="emptyTitle" />
                </slot>
            </div>

            <!-- Mobile: stacked cards -->
            <div class="sm:hidden">
                <ul v-if="rows.length" class="divide-y divide-border">
                    <li v-for="row in rows" :key="row[rowKey]" class="p-4 space-y-2">
                        <div v-for="col in columns" :key="col.key" class="flex items-start justify-between gap-3">
                            <span class="text-xs font-semibold uppercase tracking-wider text-ink-subtle shrink-0">{{ col.label }}</span>
                            <span class="text-sm text-ink text-right min-w-0 break-words">
                                <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">{{ row[col.key] }}</slot>
                            </span>
                        </div>
                    </li>
                </ul>
                <slot v-else name="empty">
                    <EmptyState :title="emptyTitle" />
                </slot>
            </div>
        </template>

        <div v-if="!loading && pagination?.meta?.last_page > 1"
             class="px-5 py-3 border-t border-border flex items-center justify-between text-sm text-ink-muted">
            <span>Showing {{ pagination.meta.from }}–{{ pagination.meta.to }} of {{ pagination.meta.total }}</span>
            <div class="flex gap-2">
                <Link v-if="pagination.links?.prev" :href="pagination.links.prev" class="px-3 py-1 border border-border rounded-lg hover:bg-surface-sunken focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">Previous</Link>
                <Link v-if="pagination.links?.next" :href="pagination.links.next" class="px-3 py-1 border border-border rounded-lg hover:bg-surface-sunken focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">Next</Link>
            </div>
        </div>
    </div>
</template>
