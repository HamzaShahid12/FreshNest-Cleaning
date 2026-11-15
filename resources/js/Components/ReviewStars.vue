<script setup>
import { ref } from 'vue';
import Icon from '@/Components/Icon.vue';

// Read-only by default. Add `interactive` + v-model for a rating picker.
const props = defineProps({
    rating: { type: Number, default: 0 },
    modelValue: { type: Number, default: 0 },
    interactive: Boolean,
    size: { type: String, default: 'h-5 w-5' },
});
const emit = defineEmits(['update:modelValue']);
const hover = ref(0);
const value = () => (props.interactive ? hover.value || props.modelValue : props.rating);
</script>

<template>
    <div class="inline-flex items-center gap-0.5" role="img" :aria-label="`${interactive ? modelValue : rating} out of 5 stars`">
        <template v-for="n in 5" :key="n">
            <button
                v-if="interactive" type="button" class="text-amber-400 transition hover:scale-110" :aria-label="`${n} star${n > 1 ? 's' : ''}`"
                @mouseenter="hover = n" @mouseleave="hover = 0" @click="emit('update:modelValue', n)"
            >
                <Icon name="star" :filled="n <= value()" :class="size" />
            </button>
            <Icon v-else name="star" :filled="n <= Math.round(value())" :class="[size, n <= Math.round(value()) ? 'text-amber-400' : 'text-gray-300']" />
        </template>
    </div>
</template>
