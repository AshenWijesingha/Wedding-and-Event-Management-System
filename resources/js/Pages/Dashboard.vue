<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <AppLayout title="Dashboard">
        <div class="space-y-6">
            <!-- Welcome banner -->
            <div class="bg-surface rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-ink">
                    Welcome back, {{ user?.name }} 👋
                </h2>
                <p class="text-ink-subtle mt-1 text-sm">Here's what's happening with your events today.</p>
            </div>

            <!-- Stats grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-surface rounded-lg shadow-sm p-5 flex items-center space-x-4">
                    <div class="p-3 rounded-full bg-primary/10 text-primary">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm text-ink-subtle">Total Bookings</p>
                        <p class="text-2xl font-bold text-ink">0</p>
                    </div>
                </div>

                <div class="bg-surface rounded-lg shadow-sm p-5 flex items-center space-x-4">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm text-ink-subtle">New Inquiries</p>
                        <p class="text-2xl font-bold text-ink">0</p>
                    </div>
                </div>

                <div class="bg-surface rounded-lg shadow-sm p-5 flex items-center space-x-4">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm text-ink-subtle">Revenue (Month)</p>
                        <p class="text-2xl font-bold text-ink">$0</p>
                    </div>
                </div>

                <div class="bg-surface rounded-lg shadow-sm p-5 flex items-center space-x-4">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm text-ink-subtle">Total Clients</p>
                        <p class="text-2xl font-bold text-ink">0</p>
                    </div>
                </div>
            </div>

            <!-- Quick actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="/admin/bookings" class="bg-surface rounded-lg shadow-sm p-5 hover:shadow-md transition-shadow group">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-ink">Manage Bookings</h3>
                        <svg class="w-5 h-5 text-ink-subtle group-hover:text-primary transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <p class="text-sm text-ink-subtle">View and manage all event bookings</p>
                </a>
                <a href="/admin/inquiries" class="bg-surface rounded-lg shadow-sm p-5 hover:shadow-md transition-shadow group">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-ink">New Inquiries</h3>
                        <svg class="w-5 h-5 text-ink-subtle group-hover:text-primary transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <p class="text-sm text-ink-subtle">Respond to incoming event inquiries</p>
                </a>
                <a href="/admin/venues" class="bg-surface rounded-lg shadow-sm p-5 hover:shadow-md transition-shadow group">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-ink">Venue Setup</h3>
                        <svg class="w-5 h-5 text-ink-subtle group-hover:text-primary transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <p class="text-sm text-ink-subtle">Configure venues and availability</p>
                </a>
            </div>
        </div>
    </AppLayout>
</template>
