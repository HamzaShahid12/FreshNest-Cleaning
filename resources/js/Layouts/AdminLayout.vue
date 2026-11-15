<script setup>
import { ref } from 'vue';
import AdminHeader from '@/Components/AdminHeader.vue';
import AdminSidebar from '@/Components/AdminSidebar.vue';
import ToastNotification from '@/Components/ToastNotification.vue';

const drawer = ref(false);
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <aside class="no-print fixed inset-y-0 left-0 z-40 hidden w-64 lg:block"><AdminSidebar /></aside>

        <div v-if="drawer" class="no-print fixed inset-0 z-50 lg:hidden">
            <div class="absolute inset-0 bg-ink/50" @click="drawer = false" />
            <aside class="absolute inset-y-0 left-0 w-64"><AdminSidebar @navigate="drawer = false" /></aside>
        </div>

        <div class="lg:pl-64">
            <AdminHeader @menu="drawer = true" />
            <main class="p-4 sm:p-6"><slot /></main>
        </div>
        <ToastNotification />
    </div>
</template>
