<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Icon from '@/Components/Icon.vue';

/**
 * The site logo: the image uploaded in Admin → Branding, or the default mark + business name.
 * dark = shown on a dark background (footer, admin sidebar). short = first word of the name only.
 */
const props = defineProps({ dark: Boolean, short: Boolean, height: { type: String, default: 'h-9' } });

const page = usePage();
const logo = computed(() => page.props.brand?.logo || null);
const name = computed(() => page.props.site?.name || 'FreshNest Cleaning');
const parts = computed(() => name.value.split(' '));
const first = computed(() => parts.value[0]);
const rest = computed(() => (props.short ? '' : parts.value.slice(1, 2).join(' ')));

const failed = ref(false);
watch(logo, () => { failed.value = false; });
</script>

<template>
    <span class="inline-flex items-center gap-2">
        <span v-if="logo && !failed" :class="['inline-flex items-center', dark && 'rounded-lg bg-white px-2 py-1']">
            <img :src="logo" :alt="name" :class="[height, 'w-auto max-w-[11rem] object-contain']" @error="failed = true">
        </span>
        <template v-else>
            <span :class="[height, 'flex aspect-square items-center justify-center rounded-xl bg-brand text-white']"><Icon name="sparkles" /></span>
            <span :class="['font-display text-lg font-semibold', dark ? 'text-white' : 'text-ink']">{{ first }}<span v-if="rest" :class="dark ? 'text-green-300' : 'text-brand'"> {{ rest }}</span></span>
        </template>
    </span>
</template>
