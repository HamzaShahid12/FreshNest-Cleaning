<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

/**
 * Page header with a banner image that the admin can change in Admin → Branding.
 * page = banner key (home, about, services, pricing, …). Falls back to the shipped default image, then to a plain gradient.
 */
const props = defineProps({
    page: { type: String, required: true },
    eyebrow: String,
    title: { type: String, required: true },
    subtitle: String,
    center: { type: Boolean, default: true },
});

const defaultSrc = computed(() => `/images/banners/${props.page}.svg`);
const configured = computed(() => usePage().props.brand?.banners?.[props.page] || defaultSrc.value);
const state = ref(0); // 0 = configured, 1 = default, 2 = nothing (gradient only)
watch(configured, () => { state.value = 0; });
const src = computed(() => (state.value === 0 ? configured.value : state.value === 1 ? defaultSrc.value : null));
const onError = () => { state.value = state.value === 0 && configured.value !== defaultSrc.value ? 1 : 2; };
</script>

<template>
    <section class="relative isolate overflow-hidden bg-gradient-to-br from-brand-dark to-brand">
        <img v-if="src" :src="src" alt="" class="absolute inset-0 -z-20 h-full w-full object-cover" decoding="async" @error="onError">
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-black/65 via-black/45 to-black/20" aria-hidden="true" />
        <div :class="['container-x py-16 sm:py-24', center && 'text-center']">
            <p v-if="eyebrow" class="text-sm font-semibold uppercase tracking-wider text-green-200">{{ eyebrow }}</p>
            <h1 :class="['mt-2 font-display text-3xl font-semibold tracking-tight text-white drop-shadow-sm sm:text-4xl lg:text-5xl', center && 'mx-auto max-w-3xl']">{{ title }}</h1>
            <p v-if="subtitle" :class="['mt-4 max-w-2xl text-lg leading-relaxed text-white/90', center && 'mx-auto']">{{ subtitle }}</p>
            <div v-if="$slots.default" class="mt-6"><slot /></div>
        </div>
    </section>
</template>
