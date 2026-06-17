<script setup>
import { computed } from 'vue';

const props = defineProps({ label: String, error: String, hint: String, required: Boolean, id: String });
const fieldId = props.id ?? `f-${Math.random().toString(36).slice(2, 9)}`;
const errorId = `${fieldId}-error`;
const hintId = `${fieldId}-hint`;

// Slot props let inputs wire a11y: aria-describedby points at whichever helper
// text is shown, aria-invalid reflects the error state.
const describedby = computed(() => (props.error ? errorId : props.hint ? hintId : undefined));
const invalid = computed(() => (props.error ? true : undefined));
</script>

<template>
    <div>
        <label v-if="label" :for="fieldId" class="block text-sm font-medium text-ink mb-1">
            {{ label }} <span v-if="required" class="text-danger">*</span>
        </label>
        <slot :id="fieldId" :describedby="describedby" :invalid="invalid" />
        <p v-if="error" :id="errorId" class="text-xs text-danger mt-1">{{ error }}</p>
        <p v-else-if="hint" :id="hintId" class="text-xs text-ink-subtle mt-1">{{ hint }}</p>
    </div>
</template>
