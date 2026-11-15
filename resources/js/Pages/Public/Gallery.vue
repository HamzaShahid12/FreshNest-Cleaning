<script setup>
import PageBanner from '@/Components/PageBanner.vue';
import { computed, ref } from 'vue';
import Btn from '@/Components/Btn.vue';
import GalleryGrid from '@/Components/GalleryGrid.vue';
import Seo from '@/Components/Seo.vue';

const props = defineProps({ items: Array });
const tabs = [{ key: 'all', label: 'All projects' }, { key: 'residential', label: 'Residential' }, { key: 'commercial', label: 'Commercial' }];
const active = ref('all');
const shown = computed(() => (active.value === 'all' ? props.items : props.items.filter((i) => i.category === active.value)));
</script>

<template>
    <Seo title="Before & After Gallery" description="See the difference a FreshNest clean makes. Before-and-after photos from residential and commercial projects." />

    <PageBanner page="gallery" eyebrow="Our work" title="Before &amp; after gallery" subtitle="Real results from homes and workplaces we have cleaned. Tap or hover a photo to see the before." />

    <section class="section">
        <div class="container-x">
            <div class="mb-8 flex flex-wrap justify-center gap-2" role="tablist" aria-label="Gallery category">
                <button
                    v-for="t in tabs" :key="t.key" type="button" role="tab" :aria-selected="active === t.key"
                    :class="['rounded-full px-4 py-2 text-sm font-medium transition', active === t.key ? 'bg-brand text-white' : 'bg-gray-100 text-body hover:bg-brand-soft']"
                    @click="active = t.key"
                >{{ t.label }}</button>
            </div>
            <GalleryGrid :items="shown" />
            <div class="mt-12 text-center"><Btn href="/book" size="lg">Book your clean</Btn></div>
        </div>
    </section>
</template>
