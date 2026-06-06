<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ themes: Object, activeTheme: String });

function activate(slug) {
    router.post('/admin/themes/activate', { theme: slug }, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Themes">
        <div class="max-w-3xl mx-auto space-y-5">
            <h2 class="text-xl font-semibold text-gray-900">Theme Management</h2>

            <div v-if="Object.keys(themes).length" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div v-for="(theme, slug) in themes" :key="slug"
                    :class="['bg-white rounded-lg shadow-sm overflow-hidden border-2 transition-colors', slug === activeTheme ? 'border-indigo-500' : 'border-transparent hover:border-gray-200']">
                    <!-- Color swatch preview -->
                    <div class="h-16 flex">
                        <div v-for="(color, key) in (theme.colors ?? {})" :key="key"
                            class="flex-1" :style="{ backgroundColor: color }"></div>
                    </div>

                    <div class="p-4">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">{{ theme.name }}</h3>
                                <p class="text-xs text-gray-500 mt-0.5">v{{ theme.version }} · {{ theme.author }}</p>
                            </div>
                            <span v-if="slug === activeTheme"
                                class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                Active
                            </span>
                        </div>
                        <p class="text-xs text-gray-600 mb-3">{{ theme.description }}</p>

                        <!-- Font info -->
                        <div class="text-xs text-gray-400 mb-3">
                            <span>Heading: {{ theme.fonts?.heading }}</span>
                            <span class="mx-1">·</span>
                            <span>Body: {{ theme.fonts?.body }}</span>
                        </div>

                        <button v-if="slug !== activeTheme"
                            @click="activate(slug)"
                            class="w-full py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                            Activate
                        </button>
                        <div v-else class="w-full py-1.5 text-center text-indigo-600 text-sm font-medium">
                            Currently Active
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="bg-white rounded-lg shadow-sm p-12 text-center text-gray-400">
                <p class="text-sm">No themes found in resources/themes/.</p>
            </div>
        </div>
    </AppLayout>
</template>
