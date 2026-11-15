<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Btn from '@/Components/Btn.vue';
import Icon from '@/Components/Icon.vue';

defineProps({
    title: { type: String, default: 'A Cleaner Home. A Happier Life.' },
    description: { type: String, default: 'Trusted, background-checked cleaners for homes and offices. See your exact price online, pick a time that suits you and relax while we take care of the rest.' },
    rating: { type: Number, default: 0 },
    reviewCount: { type: Number, default: 0 },
});

// Banner image is editable in Admin → Branding ("Home page hero").
const configured = computed(() => usePage().props.brand?.banners?.home || '/images/banners/home.svg');
const state = ref(0);
watch(configured, () => { state.value = 0; });
const src = computed(() => (state.value === 0 ? configured.value : state.value === 1 ? '/images/banners/home.svg' : null));
const onError = () => { state.value = state.value === 0 && configured.value !== '/images/banners/home.svg' ? 1 : 2; };
</script>

<template>
    <section class="relative isolate overflow-hidden bg-gradient-to-br from-brand-dark to-brand">
        <img v-if="src" :src="src" alt="" class="absolute inset-0 -z-20 h-full w-full object-cover" fetchpriority="high" @error="onError">
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-black/70 via-black/50 to-black/15" aria-hidden="true" />
        <div class="container-x py-20 sm:py-28 lg:py-36">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-wider text-green-200">Residential &amp; commercial cleaning</p>
                <h1 class="mt-3 font-display text-4xl font-semibold leading-tight tracking-tight text-white drop-shadow-sm sm:text-5xl lg:text-6xl">{{ title }}</h1>
                <p class="mt-5 max-w-xl text-lg leading-relaxed text-white/90">{{ description }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <Btn href="/book" size="lg">Book a Cleaning<Icon name="arrow-right" class="h-5 w-5" /></Btn>
                    <Btn href="/pricing" variant="outline" size="lg" class="!border-white/80 !bg-white/10 !text-white hover:!bg-white/20">See pricing</Btn>
                </div>
                <p v-if="reviewCount" class="mt-6 flex items-center gap-2 text-sm text-white/90">
                    <Icon name="star" filled class="h-5 w-5 text-amber-300" />
                    <span><strong class="text-white">{{ rating }}</strong> average from {{ reviewCount }} customer reviews</span>
                </p>
            </div>
        </div>
    </section>
</template>
