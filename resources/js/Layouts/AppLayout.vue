<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';

defineProps({ title: String });

const page = usePage();
const user = computed(() => page.props.auth.user);
const sidebarOpen = ref(false);

const navigation = [
    { name: 'Dashboard',   href: '/admin',           icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { name: 'Bookings',    href: '/admin/bookings',   icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { name: 'Inquiries',   href: '/admin/inquiries',  icon: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z' },
    { name: 'Quotations',  href: '/admin/quotations', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
    { name: 'Clients',     href: '/admin/clients',    icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' },
    { name: 'Venues',      href: '/admin/venues',     icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
    { name: 'Packages',    href: '/admin/packages',   icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' },
    { name: 'Payments',    href: '/admin/payments',   icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z' },
    { name: 'Reports',     href: '/admin/reports',    icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
    { name: 'Users',       href: '/admin/users',      icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', adminOnly: true },
    { name: 'Tenants',     href: '/admin/tenants',    icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', platformOnly: true },
    { name: 'Plans',       href: '/admin/plans',      icon: 'M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 12V7a4 4 0 014-4z', platformOnly: true },
    { name: 'Audit Log',   href: '/admin/audit-log',  icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', platformOnly: true },
    { name: 'Platform',    href: '/admin/platform-settings', icon: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z', platformOnly: true },
    { name: 'Settings',    href: '/admin/settings',   icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z' },
];

const impersonating = computed(() => page.props.impersonating);
const stopImpersonating = () => router.post('/admin/impersonate-stop');

const visibleNav = computed(() =>
    navigation.filter(item => {
        if (item.platformOnly) return user.value?.role === 'super_admin';
        if (item.adminOnly) return ['super_admin', 'tenant_owner', 'admin'].includes(user.value?.role);
        return true;
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
    <div class="min-h-screen bg-gray-50 flex">
        <!-- Sidebar -->
        <aside
            :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full', 'fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0']"
        >
            <!-- Logo -->
            <div class="flex items-center justify-between h-16 px-6 bg-gray-800">
                <Link href="/admin" class="flex items-center space-x-2">
                    <span class="text-xl font-bold text-white">EventPro</span>
                </Link>
                <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="mt-4 px-3 flex-1 overflow-y-auto">
                <Link
                    v-for="item in visibleNav"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        isActive(item.href)
                            ? 'bg-indigo-600 text-white'
                            : 'text-gray-300 hover:bg-gray-700 hover:text-white',
                        'group flex items-center px-3 py-2 text-sm font-medium rounded-md mb-1 transition-colors'
                    ]"
                >
                    <svg class="mr-3 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                    </svg>
                    {{ item.name }}
                </Link>
            </nav>

            <!-- User profile -->
            <div class="absolute bottom-0 w-full p-4 bg-gray-800">
                <div class="flex items-center space-x-3">
                    <Link href="/admin/profile" class="flex items-center space-x-3 flex-1 min-w-0 group" title="My Profile">
                        <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center text-white text-sm font-medium overflow-hidden">
                            <img v-if="user?.avatar_url" :src="user.avatar_url" alt="" class="w-full h-full object-cover" />
                            <span v-else>{{ user?.name?.charAt(0)?.toUpperCase() }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate group-hover:underline">{{ user?.name }}</p>
                            <p class="text-xs text-gray-400 truncate capitalize">{{ user?.role }}</p>
                        </div>
                    </Link>
                    <button @click="logout" class="text-gray-400 hover:text-white transition-colors" title="Logout">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Mobile overlay -->
        <div v-if="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden" />

        <!-- Main content -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Impersonation banner -->
            <div v-if="impersonating" class="flex items-center justify-between gap-3 bg-amber-500 text-white px-4 lg:px-6 py-2 text-sm font-medium">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Impersonating <strong>{{ impersonating.tenant }}</strong>
                </span>
                <button @click="stopImpersonating" class="bg-white/20 hover:bg-white/30 rounded px-3 py-1">Exit impersonation</button>
            </div>
            <!-- Top bar -->
            <header class="bg-white shadow-sm h-16 flex items-center px-4 lg:px-6">
                <button @click="sidebarOpen = true" class="lg:hidden mr-4 text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="flex-1">
                    <h1 class="text-lg font-semibold text-gray-800">{{ title }}</h1>
                </div>
                <!-- Flash messages -->
                <div v-if="$page.props.flash?.success" class="mr-4 px-3 py-1 bg-green-100 text-green-700 rounded text-sm">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash?.error" class="mr-4 px-3 py-1 bg-red-100 text-red-700 rounded text-sm">
                    {{ $page.props.flash.error }}
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto p-4 lg:p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
