<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PlanForm from '@/Components/PlanForm.vue';

const props = defineProps({ plan: Object, limitKeys: Array });

// Seed limit inputs from known keys so every field renders even if unset.
const limits = {};
props.limitKeys.forEach((k) => { limits[k] = props.plan.limits?.[k] ?? ''; });

const form = useForm({
    name: props.plan.name,
    slug: props.plan.slug,
    description: props.plan.description ?? '',
    price_monthly: props.plan.price_monthly,
    price_yearly: props.plan.price_yearly,
    features: [...(props.plan.features ?? [])],
    limits,
    is_active: props.plan.is_active,
    sort_order: props.plan.sort_order,
});

function submit() {
    form.put(`/admin/plans/${props.plan.id}`);
}
</script>

<template>
    <AppLayout title="Edit Plan">
        <div class="max-w-2xl mx-auto space-y-5">
            <div class="flex items-center gap-3">
                <Link href="/admin/plans" class="text-ink-subtle hover:text-ink-muted">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <h2 class="text-xl font-semibold text-ink">Edit {{ plan.name }}</h2>
            </div>
            <PlanForm :form="form" :limit-keys="limitKeys" submit-label="Save changes" @submit="submit" />
        </div>
    </AppLayout>
</template>
