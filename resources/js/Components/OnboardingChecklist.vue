<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { Card, Button } from '@/Components/ui';

const page = usePage();
const onboarding = computed(() => page.props.onboarding);

const items = [
    { key: 'branding', label: 'Brand your workspace' },
    { key: 'venue',    label: 'Add your first venue' },
    { key: 'package',  label: 'Create a package' },
    { key: 'team',     label: 'Invite a team member' },
];

const doneCount = computed(() => items.filter((i) => onboarding.value?.[i.key]).length);
const dismiss = () => router.post('/admin/onboarding/finish');
</script>

<template>
    <Card v-if="onboarding?.show_checklist" body-class="p-5">
        <div class="flex items-start justify-between">
            <div>
                <h3 class="font-display text-lg font-semibold text-ink">Getting started</h3>
                <p class="text-sm text-ink-muted mt-0.5">{{ doneCount }} of {{ items.length }} steps complete</p>
            </div>
            <button class="text-sm text-ink-subtle hover:text-ink" @click="dismiss">Dismiss</button>
        </div>

        <ul class="mt-4 space-y-2">
            <li v-for="item in items" :key="item.key">
                <a href="/admin/onboarding"
                   class="flex items-center gap-3 rounded-lg border border-border px-3 py-2.5 hover:bg-surface-muted transition-colors">
                    <span :class="[
                        'w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0',
                        onboarding[item.key] ? 'bg-success text-white' : 'border-2 border-border'
                    ]">
                        <svg v-if="onboarding[item.key]" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </span>
                    <span :class="['text-sm', onboarding[item.key] ? 'text-ink-subtle line-through' : 'text-ink font-medium']">{{ item.label }}</span>
                    <svg v-if="!onboarding[item.key]" class="w-4 h-4 text-ink-subtle ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </li>
        </ul>
    </Card>
</template>
