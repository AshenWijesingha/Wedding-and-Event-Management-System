<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const page = usePage();
const open = ref(false);

const items = computed(() => page.props.notifications?.items ?? []);
const unread = computed(() => page.props.notifications?.unread_count ?? 0);

function toggle() {
    open.value = !open.value;
}

function markAllRead() {
    router.post('/admin/notifications/read', {}, { preserveScroll: true, preserveState: false });
}

function openItem(item) {
    router.post('/admin/notifications/read', { id: item.id }, {
        preserveScroll: true,
        preserveState: false,
        onFinish: () => {
            open.value = false;
            if (item.url) router.visit(item.url);
        },
    });
}
</script>

<template>
    <div class="relative mr-3">
        <button @click="toggle" aria-label="Notifications"
            class="relative text-ink-subtle hover:text-ink transition-colors rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span v-if="unread > 0"
                class="absolute -top-1 -right-1 inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-semibold leading-none text-white bg-danger rounded-full">
                {{ unread > 9 ? '9+' : unread }}
            </span>
        </button>

        <div v-if="open" class="fixed inset-0 z-30" @click="open = false"></div>

        <div v-if="open"
            class="absolute right-0 z-40 mt-2 w-80 max-h-96 overflow-y-auto bg-surface border border-border rounded-lg shadow-lg">
            <div class="flex items-center justify-between px-4 py-2 border-b border-border">
                <span class="text-sm font-semibold text-ink">Notifications</span>
                <button v-if="unread > 0" @click="markAllRead" class="text-xs text-primary hover:underline">Mark all read</button>
            </div>
            <div v-if="items.length === 0" class="px-4 py-6 text-center text-sm text-ink-subtle">
                No notifications yet.
            </div>
            <button v-for="item in items" :key="item.id" @click="openItem(item)"
                class="w-full text-left px-4 py-3 border-b border-border last:border-0 hover:bg-surface-muted transition-colors"
                :class="{ 'bg-info-soft/40': !item.read_at }">
                <p class="text-sm text-ink">{{ item.message }}</p>
                <p class="text-xs text-ink-subtle mt-0.5">{{ item.created_at }}</p>
            </button>
        </div>
    </div>
</template>
