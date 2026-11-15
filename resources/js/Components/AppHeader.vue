<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Avatar from '@/Components/Avatar.vue';
import BrandLogo from '@/Components/BrandLogo.vue';
import Btn from '@/Components/Btn.vue';
import Icon from '@/Components/Icon.vue';
import { profileHref } from '@/Composables/useProfileLink';

const page = usePage();
const site = computed(() => page.props.site || {});
const user = computed(() => page.props.auth?.user);
const open = ref(false);

const links = [
    { label: 'Services', href: '/services' },
    { label: 'Pricing', href: '/pricing' },
    { label: 'Gallery', href: '/gallery' },
    { label: 'Reviews', href: '/reviews' },
    { label: 'Areas', href: '/areas' },
    { label: 'About', href: '/about' },
    { label: 'Blog', href: '/blog' },
    { label: 'Contact', href: '/contact' },
];

const dashboard = computed(() => {
    if (!user.value) return null;
    if (user.value.is_admin) return { label: 'Admin', href: '/admin' };
    if (user.value.is_staff) return { label: 'My jobs', href: '/staff' };

    return { label: 'My account', href: '/account' };
});
const active = (href) => page.url === href || page.url.startsWith(`${href}/`) || page.url.startsWith(`${href}?`);
const logout = () => router.post('/logout');
</script>

<template>
    <header class="sticky top-0 z-40 border-b border-gray-100 bg-white/90 backdrop-blur">
        <div class="container-x flex h-16 items-center justify-between gap-4">
            <Link href="/" class="flex items-center" :aria-label="`${site.name || 'FreshNest'} home`"><BrandLogo /></Link>

            <nav class="hidden items-center gap-1 lg:flex" aria-label="Main">
                <Link
                    v-for="l in links" :key="l.href" :href="l.href"
                    :class="['rounded-lg px-3 py-2 text-sm font-medium transition', active(l.href) ? 'text-brand' : 'text-body hover:text-brand']"
                >{{ l.label }}</Link>
            </nav>

            <div class="hidden items-center gap-2 lg:flex">
                <template v-if="user">
                    <Link :href="profileHref(user)" class="rounded-full" :aria-label="`${user.name}: my profile`"><Avatar :user="user" size="sm" /></Link>
                    <Btn :href="dashboard.href" variant="ghost" size="sm">{{ dashboard.label }}</Btn>
                    <Btn variant="ghost" size="sm" @click="logout">Sign out</Btn>
                </template>
                <Btn v-else href="/login" variant="ghost" size="sm">Sign in</Btn>
                <Btn href="/book">Book a Cleaning</Btn>
            </div>

            <button type="button" class="rounded-lg p-2 text-ink hover:bg-gray-100 lg:hidden" :aria-expanded="open" aria-label="Toggle menu" @click="open = !open">
                <Icon :name="open ? 'x' : 'menu'" class="h-6 w-6" />
            </button>
        </div>

        <div v-if="open" class="border-t border-gray-100 bg-white lg:hidden">
            <nav class="container-x flex flex-col gap-1 py-4" aria-label="Mobile">
                <Link v-for="l in links" :key="l.href" :href="l.href" class="rounded-lg px-3 py-2.5 font-medium text-ink hover:bg-brand-soft" @click="open = false">{{ l.label }}</Link>
                <div class="mt-3 flex flex-col gap-2 border-t border-gray-100 pt-4">
                    <Btn href="/book" block>Book a Cleaning</Btn>
                    <Link v-if="user" :href="profileHref(user)" class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-brand-soft" @click="open = false"><Avatar :user="user" size="sm" /><span class="text-sm font-medium text-ink">{{ user.name }}</span></Link>
                    <template v-if="user">
                        <Btn :href="dashboard.href" variant="outline" block>{{ dashboard.label }}</Btn>
                        <Btn variant="ghost" block @click="logout">Sign out</Btn>
                    </template>
                    <Btn v-else href="/login" variant="outline" block>Sign in</Btn>
                </div>
            </nav>
        </div>
    </header>
</template>
