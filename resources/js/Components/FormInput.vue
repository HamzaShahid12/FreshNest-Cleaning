<script setup>
import { computed, useId } from 'vue';

const props = defineProps({
    modelValue: [String, Number, null],
    label: String,
    type: { type: String, default: 'text' },
    error: String,
    hint: String,
    placeholder: String,
    required: Boolean,
    disabled: Boolean,
    autocomplete: String,
    step: [String, Number],
    min: [String, Number],
    max: [String, Number],
    maxlength: [String, Number],
    inputmode: String,
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
            :id="id" :type="type" :value="modelValue" :placeholder="placeholder" :required="required" :disabled="disabled"
            :autocomplete="autocomplete" :step="step" :min="min" :max="max" :maxlength="maxlength" :inputmode="inputmode"
            :aria-invalid="!!error" :aria-describedby="describedBy"
            :class="['input', error && 'input-error']"
            @input="$emit('update:modelValue', $event.target.value)"
        >
        <p v-if="error" :id="`${id}-err`" class="field-error" role="alert">{{ error }}</p>
        <p v-else-if="hint" :id="`${id}-hint`" class="mt-1 text-xs text-gray-500">{{ hint }}</p>
    </div>
</template>
