<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Avatar from '@/Components/Avatar.vue';
import BrandLogo from '@/Components/BrandLogo.vue';
import Btn from '@/Components/Btn.vue';
import Icon from '@/Components/Icon.vue';
import ToastNotification from '@/Components/ToastNotification.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const nav = [
    { label: 'Today & upcoming', href: '/staff', exact: true },
    { label: 'Job history', href: '/staff/history' },
    { label: 'My profile', href: '/staff/profile' },
];
const isActive = (n) => (n.exact ? page.url.split('?')[0] === n.href : page.url.startsWith(n.href));
</script>

<template>
    <div class="min-h-screen bg-brand-light">
        <header class="border-b border-gray-100 bg-white">
            <div class="container-x flex h-16 items-center justify-between">
                <Link href="/staff" class="flex items-center gap-2"><BrandLogo short /><span class="rounded bg-brand-soft px-1.5 py-0.5 font-sans text-xs font-semibold text-brand-dark">Staff</span></Link>
                <div class="flex items-center gap-2">
                    <Link href="/staff/profile" class="flex items-center gap-2 rounded-full pr-1 hover:bg-gray-50" aria-label="My profile"><Avatar :user="user" size="sm" /><span class="hidden text-sm text-gray-600 sm:inline">{{ user?.name }}</span></Link>
                    <Btn variant="ghost" size="sm" @click="router.post('/logout')">Sign out</Btn>
                </div>
            </div>
            <nav class="container-x -mb-px flex gap-1" aria-label="Staff">
                <Link
                    v-for="n in nav" :key="n.href" :href="n.href"
                    :class="['whitespace-nowrap border-b-2 px-3 py-3 text-sm font-medium', isActive(n) ? 'border-brand text-brand' : 'border-transparent text-gray-500 hover:text-ink']"
                >{{ n.label }}</Link>
            </nav>
        </header>
        <main class="container-x max-w-4xl py-8"><slot /></main>
        <ToastNotification />
    </div>
</template>
