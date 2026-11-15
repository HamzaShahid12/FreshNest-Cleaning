<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Btn from '@/Components/Btn.vue';
import FAQAccordion from '@/Components/FAQAccordion.vue';
import Icon from '@/Components/Icon.vue';
import Seo from '@/Components/Seo.vue';
import ServiceCard from '@/Components/ServiceCard.vue';
import SmartImage from '@/Components/SmartImage.vue';
import { duration, money, paragraphs } from '@/Composables/useFormat';

const props = defineProps({
    service: { type: Object, required: true },
    related: { type: Array, default: () => [] },
});

const isQuote = computed(() => props.service.pricing_method === 'quote');
const faqItems = computed(() => (props.service.faqs || []).map((f) => ({ question: f.q, answer: f.a })));
const jsonLd = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'Service',
    name: props.service.name,
    description: props.service.seo_description || props.service.short_description,
    provider: { '@type': 'HouseCleaningService', name: 'FreshNest Cleaning Services' },
    areaServed: 'United States',
    ...(isQuote.value ? {} : { offers: { '@type': 'Offer', priceCurrency: 'USD', price: String(props.service.base_price) } }),
}));
</script>

<template>
    <Seo :title="service.seo_title || service.name" :description="service.seo_description || service.short_description" :image="service.image_url" :json-ld="jsonLd" />

    <section class="bg-brand-light"><div class="container-x py-10 sm:py-14">
        <nav class="text-sm text-gray-500" aria-label="Breadcrumb"><Link href="/services" class="hover:text-brand">Services</Link> <span aria-hidden="true">/</span> <span class="text-ink">{{ service.name }}</span></nav>
        <div class="mt-6 grid items-center gap-10 lg:grid-cols-2">
            <div>
                <p v-if="service.category" class="eyebrow">{{ service.category }}</p>
                <h1 class="h-display mt-2 !text-4xl sm:!text-5xl">{{ service.name }}</h1>
                <p class="lead mt-4">{{ service.short_description }}</p>
                <dl class="mt-6 flex flex-wrap gap-6 text-sm">
                    <div><dt class="text-gray-500">Estimated duration</dt><dd class="mt-0.5 flex items-center gap-1.5 font-semibold text-ink"><Icon name="clock" class="h-4 w-4 text-brand" />{{ duration(service.duration_minutes) }}</dd></div>
                    <div><dt class="text-gray-500">Pricing</dt><dd class="mt-0.5 font-semibold text-ink">{{ isQuote ? 'Custom quotation' : `From ${money(service.base_price)}` }}</dd></div>
                </dl>
                <div class="mt-8 flex flex-wrap gap-3"><Btn :href="`/book?service=${service.slug}`" size="lg">{{ isQuote ? 'Request a quote' : 'Book this service' }}</Btn><Btn href="/pricing" variant="outline" size="lg">See pricing</Btn></div>
            </div>
            <SmartImage :src="service.image_url" :alt="service.name" class="aspect-[4/3] w-full rounded-2xl shadow-card" />
        </div>
    </div></section>

    <section class="section"><div class="container-x grid gap-12 lg:grid-cols-3">
        <div class="space-y-10 lg:col-span-2">
            <div v-if="service.description"><h2 class="h-section !text-2xl">About this service</h2><p v-for="(p, i) in paragraphs(service.description)" :key="i" class="mt-3 leading-relaxed">{{ p }}</p></div>
            <div v-if="service.included.length"><h2 class="h-section !text-2xl">What&rsquo;s included</h2>
                <ul class="mt-4 grid gap-3 sm:grid-cols-2"><li v-for="item in service.included" :key="item" class="flex gap-3 text-sm"><span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand text-white"><Icon name="check" class="h-3.5 w-3.5" /></span>{{ item }}</li></ul></div>
            <div v-if="service.benefits.length"><h2 class="h-section !text-2xl">Benefits</h2>
                <ul class="mt-4 grid gap-4 sm:grid-cols-2"><li v-for="b in service.benefits" :key="b" class="card flex items-start gap-3 p-4 text-sm"><Icon name="sparkles" class="mt-0.5 h-5 w-5 text-brand" />{{ b }}</li></ul></div>
            <div v-if="faqItems.length"><h2 class="h-section !text-2xl">Frequently asked questions</h2><div class="mt-4"><FAQAccordion :items="faqItems" /></div></div>
        </div>

        <aside class="lg:col-span-1"><div class="card card-pad sticky top-24">
            <h2 class="font-display text-xl font-semibold text-ink">{{ isQuote ? 'Get a custom quote' : 'Price your clean' }}</h2>
            <p class="mt-2 text-sm text-body">{{ isQuote ? 'Tell us about your workspace and we will send a tailored quotation.' : 'Your exact price depends on home size, extras and how often you clean, shown instantly while you book.' }}</p>
            <p v-if="!isQuote" class="mt-4 font-display text-3xl font-semibold text-ink"><span class="text-sm font-normal text-gray-500">from </span>{{ money(service.base_price) }}</p>
            <Btn :href="`/book?service=${service.slug}`" block class="mt-5">{{ isQuote ? 'Request a quote' : 'Book now' }}</Btn>
            <p class="mt-3 text-center text-xs text-gray-500">Free rescheduling per our cancellation policy.</p>
        </div></aside>
    </div></section>

    <section v-if="related.length" class="section bg-brand-light"><div class="container-x">
        <h2 class="h-section">Related services</h2>
        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3"><ServiceCard v-for="s in related" :key="s.id" :service="s" /></div>
    </div></section>
</template>
