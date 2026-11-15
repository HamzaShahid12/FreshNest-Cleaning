<script setup>
import { Link } from '@inertiajs/vue3';
import Btn from '@/Components/Btn.vue';
import Seo from '@/Components/Seo.vue';
import ServiceCard from '@/Components/ServiceCard.vue';
import ZipChecker from '@/Components/ZipChecker.vue';

const props = defineProps({ area: Object, services: Array });
const place = [props.area.name, props.area.state].filter(Boolean).join(', ');
const title = `House & Office Cleaning in ${place}`;
</script>

<template>
    <Seo :title="title" :description="area.description || `Book trusted home and office cleaning in ${place}.`" />

    <section class="bg-brand-light py-14 sm:py-16">
        <div class="container-x">
            <Link href="/areas" class="text-sm font-medium text-brand hover:underline">&larr; All service areas</Link>
            <h1 class="h-section mt-3">{{ title }}</h1>
            <p v-if="area.description" class="lead mt-3 max-w-3xl">{{ area.description }}</p>
            <div class="mt-6 flex flex-wrap items-end gap-6"><ZipChecker /><Btn href="/book" size="lg">Book in {{ area.name }}</Btn></div>
        </div>
    </section>

    <section v-if="area.zips.length" class="section pb-0">
        <div class="container-x">
            <h2 class="font-display text-xl font-semibold text-ink">ZIP codes we serve in {{ area.name }}</h2>
            <ul class="mt-4 flex flex-wrap gap-2">
                <li v-for="z in area.zips" :key="z" class="rounded-full bg-brand-soft px-3 py-1 text-sm font-medium text-brand-dark">{{ z }}</li>
            </ul>
        </div>
    </section>

    <section class="section">
        <div class="container-x">
            <h2 class="h-section">Popular services in {{ area.name }}</h2>
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3"><ServiceCard v-for="s in services" :key="s.slug" :service="s" /></div>
        </div>
    </section>
</template>
