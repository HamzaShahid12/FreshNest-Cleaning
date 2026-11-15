<script setup>
import { computed, useId } from 'vue';

// Accessible wrapper around the native date input (value format: YYYY-MM-DD).
const props = defineProps({
    modelValue: String,
    label: String,
    min: String,
    max: String,
    error: String,
    hint: String,
    required: Boolean,
});
defineEmits(['update:modelValue']);

const id = useId();
const describedBy = computed(() => (props.error ? `${id}-err` : props.hint ? `${id}-hint` : undefined));
</script>

<template>
    <div>
        <label v-if="label" :for="id" class="label">
            {{ label }}<span v-if="required" class="text-red-500" aria-hidden="true"> *</span>
        </label>
        <input
            :id="id" type="date" :value="modelValue" :min="min" :max="max" :required="required"
            :aria-invalid="!!error" :aria-describedby="describedBy" :class="['input', error && 'input-error']"
            @input="$emit('update:modelValue', $event.target.value)"
        >
        <p v-if="error" :id="`${id}-err`" class="field-error" role="alert">{{ error }}</p>
        <p v-else-if="hint" :id="`${id}-hint`" class="mt-1 text-xs text-gray-500">{{ hint }}</p>
    </div>
</template>
