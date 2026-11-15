<script setup>
import { ref } from 'vue';
import Icon from '@/Components/Icon.vue';

// items: [{ question, answer }]
defineProps({ items: { type: Array, default: () => [] } });
const open = ref(null);
</script>

<template>
    <div class="divide-y divide-gray-100 rounded-2xl border border-gray-100 bg-white shadow-card">
        <div v-for="(item, i) in items" :key="item.id ?? i">
            <h3>
                <button
                    type="button" class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left font-semibold text-ink hover:text-brand"
                    :aria-expanded="open === i" @click="open = open === i ? null : i"
                >
                    <span>{{ item.question }}</span>
                    <Icon name="chevron-down" :class="['transition-transform', open === i && 'rotate-180']" />
                </button>
            </h3>
            <div v-show="open === i" class="px-5 pb-5 text-sm leading-relaxed text-body">{{ item.answer }}</div>
        </div>
        <p v-if="!items.length" class="px-5 py-8 text-center text-sm text-gray-500">No questions found.</p>
    </div>
</template>
