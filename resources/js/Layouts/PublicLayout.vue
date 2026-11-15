<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppFooter from '@/Components/AppFooter.vue';
import AppHeader from '@/Components/AppHeader.vue';
import ToastNotification from '@/Components/ToastNotification.vue';

const page = usePage();
const announcement = computed(() => page.props.site?.announcement);
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <a href="#main" class="sr-only z-50 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-brand focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>
        <div v-if="announcement" class="bg-brand px-4 py-2 text-center text-sm font-medium text-white">{{ announcement }}</div>
        <AppHeader />
        <main id="main" class="flex-1"><slot /></main>
        <AppFooter />
        <ToastNotification />
    </div>
</template>
