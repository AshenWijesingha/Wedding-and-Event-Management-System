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
            <h2 class="text-xl font-semibold text-ink">Theme Management</h2>

            <div v-if="Object.keys(themes).length" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div v-for="(theme, slug) in themes" :key="slug"
                    :class="['bg-surface rounded-lg shadow-sm overflow-hidden border-2 transition-colors', slug === activeTheme ? 'border-primary' : 'border-transparent hover:border-border']">
                    <!-- Color swatch preview -->
                    <div class="h-16 flex">
                        <div v-for="(color, key) in (theme.colors ?? {})" :key="key"
                            class="flex-1" :style="{ backgroundColor: color }"></div>
                    </div>

                    <div class="p-4">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h3 class="text-sm font-semibold text-ink">{{ theme.name }}</h3>
                                <p class="text-xs text-ink-subtle mt-0.5">v{{ theme.version }} · {{ theme.author }}</p>
                            </div>
                            <span v-if="slug === activeTheme"
                                class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary-dark">
                                Active
                            </span>
                        </div>
                        <p class="text-xs text-ink-muted mb-3">{{ theme.description }}</p>

                        <!-- Font info -->
                        <div class="text-xs text-ink-subtle mb-3">
                            <span>Heading: {{ theme.fonts?.heading }}</span>
                            <span class="mx-1">·</span>
                            <span>Body: {{ theme.fonts?.body }}</span>
                        </div>

                        <button v-if="slug !== activeTheme"
                            @click="activate(slug)"
                            class="w-full py-1.5 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-md">
                            Activate
                        </button>
                        <div v-else class="w-full py-1.5 text-center text-primary text-sm font-medium">
                            Currently Active
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="bg-surface rounded-lg shadow-sm p-12 text-center text-ink-subtle">
                <p class="text-sm">No themes found in resources/themes/.</p>
            </div>
        </div>
    </AppLayout>
</template>
