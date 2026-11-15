<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Avatar from '@/Components/Avatar.vue';
import Btn from '@/Components/Btn.vue';
import Icon from '@/Components/Icon.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

defineEmits(['menu']);
const user = computed(() => usePage().props.auth?.user);
</script>

<template>
    <header class="no-print sticky top-0 z-30 flex h-16 items-center justify-between gap-3 border-b border-gray-100 bg-white px-4 sm:px-6">
        <button type="button" class="rounded-lg p-2 text-ink hover:bg-gray-100 lg:hidden" aria-label="Open menu" @click="$emit('menu')"><Icon name="menu" class="h-6 w-6" /></button>
        <Link href="/admin/profile" class="hidden items-center gap-2 rounded-full pr-2 text-sm text-gray-500 hover:bg-gray-50 lg:flex" aria-label="My profile"><Avatar :user="user" size="sm" />Signed in as <span class="font-medium text-ink">{{ user?.name }}</span> <StatusBadge :status="user?.role" class="ml-1" /></Link>
        <div class="ml-auto flex items-center gap-2">
            <Link href="/admin/profile" class="lg:hidden" aria-label="My profile"><Avatar :user="user" size="sm" /></Link>
            <Link href="/" class="hidden text-sm text-gray-500 hover:text-brand sm:inline">View website</Link>
            <Btn variant="outline" size="sm" @click="router.post('/logout')"><Icon name="logout" class="h-4 w-4" />Sign out</Btn>
        </div>
    </header>
</template>
