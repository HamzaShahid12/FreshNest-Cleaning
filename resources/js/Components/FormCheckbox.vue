<script setup>
import { useId } from 'vue';

defineProps({ modelValue: Boolean, label: String, error: String, hint: String });
defineEmits(['update:modelValue']);

const id = useId();
</script>

<template>
    <div>
        <label :for="id" class="flex cursor-pointer items-start gap-2.5 text-sm text-ink">
            <input
                :id="id" type="checkbox" :checked="modelValue"
                class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand"
                @change="$emit('update:modelValue', $event.target.checked)"
            >
            <span><slot>{{ label }}</slot></span>
        </label>
        <p v-if="hint" class="mt-0.5 pl-6 text-xs text-gray-500">{{ hint }}</p>
        <p v-if="error" class="field-error pl-6" role="alert">{{ error }}</p>
    </div>
</template>
