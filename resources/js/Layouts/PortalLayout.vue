<script setup>
import { ref, computed } from 'vue';
import { Link, Head, usePage, router } from '@inertiajs/vue3';
import Avatar from '@/Components/ui/Avatar.vue';

defineProps({ title: String });

const page = usePage();
const user = computed(() => page.props.auth.user);
const sidebarOpen = ref(false);

// Client portal navigation — every item is available to the `client` role, so
// no permission gating (the bug in the shared admin layout was using admin-only
// permission gates, which left clients with an empty sidebar).
const navigation = [
    { name: 'Dashboard',   href: '/portal',            icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { name: 'My Bookings', href: '/portal/bookings',   icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { name: 'Quotations',  href: '/portal/quotations', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
    { name: 'Payments',    href: '/portal/payments',   icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z' },
    { name: 'Sessions',    href: '/portal/sessions',   icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
];

const isActive = (href) => {
    if (href === '/portal') return page.url === '/portal' || page.url === '/portal/';
    return page.url.startsWith(href);
};

const logout = () => router.post('/logout');
</script>

<template>
    <Head :title="title" />
    <div class="min-h-screen bg-surface-muted flex">
        <!-- Sidebar -->
        <aside
            :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full', 'fixed inset-y-0 left-0 z-50 w-64 bg-surface border-r border-border flex flex-col transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0']"
        >
            <div class="flex items-center justify-between h-16 px-6 border-b border-border flex-shrink-0">
                <Link href="/portal" class="flex items-center gap-2">
                    <span class="font-display text-xl font-semibold text-ink">EventPro</span>
                </Link>
                <button @click="sidebarOpen = false" class="lg:hidden text-ink-subtle hover:text-ink">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <nav class="mt-3 px-3 flex-1 overflow-y-auto">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        isActive(item.href) ? 'bg-primary/10 text-primary' : 'text-ink-muted hover:bg-surface-sunken hover:text-ink',
                        'group flex items-center px-3 py-2 text-sm font-medium rounded-lg mb-0.5 transition-colors'
                    ]"
                >
                    <svg class="mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                    </svg>
                    {{ item.name }}
                </Link>
            </nav>

            <div class="p-3 border-t border-border flex-shrink-0">
                <div class="flex items-center gap-3">
                    <Avatar :name="user?.name" :src="user?.avatar_url" size="sm" />
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-ink truncate">{{ user?.name }}</p>
                        <p class="text-xs text-ink-subtle truncate">Client</p>
                    </div>
                    <button @click="logout" class="text-ink-subtle hover:text-ink transition-colors" title="Logout">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </div>
            </div>
        </aside>

        <div v-if="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-ink/40 lg:hidden" />

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <header class="bg-surface border-b border-border h-16 flex items-center px-4 lg:px-6">
                <button @click="sidebarOpen = true" class="lg:hidden mr-4 text-ink-muted hover:text-ink">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="flex-1">
                    <h1 class="font-display text-lg font-semibold text-ink">{{ title }}</h1>
                </div>
                <div v-if="page.props.flash?.success" class="mr-3 px-3 py-1 bg-success-soft text-success rounded-lg text-sm">{{ page.props.flash.success }}</div>
                <div v-if="page.props.flash?.error" class="mr-3 px-3 py-1 bg-danger-soft text-danger rounded-lg text-sm">{{ page.props.flash.error }}</div>
                <div v-if="page.props.flash?.info" class="mr-3 px-3 py-1 bg-info-soft text-info rounded-lg text-sm">{{ page.props.flash.info }}</div>
            </header>

            <main class="flex-1 overflow-y-auto p-4 lg:p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
