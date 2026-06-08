<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PlanForm from '@/Components/PlanForm.vue';

const props = defineProps({ limitKeys: Array });

const limits = {};
props.limitKeys.forEach((k) => { limits[k] = ''; });

const form = useForm({
    name: '',
    slug: '',
    description: '',
    price_monthly: 0,
    price_yearly: 0,
    features: [],
    limits,
    is_active: true,
    sort_order: 0,
});

function submit() {
    form.post('/admin/plans');
}
</script>

<template>
    <AppLayout title="Add Plan">
        <div class="max-w-2xl mx-auto space-y-5">
            <div class="flex items-center gap-3">
                <Link href="/admin/plans" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <h2 class="text-xl font-semibold text-gray-900">Add Plan</h2>
            </div>
            <PlanForm :form="form" :limit-keys="limitKeys" submit-label="Create plan" @submit="submit" />
        </div>
    </AppLayout>
</template>
