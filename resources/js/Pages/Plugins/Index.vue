<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ plugins: Array, loadedPlugins: Array });

function enable(slug) {
    router.post('/admin/plugins/enable', { plugin: slug }, { preserveScroll: true });
}

function disable(slug) {
    if (!window.confirm('Disable this plugin?')) return;
    router.post('/admin/plugins/disable', { plugin: slug }, { preserveScroll: true });
}

function isEnabled(slug) {
    return props.loadedPlugins.includes(slug);
}
</script>

<template>
    <AppLayout title="Plugins">
        <div class="max-w-3xl mx-auto space-y-5">
            <h2 class="text-xl font-semibold text-gray-900">Plugins</h2>

            <div v-if="plugins.length" class="space-y-3">
                <div v-for="plugin in plugins" :key="plugin.slug" class="bg-white rounded-lg shadow-sm p-5 flex items-start gap-4">
                    <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 0h2a2 2 0 002-2V6a2 2 0 00-2-2h-2a2 2 0 00-2 2v2a2 2 0 002 2zM6 20h2a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-sm font-semibold text-gray-900">{{ plugin.name }}</h3>
                            <span class="text-xs text-gray-400">v{{ plugin.version }}</span>
                            <span v-if="isEnabled(plugin.slug)"
                                class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                Enabled
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">{{ plugin.description }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">Author: {{ plugin.author }}</p>

                        <!-- Hooks -->
                        <div v-if="plugin.hooks && Object.keys(plugin.hooks).length" class="mt-2">
                            <p class="text-xs text-gray-500 mb-1">Hooks:</p>
                            <div class="flex flex-wrap gap-1">
                                <span v-for="(handler, hook) in plugin.hooks" :key="hook"
                                    class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">
                                    {{ hook }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="shrink-0">
                        <button v-if="!isEnabled(plugin.slug)"
                            @click="enable(plugin.slug)"
                            class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg">
                            Enable
                        </button>
                        <button v-else
                            @click="disable(plugin.slug)"
                            class="px-3 py-1.5 border border-red-300 text-red-600 hover:bg-red-50 text-xs font-medium rounded-lg">
                            Disable
                        </button>
                    </div>
                </div>
            </div>
            <div v-else class="bg-white rounded-lg shadow-sm p-12 text-center text-gray-400">
                <p class="text-sm">No plugins found in plugins/ directory.</p>
            </div>
        </div>
    </AppLayout>
</template>
