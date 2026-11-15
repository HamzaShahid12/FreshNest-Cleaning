<script setup>
import BrandLogo from '@/Components/BrandLogo.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Icon from '@/Components/Icon.vue';

const page = usePage();
const site = computed(() => page.props.site || {});
const social = computed(() => Object.entries(site.value.social || {}).filter(([, url]) => url));
const year = new Date().getFullYear();

const columns = [
    { title: 'Services', links: [['Standard cleaning', '/services/standard-house-cleaning'], ['Deep cleaning', '/services/deep-house-cleaning'], ['Move in / out', '/services/move-in-cleaning'], ['Office cleaning', '/services/office-cleaning'], ['All services', '/services']] },
    { title: 'Company', links: [['About us', '/about'], ['Pricing', '/pricing'], ['Gallery', '/gallery'], ['Reviews', '/reviews'], ['Blog', '/blog'], ['Service areas', '/areas']] },
    { title: 'Support', links: [['Book online', '/book'], ['FAQ', '/faq'], ['Contact', '/contact'], ['My account', '/account'], ['Staff sign in', '/staff/login']] },
];
const legal = [['Privacy Policy', '/legal/privacy'], ['Terms & Conditions', '/legal/terms'], ['Cancellation Policy', '/legal/cancellation']];
</script>

<template>
    <footer class="bg-ink text-gray-300">
        <div class="container-x grid gap-10 py-14 md:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <BrandLogo dark />
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-gray-400">{{ site.tagline }} Trusted residential and commercial cleaning for homeowners, renters, landlords and offices.</p>
                <ul class="mt-5 space-y-2 text-sm">
                    <li v-if="site.phone" class="flex items-center gap-2"><Icon name="phone" class="h-4 w-4 text-brand" /><a :href="`tel:${site.phone}`" class="hover:text-white">{{ site.phone }}</a></li>
                    <li v-if="site.email" class="flex items-center gap-2"><Icon name="mail" class="h-4 w-4 text-brand" /><a :href="`mailto:${site.email}`" class="hover:text-white">{{ site.email }}</a></li>
                    <li v-if="site.address" class="flex items-center gap-2"><Icon name="map-pin" class="h-4 w-4 text-brand" />{{ site.address }}</li>
                    <li v-if="site.hours" class="flex items-center gap-2"><Icon name="clock" class="h-4 w-4 text-brand" />{{ site.hours }}</li>
                </ul>
                <div v-if="social.length" class="mt-5 flex flex-wrap gap-3 text-sm">
                    <a v-for="[name, url] in social" :key="name" :href="url" target="_blank" rel="noopener noreferrer" class="rounded-lg bg-white/10 px-3 py-1.5 capitalize hover:bg-white/20">{{ name === 'x' ? 'X' : name }}</a>
                </div>
            </div>
            <div v-for="c in columns" :key="c.title">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-white">{{ c.title }}</h3>
                <ul class="mt-4 space-y-2 text-sm">
                    <li v-for="[label, href] in c.links" :key="href"><Link :href="href" class="hover:text-white">{{ label }}</Link></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="container-x flex flex-col items-center justify-between gap-3 py-5 text-xs text-gray-400 sm:flex-row">
                <p>&copy; {{ year }} {{ site.name }}. All rights reserved.</p>
                <ul class="flex flex-wrap gap-x-5 gap-y-1">
                    <li v-for="[label, href] in legal" :key="href"><Link :href="href" class="hover:text-white">{{ label }}</Link></li>
                </ul>
            </div>
        </div>
    </footer>
</template>
