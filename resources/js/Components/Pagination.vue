<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

// Accepts the `links` array of a Laravel paginator.
const props = defineProps({ links: { type: Array, default: () => [] } });

const items = computed(() => props.links.map((l) => ({
    ...l,
    text: l.label.includes('Previous') ? 'Previous' : l.label.includes('Next') ? 'Next' : l.label,
})));
const show = computed(() => props.links.length > 3);
</script>

<template>
    <nav v-if="show" class="flex flex-wrap items-center justify-center gap-1" aria-label="Pagination">
        <template v-for="(l, i) in items" :key="i">
            <span v-if="!l.url" class="rounded-lg px-3 py-1.5 text-sm text-gray-400">{{ l.text }}</span>
            <Link
                v-else :href="l.url" preserve-scroll
                :class="['rounded-lg px-3 py-1.5 text-sm font-medium transition', l.active ? 'bg-brand text-white' : 'text-body hover:bg-gray-100']"
                :aria-current="l.active ? 'page' : undefined"
            >{{ l.text }}</Link>
        </template>
    </nav>
</template>
