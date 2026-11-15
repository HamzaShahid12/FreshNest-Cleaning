<script setup>
import PageBanner from '@/Components/PageBanner.vue';
import { computed } from 'vue';
import Btn from '@/Components/Btn.vue';
import Seo from '@/Components/Seo.vue';
import ServiceCard from '@/Components/ServiceCard.vue';

const props = defineProps({ services: { type: Array, default: () => [] } });

// Group by category for easier browsing.
const groups = computed(() => {
    const map = new Map();
    props.services.forEach((s) => {
        const key = s.category || 'Services';
        if (!map.has(key)) map.set(key, []);
        map.get(key).push(s);
    });

    return [...map].map(([name, items]) => ({ name, items }));
});
</script>

<template>
    <Seo title="Cleaning Services" description="Standard, deep, move-in, move-out, apartment, office, kitchen, bathroom, carpet and window cleaning." />
    <PageBanner page="services" eyebrow="Our services" title="Cleaning services for every need" subtitle="Choose a service to see what is included, how long it takes and what it costs." :center="false" />

    <section class="section"><div class="container-x space-y-14">
        <div v-for="g in groups" :key="g.name">
            <h2 class="font-display text-2xl font-semibold text-ink">{{ g.name }}</h2>
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3"><ServiceCard v-for="s in g.items" :key="s.id" :service="s" /></div>
        </div>
        <p v-if="!services.length" class="text-center text-gray-500">Services are coming soon.</p>
        <div class="rounded-3xl bg-brand-soft p-8 text-center sm:p-12"><h2 class="h-section">Not sure which clean you need?</h2><p class="lead mx-auto mt-3 max-w-xl">Our booking tool shows an exact price as you go, or get in touch and we will help.</p><div class="mt-6 flex justify-center gap-3"><Btn href="/book">Book a Cleaning</Btn><Btn href="/contact" variant="outline">Ask a question</Btn></div></div>
    </div></section>
</template>
