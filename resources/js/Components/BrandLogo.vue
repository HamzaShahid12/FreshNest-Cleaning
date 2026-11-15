<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Icon from '@/Components/Icon.vue';

/**
 * The site logo: the image uploaded in Admin → Branding, or the default mark + business name.
 * dark = shown on a dark background (footer, admin sidebar). short = first word of the name only.
 */
const props = defineProps({ dark: Boolean, short: Boolean, height: { type: String, default: 'h-9' }, place: { type: String, default: '' } });

const page = usePage();
const logo = computed(() => page.props.brand?.logo || null);
const name = computed(() => page.props.site?.name || 'FreshNest Cleaning');
const parts = computed(() => name.value.split(' '));
const first = computed(() => parts.value[0]);
const rest = computed(() => (props.short ? '' : parts.value.slice(1, 2).join(' ')));

// Header and footer logo heights are set by the admin (Branding → Logo size). Other places keep the default.
const px = computed(() => (props.place ? Number(page.props.brand?.logo_height?.[props.place]) || 36 : null));
const imgStyle = computed(() => (px.value ? { height: `${px.value}px` } : {}));

const failed = ref(false);
watch(logo, () => { failed.value = false; });
</script>

<template>
    <span class="inline-flex items-center gap-2">
        <span v-if="logo && !failed" :class="['inline-flex items-center', dark && 'rounded-lg bg-white px-2 py-1']">
            <img :src="logo" :alt="name" :style="imgStyle" :class="[px ? '' : height, 'w-auto max-w-[min(20rem,70vw)] object-contain']" @error="failed = true">
        </span>
        <template v-else>
            <span :style="px ? { height: `${px}px`, width: `${px}px` } : {}" :class="[px ? '' : height, 'flex aspect-square items-center justify-center rounded-xl bg-brand text-white']"><Icon name="sparkles" /></span>
            <span :class="['font-display text-lg font-semibold', dark ? 'text-white' : 'text-ink']">{{ first }}<span v-if="rest" :class="dark ? 'text-green-300' : 'text-brand'"> {{ rest }}</span></span>
        </template>
    </span>
</template>
