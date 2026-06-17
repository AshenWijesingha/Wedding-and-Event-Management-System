<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';
import Avatar from '@/Components/ui/Avatar.vue';
import PageTour from '@/Components/PageTour.vue';

defineProps({ title: String, tour: String });

const page = usePage();
const user = computed(() => page.props.auth.user);
const { can, isSuperAdmin } = usePermissions();
const sidebarOpen = ref(false);

// `permission` gates a link by a single permission; `superAdminOnly` marks the
// cross-tenant platform area. Items with neither are visible to every admin-area user.
const navigation = [
    { name: 'Dashboard',     href: '/admin',                   icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { name: 'Bookings',      href: '/admin/bookings',          icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', permission: 'bookings.view' },
    { name: 'Calendar',      href: '/admin/calendar',          icon: 'M8 7V3m8 4V3M3 11h18M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2zM7 14h.01M11 14h.01M15 14h.01M7 17h.01M11 17h.01M15 17h.01', permission: 'bookings.view' },
    { name: 'Inquiries',     href: '/admin/inquiries',         icon: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z', permission: 'inquiries.view' },
    { name: 'Quotations',    href: '/admin/quotations',        icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', permission: 'quotations.view' },
    { name: 'Clients',       href: '/admin/clients',           icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', permission: 'clients.view' },
    { name: 'Venues',        href: '/admin/venues',            icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', permission: 'venues.view' },
    { name: 'Packages',      href: '/admin/packages',          icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', permission: 'packages.view' },
    { name: 'Vendors',       href: '/admin/vendors',           icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', permission: 'vendors.view' },
    { name: 'Tasks',         href: '/admin/tasks',             icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01', permission: 'tasks.view' },
    { name: 'Staff',         href: '/admin/staff',             icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', permission: 'staff.view' },
    { name: 'Payments',      href: '/admin/payments',          icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', permission: 'payments.view' },
    { name: 'Reports',       href: '/admin/reports',           icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', permission: 'reports.view' },
    { name: 'Users',         href: '/admin/users',             icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', permission: 'users.view' },
    { name: 'Tenants',       href: '/admin/tenants',           icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', superAdminOnly: true },
    { name: 'Plans',         href: '/admin/plans',             icon: 'M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 12V7a4 4 0 014-4z', superAdminOnly: true },
    { name: 'Audit Log',     href: '/admin/audit-log',         icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', superAdminOnly: true },
    { name: 'Platform',      href: '/admin/platform-settings', icon: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z', superAdminOnly: true },
    { name: 'Settings',      href: '/admin/settings',          icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', permission: 'settings.view' },
    { name: 'Active Sessions', href: '/admin/profile/sessions', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
];

const impersonating = computed(() => page.props.impersonating);
const stopImpersonating = () => router.post('/admin/impersonate-stop');

const visibleNav = computed(() =>
    navigation.filter(item => {
        if (item.superAdminOnly) return isSuperAdmin.value;
        return can(item.permission);
    })
);

const isActive = (href) => {
    if (href === '/admin') return page.url === '/admin' || page.url === '/admin/';
    return page.url.startsWith(href);
};

const logout = () => router.post('/logout');
</script>

<template>
    <Head :title="title" />
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:z-[60] focus:top-2 focus:left-2 focus:bg-primary focus:text-white focus:px-4 focus:py-2 focus:rounded-lg">Skip to content</a>
    <div class="min-h-screen bg-surface-muted flex">
        <!-- Sidebar -->
        <aside
            :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full', 'fixed inset-y-0 left-0 z-50 w-64 bg-surface border-r border-border flex flex-col transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0']"
        >
            <!-- Logo -->
            <div class="flex items-center justify-between h-16 px-6 border-b border-border flex-shrink-0">
                <Link href="/admin" class="flex items-center gap-2">
                    <span class="font-display text-xl font-semibold text-ink">EventPro</span>
                </Link>
                <button @click="sidebarOpen = false" aria-label="Close menu" class="lg:hidden text-ink-subtle hover:text-ink rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav data-tour="t-nav" class="mt-3 px-3 flex-1 overflow-y-auto">
                <Link
                    v-for="item in visibleNav"
                    :key="item.name"
                    :href="item.href"
                    :aria-current="isActive(item.href) ? 'page' : undefined"
                    :class="[
                        isActive(item.href)
                            ? 'bg-primary/10 text-primary'
                            : 'text-ink-muted hover:bg-surface-sunken hover:text-ink',
                        'group flex items-center px-3 py-2 text-sm font-medium rounded-lg mb-0.5 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary'
                    ]"
                >
                    <svg class="mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                    </svg>
                    {{ item.name }}
                </Link>
            </nav>

            <!-- User profile -->
            <div class="p-3 border-t border-border flex-shrink-0">
                <div class="flex items-center gap-3">
                    <Link href="/admin/profile" class="flex items-center gap-3 flex-1 min-w-0 group" title="My Profile">
                        <Avatar :name="user?.name" :src="user?.avatar_url" size="sm" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-ink truncate group-hover:underline">{{ user?.name }}</p>
                            <p class="text-xs text-ink-subtle truncate capitalize">{{ user?.role }}</p>
                        </div>
                    </Link>
                    <button @click="logout" aria-label="Log out" class="text-ink-subtle hover:text-ink transition-colors rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-primary" title="Logout">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Mobile overlay -->
        <div v-if="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-ink/40 lg:hidden" />

        <!-- Main content -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Demo sandbox banner -->
            <div v-if="$page.props.demo?.is_demo" class="flex items-center justify-center gap-2 bg-info text-white px-4 lg:px-6 py-2 text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                You're exploring a live demo — it resets automatically and your changes won't be saved.
            </div>
            <!-- Impersonation banner -->
            <div v-if="impersonating" class="flex items-center justify-between gap-3 bg-warning text-white px-4 lg:px-6 py-2 text-sm font-medium">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Impersonating <strong>{{ impersonating.tenant }}</strong>
                </span>
                <button @click="stopImpersonating" class="bg-white/20 hover:bg-white/30 rounded-lg px-3 py-1">Exit impersonation</button>
            </div>
            <!-- Top bar -->
            <header class="bg-surface border-b border-border h-16 flex items-center px-4 lg:px-6">
                <button @click="sidebarOpen = true" aria-label="Open menu" class="lg:hidden mr-4 text-ink-muted hover:text-ink rounded focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="flex-1">
                    <h1 class="font-display text-lg font-semibold text-ink">{{ title }}</h1>
                </div>
                <!-- Flash messages -->
                <div v-if="$page.props.flash?.success" class="mr-3 px-3 py-1 bg-success-soft text-success rounded-lg text-sm">{{ $page.props.flash.success }}</div>
                <div v-if="$page.props.flash?.error" class="mr-3 px-3 py-1 bg-danger-soft text-danger rounded-lg text-sm">{{ $page.props.flash.error }}</div>
                <div v-if="$page.props.flash?.info" class="mr-3 px-3 py-1 bg-info-soft text-info rounded-lg text-sm">{{ $page.props.flash.info }}</div>
            </header>

            <!-- Page content -->
            <main id="main" class="flex-1 overflow-y-auto p-4 lg:p-6">
                <slot />
            </main>
        </div>

        <!-- Per-page guided tour (auto-runs once, replayable via the "?" button) -->
        <PageTour v-if="tour" :key="tour" :tour-key="tour" />
    </div>
</template>
