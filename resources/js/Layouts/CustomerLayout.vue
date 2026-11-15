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
    { label: 'Dashboard', href: '/account', icon: 'grid', exact: true },
    { label: 'Bookings', href: '/account/bookings', icon: 'calendar' },
    { label: 'Addresses', href: '/account/addresses', icon: 'map-pin' },
    { label: 'Invoices', href: '/account/invoices', icon: 'document' },
    { label: 'Profile', href: '/account/profile', icon: 'user' },
    { label: 'Support', href: '/account/support', icon: 'chat' },
];
const isActive = (n) => (n.exact ? page.url.split('?')[0] === n.href : page.url.startsWith(n.href));
const resend = () => router.post('/email/verification-notification', {}, { preserveScroll: true });
</script>

<template>
    <div class="min-h-screen bg-brand-light">
        <header class="border-b border-gray-100 bg-white">
            <div class="container-x flex h-16 items-center justify-between">
                <Link href="/" class="flex items-center"><BrandLogo short /></Link>
                <div class="flex items-center gap-2">
                    <Link href="/account/profile" class="flex items-center gap-2 rounded-full pr-1 hover:bg-gray-50" aria-label="My profile"><Avatar :user="user" size="sm" /><span class="hidden text-sm text-gray-600 sm:inline">{{ user?.name }}</span></Link>
                    <Btn href="/book" size="sm">Book a Cleaning</Btn>
                    <Btn variant="ghost" size="sm" @click="router.post('/logout')">Sign out</Btn>
                </div>
            </div>
            <nav class="container-x -mb-px flex gap-1 overflow-x-auto" aria-label="Account">
                <Link
                    v-for="n in nav" :key="n.href" :href="n.href"
                    :class="['flex items-center gap-2 whitespace-nowrap border-b-2 px-3 py-3 text-sm font-medium transition', isActive(n) ? 'border-brand text-brand' : 'border-transparent text-gray-500 hover:text-ink']"
                ><Icon :name="n.icon" class="h-4 w-4" />{{ n.label }}</Link>
            </nav>
        </header>

        <div v-if="user && !user.verified" class="border-b border-amber-200 bg-amber-50 px-4 py-2.5 text-center text-sm text-amber-900">
            Please verify your email address. <button type="button" class="font-semibold underline" @click="resend">Resend verification email</button>
        </div>

        <main class="container-x py-8"><slot /></main>
        <ToastNotification />
    </div>
</template>
