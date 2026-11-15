<script setup>
import { computed } from 'vue';
import Icon from '@/Components/Icon.vue';

// steps: [{ key, label }]; current is 1-based; completed steps (<= maxReached) can be clicked to go back.
const props = defineProps({
    steps: { type: Array, required: true },
    current: { type: Number, required: true },
    maxReached: { type: Number, default: 1 },
});
const emit = defineEmits(['go']);

const percent = computed(() => Math.round(((props.current - 1) / (props.steps.length - 1)) * 100));
const label = computed(() => props.steps[props.current - 1]?.label);
</script>

<template>
    <nav aria-label="Booking progress">
        <!-- Small screens: compact bar -->
        <div class="md:hidden">
            <div class="flex items-center justify-between text-sm">
                <span class="font-semibold text-ink">Step {{ current }} of {{ steps.length }}</span>
                <span class="text-gray-500">{{ label }}</span>
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-200" role="progressbar" :aria-valuenow="current" aria-valuemin="1" :aria-valuemax="steps.length">
                <div class="h-full rounded-full bg-brand transition-all duration-300" :style="{ width: `${percent}%` }" />
            </div>
        </div>

        <!-- Tablet / desktop: numbered steps -->
        <ol class="hidden items-start md:flex">
            <li v-for="(s, i) in steps" :key="s.key" :class="['flex items-start', i < steps.length - 1 && 'flex-1']">
                <button
                    type="button" :disabled="i + 1 > maxReached" :aria-current="i + 1 === current ? 'step' : undefined"
                    class="group flex flex-col items-center gap-1.5 disabled:cursor-not-allowed" @click="emit('go', i + 1)"
                >
                    <span
                        :class="['flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold transition',
                                 i + 1 === current ? 'bg-brand text-white ring-4 ring-brand-soft'
                                 : i + 1 < current || i + 1 <= maxReached ? 'bg-brand-soft text-brand-dark group-hover:bg-green-100'
                                 : 'bg-gray-100 text-gray-400']"
                    >
                        <Icon v-if="i + 1 < current" name="check" class="h-4 w-4" />
                        <template v-else>{{ i + 1 }}</template>
                    </span>
                    <span :class="['hidden text-xs lg:block', i + 1 === current ? 'font-semibold text-ink' : 'text-gray-500']">{{ s.label }}</span>
                </button>
                <span v-if="i < steps.length - 1" :class="['mx-1 mt-4 h-0.5 flex-1 rounded', i + 1 < current ? 'bg-brand' : 'bg-gray-200']" aria-hidden="true" />
            </li>
        </ol>
        <p class="mt-2 hidden text-center text-sm font-medium text-ink md:block lg:hidden">{{ label }}</p>
    </nav>
</template>
