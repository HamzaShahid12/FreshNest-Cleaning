<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import Avatar from '@/Components/Avatar.vue';
import BrandLogo from '@/Components/BrandLogo.vue';
import Btn from '@/Components/Btn.vue';
import Icon from '@/Components/Icon.vue';
import { profileHref } from '@/Composables/useProfileLink';

const page = usePage();
const site = computed(() => page.props.site || {});
const user = computed(() => page.props.auth?.user);

const open = ref(false);
const activeDropdown = ref(null);
const navEl = ref(null);

// Menu: Home | Services ▾ | Pricing | Company ▾ | Service Areas ▾ | Contact
// Service and area entries come from the database (Admin → Services / Service areas), so links always exist.
const menus = computed(() => [
    {
        label: 'Services', href: '/services',
        children: [{ label: 'All Services', href: '/services' }, ...(page.props.nav?.services || []).map((s) => ({ label: s.name, href: `/services/${s.slug}` }))],
    },
    {
        label: 'Company', href: '/about',
        children: [
            { label: 'About Us', href: '/about' },
            { label: 'Our Gallery', href: '/gallery' },
            { label: 'Customer Reviews', href: '/reviews' },
            { label: 'Our Blog', href: '/blog' },
            { label: 'FAQ', href: '/faq' },
        ],
    },
    {
        label: 'Service Areas', href: '/areas',
        children: [{ label: 'All Service Areas', href: '/areas' }, ...(page.props.nav?.areas || []).map((a) => ({ label: a.name, href: `/areas/${a.slug}` }))],
    },
]);

const dashboard = computed(() => {
    if (!user.value) return null;
    if (user.value.is_admin) return { label: 'Admin', href: '/admin' };
    if (user.value.is_staff) return { label: 'My Jobs', href: '/staff' };

    return { label: 'My Account', href: '/account' };
});

const path = computed(() => page.url.split('?')[0]);
const active = (href) => (href === '/' ? path.value === '/' : path.value === href || path.value.startsWith(`${href}/`));
const menuActive = (menu) => menu.children.some((c) => active(c.href));

const logout = () => router.post('/logout');
const toggleDropdown = (label) => { activeDropdown.value = activeDropdown.value === label ? null : label; };
const closeAll = () => { activeDropdown.value = null; open.value = false; };

// Close dropdowns on outside click, Escape, and after navigating.
const onDocClick = (e) => { if (navEl.value && !navEl.value.contains(e.target)) activeDropdown.value = null; };
const onKey = (e) => { if (e.key === 'Escape') activeDropdown.value = null; };
let offNavigate = null;
onMounted(() => {
    document.addEventListener('click', onDocClick);
    document.addEventListener('keydown', onKey);
    offNavigate = router.on('navigate', closeAll);
});
onBeforeUnmount(() => {
    document.removeEventListener('click', onDocClick);
    document.removeEventListener('keydown', onKey);
    if (typeof offNavigate === 'function') offNavigate();
});
</script>

