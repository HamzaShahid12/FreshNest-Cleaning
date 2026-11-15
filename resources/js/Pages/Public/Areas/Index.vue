<script setup>
import PageBanner from '@/Components/PageBanner.vue';
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';
import Seo from '@/Components/Seo.vue';
import ZipChecker from '@/Components/ZipChecker.vue';

defineProps({ areas: Array });
</script>

<template>
    <Seo title="Service Areas" description="Cities and neighbourhoods where FreshNest provides home and office cleaning. Check your ZIP code." />

    <PageBanner page="areas" eyebrow="Where we work" title="Our service areas" subtitle="Choose your city to see local details, or check your ZIP code below.">
        <div class="mx-auto inline-block rounded-2xl bg-white/95 p-3 shadow-card"><ZipChecker /></div>
    </PageBanner>

    <section class="section">
        <div class="container-x">
            <div v-if="areas.length" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <Link v-for="a in areas" :key="a.id" :href="`/areas/${a.slug}`" class="card card-pad group transition hover:-translate-y-0.5 hover:border-brand">
                    <div class="flex items-center gap-3"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-soft text-brand"><Icon name="map-pin" /></span><h2 class="font-display text-lg font-semibold text-ink group-hover:text-brand">{{ a.name }}<span v-if="a.state">, {{ a.state }}</span></h2></div>
                    <p v-if="a.description" class="mt-3 line-clamp-3 text-sm text-body">{{ a.description }}</p>
                </Link>
            </div>
            <p v-else class="text-center text-gray-500">Service areas will be announced soon. Please contact us.</p>
        </div>
    </section>
</template>
