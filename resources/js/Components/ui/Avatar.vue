<script setup>
import { computed } from 'vue';
const props = defineProps({
    name: String,
    src: String,
    size: { type: String, default: 'md' }, // sm | md | lg
});
const sizes = { sm: 'w-8 h-8 text-xs', md: 'w-10 h-10 text-sm', lg: 'w-14 h-14 text-lg' };
const initial = computed(() => props.name?.charAt(0)?.toUpperCase() ?? '?');
</script>

<template>
    <div :class="['rounded-full bg-primary/10 text-primary flex items-center justify-center font-semibold overflow-hidden flex-shrink-0', sizes[size] ?? sizes.md]">
        <img v-if="src" :src="src" alt="" class="w-full h-full object-cover" />
        <span v-else>{{ initial }}</span>
    </div>
</template>
