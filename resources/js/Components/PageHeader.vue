<script setup>
defineProps({ title: String, subtitle: String });
</script>

<template>
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-semibold text-ink sm:text-3xl">{{ title }}</h1>
            <p v-if="subtitle" class="mt-1 text-sm text-gray-500">{{ subtitle }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2"><slot /></div>
    </div>
</template>
