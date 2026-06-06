<script setup>
defineProps({ disabled: Boolean });
</script>
<template>
    <button
        :disabled="disabled"
        class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
        v-bind="$attrs"
    >
        <slot />
    </button>
</template>