<template>
    <header class="sticky top-0 z-40 border-b border-gray-100 bg-white/95 backdrop-blur">
        <div class="container-x flex h-16 items-center justify-between gap-4 xl:grid xl:grid-cols-[minmax(max-content,1fr)_auto_minmax(max-content,1fr)]">
            <Link href="/" class="flex items-center justify-self-start" :aria-label="`${site.name || 'FreshNest'} home`"><BrandLogo place="header" /></Link>

            <!-- Desktop navigation -->
            <nav ref="navEl" class="hidden items-center justify-center gap-0.5 justify-self-center whitespace-nowrap xl:flex" aria-label="Main navigation">
                <Link href="/" :class="['rounded-lg px-3 py-2 text-sm font-medium transition', active('/') ? 'text-brand' : 'text-body hover:text-brand']">Home</Link>

                <div v-for="menu in menus" :key="menu.label" class="relative">
                    <button
                        type="button" aria-haspopup="true" :aria-expanded="activeDropdown === menu.label"
                        :class="['flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium transition hover:text-brand', menuActive(menu) ? 'text-brand' : 'text-body']"
                        @click="toggleDropdown(menu.label)"
                    >
                        {{ menu.label }}
                        <Icon name="chevron-down" :class="['h-4 w-4 transition-transform', activeDropdown === menu.label && 'rotate-180']" />
                    </button>
                    <div v-if="activeDropdown === menu.label" class="absolute left-1/2 top-full z-50 mt-2 max-h-96 w-64 -translate-x-1/2 overflow-y-auto rounded-xl border border-gray-100 bg-white p-2 shadow-xl">
                        <Link
                            v-for="item in menu.children" :key="item.href" :href="item.href"
                            :class="['block rounded-lg px-4 py-2.5 text-sm transition hover:bg-brand-soft hover:text-brand', active(item.href) ? 'font-semibold text-brand' : 'text-body']"
                            @click="activeDropdown = null"
                        >{{ item.label }}</Link>
                    </div>
                </div>

                <Link href="/pricing" :class="['rounded-lg px-3 py-2 text-sm font-medium transition', active('/pricing') ? 'text-brand' : 'text-body hover:text-brand']">Pricing</Link>
                <Link href="/contact" :class="['rounded-lg px-3 py-2 text-sm font-medium transition', active('/contact') ? 'text-brand' : 'text-body hover:text-brand']">Contact</Link>
            </nav>

            <!-- Desktop actions -->
            <div class="hidden items-center gap-1.5 justify-self-end whitespace-nowrap xl:flex">
                <Btn href="/book">Book a Cleaning</Btn>
                <template v-if="user">
                    <Link :href="profileHref(user)" class="rounded-full" :aria-label="`${user.name}: my profile`"><Avatar :user="user" size="sm" /></Link>
                    <Btn :href="dashboard.href" variant="ghost" size="sm">{{ dashboard.label }}</Btn>
                    <Btn variant="ghost" size="sm" @click="logout">Sign out</Btn>
                </template>
                <Btn v-else href="/login" variant="ghost" size="sm">Sign in</Btn>
            </div>

            <button type="button" class="rounded-lg p-2 text-ink hover:bg-gray-100 xl:hidden" :aria-expanded="open" aria-label="Toggle menu" @click="open = !open">
                <Icon :name="open ? 'x' : 'menu'" class="h-6 w-6" />
            </button>
        </div>

        <!-- Mobile navigation -->
        <div v-if="open" class="max-h-[calc(100vh-4rem)] overflow-y-auto border-t border-gray-100 bg-white xl:hidden">
            <nav class="container-x flex flex-col gap-1 py-4" aria-label="Mobile navigation">
                <Link href="/" class="rounded-lg px-3 py-2.5 font-medium text-ink hover:bg-brand-soft" @click="open = false">Home</Link>

                <div v-for="menu in menus" :key="menu.label">
                    <button type="button" :aria-expanded="activeDropdown === menu.label" class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 font-medium text-ink hover:bg-brand-soft" @click="toggleDropdown(menu.label)">
                        {{ menu.label }}
                        <Icon name="chevron-down" :class="['h-4 w-4 transition-transform', activeDropdown === menu.label && 'rotate-180']" />
                    </button>
                    <div v-if="activeDropdown === menu.label" class="ml-3 border-l-2 border-gray-100 py-1 pl-3">
                        <Link v-for="item in menu.children" :key="item.href" :href="item.href" class="block rounded-lg px-3 py-2.5 text-sm text-body hover:bg-brand-soft hover:text-brand" @click="closeAll">{{ item.label }}</Link>
                    </div>
                </div>

                <Link href="/pricing" class="rounded-lg px-3 py-2.5 font-medium text-ink hover:bg-brand-soft" @click="open = false">Pricing</Link>
                <Link href="/contact" class="rounded-lg px-3 py-2.5 font-medium text-ink hover:bg-brand-soft" @click="open = false">Contact</Link>

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
