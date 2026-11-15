<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

// <Seo title="..." description="..." /> sets title, description, canonical, Open Graph and optional JSON-LD.
const props = defineProps({
    title: String,
    description: String,
    image: String,
    type: { type: String, default: 'website' },
    noindex: Boolean,
    jsonLd: Object,
});

const page = usePage();
const site = computed(() => page.props.site || {});
const canonical = computed(() => `${String(page.props.appUrl || '').replace(/\/$/, '')}${String(page.url || '/').split('?')[0]}`);
const desc = computed(() => props.description || site.value.tagline || '');
const ld = computed(() => (props.jsonLd ? JSON.stringify(props.jsonLd).replace(/</g, '\\u003c') : null));
</script>

<template>
    <Head>
        <title>{{ title }}</title>
        <meta v-if="desc" head-key="description" name="description" :content="desc">
        <meta v-if="noindex" head-key="robots" name="robots" content="noindex,nofollow">
        <link head-key="canonical" rel="canonical" :href="canonical">
        <meta head-key="og:title" property="og:title" :content="title || site.name">
        <meta v-if="desc" head-key="og:description" property="og:description" :content="desc">
        <meta head-key="og:type" property="og:type" :content="type">
        <meta head-key="og:url" property="og:url" :content="canonical">
        <meta head-key="og:site_name" property="og:site_name" :content="site.name || 'FreshNest Cleaning'">
        <meta v-if="image" head-key="og:image" property="og:image" :content="image">
        <meta head-key="twitter:card" name="twitter:card" content="summary_large_image">
        <component :is="'script'" v-if="ld" head-key="jsonld" type="application/ld+json" v-html="ld" />
    </Head>
</template>
