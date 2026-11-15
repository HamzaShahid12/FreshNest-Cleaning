<script setup>
import { ref, watch } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
    src: String,
    alt: { type: String, default: '' },
    icon: { type: String, default: 'sparkles' },
    rounded: { type: String, default: '' },
});

// Graceful fallback: a branded placeholder is shown when there is no image or it fails to load.
const failed = ref(false);
watch(() => props.src, () => (failed.value = false));
</script>

<template>
    <div :class="['relative overflow-hidden bg-brand-soft', rounded]">
        <img
            v-if="src && !failed" :src="src" :alt="alt" loading="lazy" decoding="async"
            class="h-full w-full object-cover" @error="failed = true"
        >
        <div v-else class="flex h-full w-full items-center justify-center bg-gradient-to-br from-brand-soft to-green-100 text-brand" role="img" :aria-label="alt || 'Placeholder image'">
            <Icon :name="icon" class="h-10 w-10 opacity-70" />
        </div>
    </div>
</template>
