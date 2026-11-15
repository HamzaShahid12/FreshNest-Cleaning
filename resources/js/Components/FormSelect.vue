<script setup>
import { useId } from 'vue';

defineProps({
    modelValue: [String, Number, null],
    label: String,
    error: String,
    required: Boolean,
    placeholder: String,
    // [{ value, label }]
    options: { type: Array, default: () => [] },
});
defineEmits(['update:modelValue']);

const id = useId();
</script>

<template>
    <div>
        <label v-if="label" :for="id" class="label">
            {{ label }}<span v-if="required" class="text-red-500" aria-hidden="true"> *</span>
        </label>
        <select
            :id="id" :value="modelValue ?? ''" :required="required" :aria-invalid="!!error" :class="['input', error && 'input-error']"
            @change="$emit('update:modelValue', $event.target.value)"
        >
            <option v-if="placeholder !== undefined" value="">{{ placeholder }}</option>
            <option v-for="o in options" :key="o.value" :value="o.value">{{ o.label }}</option>
        </select>
        <p v-if="error" class="field-error" role="alert">{{ error }}</p>
    </div>
</template>
