<script setup>
import { onMounted, ref } from 'vue';
const model = defineModel();
defineProps({ type: { type: String, default: 'text' } });
const input = ref(null);
onMounted(() => { if (input.value?.hasAttribute('autofocus')) input.value.focus(); });
defineExpose({ focus: () => input.value.focus() });
</script>
<template>
    <input
        ref="input"
        v-model="model"
        :type="type"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
        v-bind="$attrs"
    />
</template>
