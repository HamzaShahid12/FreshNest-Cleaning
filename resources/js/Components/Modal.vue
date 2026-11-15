<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({
    show: Boolean,
    title: String,
    maxWidth: { type: String, default: 'max-w-lg' },
});
const emit = defineEmits(['close']);

const panel = ref(null);
const onKey = (e) => { if (e.key === 'Escape') emit('close'); };

watch(() => props.show, async (open) => {
    if (open) {
        document.addEventListener('keydown', onKey);
        document.body.style.overflow = 'hidden';
        await nextTick();
        panel.value?.querySelector('input, select, textarea, button')?.focus();
    } else {
        document.removeEventListener('keydown', onKey);
        document.body.style.overflow = '';
    }
});
onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKey);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center" role="dialog" aria-modal="true" :aria-label="title">
            <div class="absolute inset-0 bg-ink/50" @click="emit('close')" />
            <div ref="panel" :class="['relative max-h-[90vh] w-full overflow-y-auto rounded-2xl bg-white p-6 shadow-xl', maxWidth]">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <h2 v-if="title" class="font-display text-xl font-semibold text-ink">{{ title }}</h2>
                    <button type="button" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-ink" aria-label="Close dialog" @click="emit('close')">
                        <Icon name="x" />
                    </button>
                </div>
                <slot />
            </div>
        </div>
    </Teleport>
</template>
