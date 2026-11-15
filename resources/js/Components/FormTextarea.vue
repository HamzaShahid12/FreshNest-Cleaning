<script setup>
import { useId } from 'vue';

defineProps({
    modelValue: String, label: String, error: String, hint: String, placeholder: String,
    required: Boolean, rows: { type: [String, Number], default: 4 }, maxlength: [String, Number],
});
defineEmits(['update:modelValue']);

const id = useId();
</script>

<template>
    <div>
        <label v-if="label" :for="id" class="label">
            {{ label }}<span v-if="required" class="text-red-500" aria-hidden="true"> *</span>
        </label>
        <textarea
            :id="id" :value="modelValue" :rows="rows" :placeholder="placeholder" :required="required" :maxlength="maxlength"
            :aria-invalid="!!error" :class="['input', error && 'input-error']"
            @input="$emit('update:modelValue', $event.target.value)"
        />
        <p v-if="error" class="field-error" role="alert">{{ error }}</p>
        <p v-else-if="hint" class="mt-1 text-xs text-gray-500">{{ hint }}</p>
    </div>
</template>
