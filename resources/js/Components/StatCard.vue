<script setup>
import Icon from '@/Components/Icon.vue';

defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], default: '-' },
    icon: { type: String, default: 'chart' },
    hint: String,
    tone: { type: String, default: 'green' }, // green | blue | amber | purple | gray
});

const tones = {
    green: 'bg-brand-soft text-brand', blue: 'bg-blue-50 text-blue-600', amber: 'bg-amber-50 text-amber-600',
    purple: 'bg-purple-50 text-purple-600', gray: 'bg-gray-100 text-gray-600',
};
</script>

<template>
    <div class="card flex items-center gap-4 p-4 sm:p-5">
        <span :class="['flex h-11 w-11 shrink-0 items-center justify-center rounded-xl', tones[tone] || tones.green]"><Icon :name="icon" class="h-5 w-5" /></span>
        <div class="min-w-0">
            <p class="truncate text-xs font-medium uppercase tracking-wide text-gray-500">{{ label }}</p>
            <p class="truncate font-display text-2xl font-semibold text-ink">{{ value }}</p>
            <p v-if="hint" class="truncate text-xs text-gray-400">{{ hint }}</p>
        </div>
    </div>
</template>
