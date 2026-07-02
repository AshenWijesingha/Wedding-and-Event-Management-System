<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ vendor: Object });
</script>

<template>
    <AppLayout :title="vendor.name">
        <div class="max-w-3xl mx-auto space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link href="/admin/vendors" class="text-ink-subtle hover:text-ink-muted">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </Link>
                    <div>
                        <h2 class="text-xl font-semibold text-ink">{{ vendor.name }}</h2>
                        <span class="text-xs text-ink-subtle capitalize">{{ vendor.category }}</span>
                    </div>
                </div>
                <Link :href="`/admin/vendors/${vendor.id}/edit`"
                    class="px-4 py-2 border border-border text-ink-muted text-sm font-medium rounded-lg hover:bg-surface-muted">
                    Edit
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2 bg-surface rounded-lg shadow-sm p-5 space-y-3">
                    <h3 class="text-sm font-semibold text-ink">Details</h3>
                    <dl class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-ink-subtle">Contact</dt>
                            <dd class="font-medium">{{ vendor.contact_name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-ink-subtle">Email</dt>
                            <dd>{{ vendor.email ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-ink-subtle">Phone</dt>
                            <dd>{{ vendor.phone ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-ink-subtle">Website</dt>
                            <dd>
                                <a v-if="vendor.website" :href="vendor.website" target="_blank" class="text-primary hover:underline text-xs">{{ vendor.website }}</a>
                                <span v-else>—</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-ink-subtle">Base Rate</dt>
                            <dd>LKR {{ vendor.base_rate?.toLocaleString() ?? '—' }} <span class="text-xs text-ink-subtle capitalize">{{ vendor.rate_type?.replace('_', ' ') }}</span></dd>
                        </div>
                        <div>
                            <dt class="text-ink-subtle">Status</dt>
                            <dd>
                                <span :class="vendor.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-surface-sunken text-ink-subtle'"
                                    class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium capitalize">
                                    {{ vendor.status }}
                                </span>
                            </dd>
                        </div>
                    </dl>

                    <div v-if="vendor.description" class="pt-2 border-t border-border">
                        <p class="text-sm text-ink-muted whitespace-pre-line">{{ vendor.description }}</p>
                    </div>

                    <div v-if="vendor.services?.length" class="pt-2 border-t border-border">
                        <p class="text-xs font-medium text-ink-subtle uppercase mb-2">Services</p>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="s in vendor.services" :key="s" class="px-2.5 py-1 bg-primary/5 text-primary-dark text-xs rounded-full">{{ s }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-surface rounded-lg shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-ink mb-3">Notes</h3>
                    <p class="text-sm text-ink-muted whitespace-pre-line">{{ vendor.notes ?? 'No notes.' }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
