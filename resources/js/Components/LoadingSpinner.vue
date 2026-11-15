<script setup>
defineProps({ label: { type: String, default: 'Loading' }, size: { type: String, default: 'h-5 w-5' } });
</script>

<template>
    <span role="status" class="inline-flex items-center gap-2">
        <svg :class="['animate-spin', size]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25" />
            <path d="M22 12a10 10 0 00-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
        </svg>
        <span class="sr-only">{{ label }}</span>
    </span>
</template>
