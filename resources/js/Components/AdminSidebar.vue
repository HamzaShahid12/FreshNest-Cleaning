<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import BrandLogo from '@/Components/BrandLogo.vue';
import Icon from '@/Components/Icon.vue';

defineEmits(['navigate']);

const page = usePage();
const role = computed(() => page.props.auth?.user?.role);

// owners = Administrator + Super Admin only. Server-side checks are the real protection; this only hides links.
const groups = [
    { title: 'Operations', items: [
        { label: 'Dashboard', href: '/admin', icon: 'grid', exact: true },
        { label: 'Bookings', href: '/admin/bookings', icon: 'clipboard' },
        { label: 'Calendar', href: '/admin/calendar', icon: 'calendar' },
        { label: 'Customers', href: '/admin/customers', icon: 'users' },
        { label: 'Employees', href: '/admin/employees', icon: 'briefcase' },
    ] },
    { title: 'Catalogue', owners: true, items: [
        { label: 'Services', href: '/admin/m/services', icon: 'sparkles' },
        { label: 'Categories', href: '/admin/m/categories', icon: 'tag' },
        { label: 'Extra services', href: '/admin/m/extras', icon: 'plus' },
        { label: 'Coupons', href: '/admin/m/coupons', icon: 'tag' },
        { label: 'Service areas', href: '/admin/m/areas', icon: 'map-pin' },
    ] },
    { title: 'Money', items: [
        { label: 'Payments', href: '/admin/m/payments', icon: 'card' },
        { label: 'Invoices', href: '/admin/m/invoices', icon: 'document' },
        { label: 'Reports', href: '/admin/reports', icon: 'chart' },
    ] },
    { title: 'Website', items: [
        { label: 'Reviews', href: '/admin/m/reviews', icon: 'star' },
        { label: 'Enquiries', href: '/admin/m/enquiries', icon: 'mail' },
        { label: 'Blog posts', href: '/admin/m/blog', icon: 'document' },
        { label: 'Blog categories', href: '/admin/m/blog-categories', icon: 'tag' },
        { label: 'FAQs', href: '/admin/m/faqs', icon: 'chat' },
        { label: 'Gallery', href: '/admin/m/gallery', icon: 'photo' },
    ] },
    { title: 'System', owners: true, items: [
        { label: 'Business settings', href: '/admin/settings', icon: 'sliders' },
        { label: 'Branding & banners', href: '/admin/branding', icon: 'photo' },
        { label: 'Users & roles', href: '/admin/m/users', icon: 'shield', superOnly: true },
        { label: 'Audit logs', href: '/admin/audit-logs', icon: 'clipboard' },
    ] },
];

const visible = computed(() => groups
    .filter((g) => !g.owners || ['super_admin', 'admin'].includes(role.value))
    .map((g) => ({ ...g, items: g.items.filter((i) => !i.superOnly || role.value === 'super_admin') })));

const isActive = (i) => (i.exact ? page.url.split('?')[0] === i.href : page.url.startsWith(i.href));
</script>

<template>
    <nav class="flex h-full flex-col overflow-y-auto bg-ink px-3 py-5 text-gray-300" aria-label="Admin">
        <Link href="/admin" class="mb-6 flex items-center px-3" @click="$emit('navigate')"><BrandLogo dark short /></Link>
        <div v-for="g in visible" :key="g.title" class="mb-5">
            <p class="mb-1.5 px-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">{{ g.title }}</p>
            <Link
                v-for="i in g.items" :key="i.href" :href="i.href"
                :class="['flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition', isActive(i) ? 'bg-brand text-white' : 'hover:bg-white/10 hover:text-white']"
                @click="$emit('navigate')"
            ><Icon :name="i.icon" class="h-4 w-4" />{{ i.label }}</Link>
        </div>
    </nav>
</template>
