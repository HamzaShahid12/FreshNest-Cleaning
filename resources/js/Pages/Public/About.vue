<script setup>
import PageBanner from '@/Components/PageBanner.vue';
import Btn from '@/Components/Btn.vue';
import Icon from '@/Components/Icon.vue';
import Seo from '@/Components/Seo.vue';
import SmartImage from '@/Components/SmartImage.vue';

const values = [
    { icon: 'heart', title: 'Care', text: 'We treat every home and workplace as if it were our own.' },
    { icon: 'shield', title: 'Trust', text: 'Background-checked, trained and accountable team members.' },
    { icon: 'sparkles', title: 'Quality', text: 'Checklists and follow-ups keep results consistent visit after visit.' },
    { icon: 'leaf', title: 'Responsibility', text: 'Safer products and less waste wherever we can.' },
];
const standards = ['Room-by-room cleaning checklist on every visit', 'Colour-coded cloths to avoid cross-contamination', 'HEPA-filter vacuums for carpets and upholstery', 'Final walkthrough before we leave', 'Quality follow-up within 24 hours'];
const team = [
    { name: 'Operations team', role: 'Scheduling & customer care' },
    { name: 'Cleaning specialists', role: 'Trained, vetted and insured' },
    { name: 'Quality leads', role: 'Spot checks and training' },
];
</script>

<template>
    <Seo title="About Us" description="Learn about FreshNest Cleaning Services: our mission, values and cleaning standards." />

    <PageBanner page="about" eyebrow="About FreshNest" title="Cleaning that gives you your time back" subtitle="FreshNest Cleaning Services helps homeowners, renters, landlords and offices enjoy a spotless space without the hassle, with clear pricing, easy online booking and a team you can trust." :center="false" />

    <section class="section"><div class="container-x grid gap-8 md:grid-cols-2">
        <div class="card card-pad"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-soft text-brand"><Icon name="heart" /></span><h2 class="mt-4 font-display text-2xl font-semibold text-ink">Our mission</h2><p class="mt-2 text-body">To make professional cleaning simple, transparent and dependable, so every customer comes home to a space that feels fresh and cared for.</p></div>
        <div class="card card-pad"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-soft text-brand"><Icon name="sparkles" /></span><h2 class="mt-4 font-display text-2xl font-semibold text-ink">Our vision</h2><p class="mt-2 text-body">To be the most trusted home and commercial cleaning company in every community we serve, known for great people and consistent quality.</p></div>
    </div></section>

    <section class="section bg-brand-light"><div class="container-x">
        <div class="text-center"><p class="eyebrow">Our values</p><h2 class="h-section mt-2">What guides us</h2></div>
        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="v in values" :key="v.title" class="card p-6 text-center"><span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-soft text-brand"><Icon :name="v.icon === 'leaf' ? 'heart' : v.icon" /></span><h3 class="mt-4 font-semibold text-ink">{{ v.title }}</h3><p class="mt-1.5 text-sm text-body">{{ v.text }}</p></div>
        </div>
    </div></section>

    <section class="section"><div class="container-x grid items-center gap-10 lg:grid-cols-2">
        <div><p class="eyebrow">Our cleaning standards</p><h2 class="h-section mt-2">The same high bar, every visit</h2>
            <ul class="mt-6 space-y-3"><li v-for="s in standards" :key="s" class="flex gap-3"><span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand text-white"><Icon name="check" class="h-4 w-4" /></span><span>{{ s }}</span></li></ul></div>
        <SmartImage alt="The FreshNest team at work" class="aspect-[4/3] w-full rounded-2xl" />
    </div></section>

    <section class="section bg-brand-light"><div class="container-x">
        <div class="text-center"><p class="eyebrow">Meet the team</p><h2 class="h-section mt-2">The people behind your clean</h2></div>
        <div class="mt-10 grid gap-6 md:grid-cols-3">
            <div v-for="t in team" :key="t.name" class="card p-6 text-center"><SmartImage :alt="t.name" icon="users" class="mx-auto h-24 w-24 rounded-full" /><h3 class="mt-4 font-semibold text-ink">{{ t.name }}</h3><p class="text-sm text-body">{{ t.role }}</p></div>
        </div>
    </div></section>

    <section class="section"><div class="container-x text-center"><h2 class="h-section">Let&rsquo;s make your space shine</h2><p class="lead mx-auto mt-3 max-w-xl">Get an instant price and book online today.</p><div class="mt-6 flex justify-center gap-3"><Btn href="/book" size="lg">Book a Cleaning</Btn><Btn href="/contact" variant="outline" size="lg">Contact us</Btn></div></div></section>
</template>
