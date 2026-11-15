<script setup>
import BrandingSlot from '@/Components/BrandingSlot.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Seo from '@/Components/Seo.vue';

defineProps({ items: Array });
</script>

<template>
    <Seo title="Branding & banners" noindex />
    <PageHeader title="Branding & banners" subtitle="Change your logo and the banner image of each public page at any time. Changes appear on the website immediately." />
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        <BrandingSlot v-for="i in items" :key="i.key" :item="i" />
    </div>
</template>
