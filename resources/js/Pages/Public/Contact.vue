<script setup>
import LocationMap from '@/Components/LocationMap.vue';
import PageBanner from '@/Components/PageBanner.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ContactForm from '@/Components/ContactForm.vue';
import Icon from '@/Components/Icon.vue';
import Seo from '@/Components/Seo.vue';

const site = computed(() => usePage().props.site || {});
const social = computed(() => Object.entries(site.value.social || {}).filter(([, u]) => u));
// Only allow http(s) map embeds configured by an admin.
const hasPin = computed(() => typeof site.value.lat === 'number' && typeof site.value.lng === 'number');
const mapUrl = computed(() => (/^https:\/\//i.test(site.value.maps_embed_url || '') ? site.value.maps_embed_url : null));
</script>

<template>
    <Seo title="Contact Us" description="Questions or a custom quote? Get in touch with the FreshNest team." />

    <PageBanner page="contact" eyebrow="Contact" title="We would love to hear from you" subtitle="Ask a question, request an office cleaning quote, or tell us about a special project." />

    <section class="section">
        <div class="container-x grid gap-10 lg:grid-cols-5">
            <div class="card card-pad lg:col-span-3"><h2 class="font-display text-xl font-semibold text-ink">Send us a message</h2><div class="mt-5"><ContactForm /></div></div>

            <aside class="space-y-5 lg:col-span-2">
                <div class="card card-pad space-y-4 text-sm">
                    <h2 class="font-display text-xl font-semibold text-ink">Contact details</h2>
                    <p v-if="site.phone" class="flex gap-3"><Icon name="phone" class="text-brand" /><a :href="`tel:${site.phone}`" class="hover:text-brand">{{ site.phone }}</a></p>
                    <p v-if="site.email" class="flex gap-3"><Icon name="mail" class="text-brand" /><a :href="`mailto:${site.email}`" class="hover:text-brand">{{ site.email }}</a></p>
                    <p v-if="site.address" class="flex gap-3"><Icon name="map-pin" class="text-brand" />{{ site.address }}</p>
                    <p v-if="site.hours" class="flex gap-3"><Icon name="clock" class="text-brand" />{{ site.hours }}</p>
                    <div v-if="social.length" class="flex flex-wrap gap-2 pt-2">
                        <a v-for="[n, u] in social" :key="n" :href="u" target="_blank" rel="noopener noreferrer" class="rounded-lg bg-brand-soft px-3 py-1.5 font-medium capitalize text-brand-dark hover:bg-green-100">{{ n === 'x' ? 'X' : n }}</a>
                    </div>
                </div>
                <div v-if="hasPin" class="overflow-hidden rounded-2xl border border-gray-100 shadow-card">
                    <LocationMap :latitude="site.lat" :longitude="site.lng" :label="site.name" />
                    <a :href="`https://www.google.com/maps/dir/?api=1&destination=${site.lat},${site.lng}`" target="_blank" rel="noopener noreferrer" class="block bg-white px-4 py-2.5 text-center text-sm font-semibold text-brand hover:bg-brand-soft">Get directions</a>
                </div>
                <div v-else-if="mapUrl" class="overflow-hidden rounded-2xl border border-gray-100 shadow-card">
                    <iframe :src="mapUrl" title="Map showing our location" class="h-64 w-full border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" />
                </div>
            </aside>
        </div>
    </section>
</template>
