<script setup>
import icons from '@/icons';

defineProps({ name: { type: String, required: true }, filled: Boolean });
</script>

<template>
    <svg
        viewBox="0 0 24 24" width="20" height="20" :fill="filled ? 'currentColor' : 'none'" stroke="currentColor"
        stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="shrink-0"
    >
        <path v-for="(d, i) in icons[name] || []" :key="i" :d="d" />
    </svg>
</template>
