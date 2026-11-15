<script setup>
import { ref } from 'vue';
import SmartImage from '@/Components/SmartImage.vue';

// items: gallery rows { id, title, category, description, before_url, after_url }
defineProps({ items: { type: Array, default: () => [] } });

// Each card shows the "after" photo; hover/focus/tap flips to "before".
const showBefore = ref({});
const toggle = (id) => (showBefore.value[id] = !showBefore.value[id]);
</script>

<template>
    <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <li v-for="item in items" :key="item.id" class="card overflow-hidden">
            <button type="button" class="block w-full text-left" :aria-pressed="!!showBefore[item.id]" :aria-label="`${item.title}: showing ${showBefore[item.id] ? 'before' : 'after'} photo. Activate to switch.`" @click="toggle(item.id)">
                <div class="relative">
                    <SmartImage :src="showBefore[item.id] ? item.before_url : item.after_url" :alt="`${item.title} ${showBefore[item.id] ? 'before' : 'after'} cleaning`" :icon="showBefore[item.id] ? 'photo' : 'sparkles'" class="aspect-[4/3] w-full" />
                    <span :class="['absolute left-3 top-3 rounded-full px-3 py-1 text-xs font-semibold', showBefore[item.id] ? 'bg-ink text-white' : 'bg-brand text-white']">{{ showBefore[item.id] ? 'Before' : 'After' }}</span>
                </div>
            </button>
            <div class="p-4">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="font-semibold text-ink">{{ item.title }}</h3>
                    <span class="badge bg-brand-soft capitalize text-brand-dark">{{ item.category }}</span>
                </div>
                <p v-if="item.description" class="mt-1 text-sm text-body">{{ item.description }}</p>
                <p class="mt-2 text-xs text-gray-400">Tap the photo to compare before and after.</p>
            </div>
        </li>
    </ul>
</template>
