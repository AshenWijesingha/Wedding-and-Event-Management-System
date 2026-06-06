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
                    <Link href="/admin/vendors" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </Link>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">{{ vendor.name }}</h2>
                        <span class="text-xs text-gray-500 capitalize">{{ vendor.category }}</span>
                    </div>
                </div>
                <Link :href="`/admin/vendors/${vendor.id}/edit`"
                    class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
                    Edit
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-5 space-y-3">
                    <h3 class="text-sm font-semibold text-gray-900">Details</h3>
                    <dl class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="text-gray-500">Contact</dt>
                            <dd class="font-medium">{{ vendor.contact_name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Email</dt>
                            <dd>{{ vendor.email ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Phone</dt>
                            <dd>{{ vendor.phone ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Website</dt>
                            <dd>
                                <a v-if="vendor.website" :href="vendor.website" target="_blank" class="text-indigo-600 hover:underline text-xs">{{ vendor.website }}</a>
                                <span v-else>—</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Base Rate</dt>
                            <dd>${{ vendor.base_rate?.toLocaleString() ?? '—' }} <span class="text-xs text-gray-400 capitalize">{{ vendor.rate_type?.replace('_', ' ') }}</span></dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Status</dt>
                            <dd>
                                <span :class="vendor.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                    class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium capitalize">
                                    {{ vendor.status }}
                                </span>
                            </dd>
                        </div>
                    </dl>

                    <div v-if="vendor.description" class="pt-2 border-t border-gray-100">
                        <p class="text-sm text-gray-600 whitespace-pre-line">{{ vendor.description }}</p>
                    </div>

                    <div v-if="vendor.services?.length" class="pt-2 border-t border-gray-100">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-2">Services</p>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="s in vendor.services" :key="s" class="px-2.5 py-1 bg-indigo-50 text-indigo-700 text-xs rounded-full">{{ s }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">Notes</h3>
                    <p class="text-sm text-gray-600 whitespace-pre-line">{{ vendor.notes ?? 'No notes.' }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
