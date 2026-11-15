<script setup>
import Btn from '@/Components/Btn.vue';
import Icon from '@/Components/Icon.vue';
import { duration, money } from '@/Composables/useFormat';

// pkg: { slug, name, short_description, base_price, pricing_method, duration_minutes, included? }
defineProps({ pkg: { type: Object, required: true }, highlight: Boolean });
</script>

<template>
    <div :class="['card flex h-full flex-col p-6', highlight && 'ring-2 ring-brand']">
        <p v-if="highlight" class="mb-3 inline-flex self-start rounded-full bg-brand px-3 py-1 text-xs font-semibold text-white">Most popular</p>
        <h3 class="font-display text-xl font-semibold text-ink">{{ pkg.name }}</h3>
        <p class="mt-1 text-sm text-body">{{ pkg.short_description }}</p>
        <p class="mt-5">
            <template v-if="pkg.pricing_method === 'quote'"><span class="font-display text-3xl font-semibold text-ink">Custom quote</span></template>
            <template v-else>
                <span class="text-sm text-gray-500">from </span><span class="font-display text-4xl font-semibold text-ink">{{ money(pkg.base_price) }}</span>
            </template>
        </p>
        <p class="mt-1 flex items-center gap-1 text-sm text-gray-500"><Icon name="clock" class="h-4 w-4" />About {{ duration(pkg.duration_minutes) }}</p>
        <ul v-if="pkg.included?.length" class="mt-5 flex-1 space-y-2 text-sm">
            <li v-for="item in pkg.included" :key="item" class="flex gap-2"><Icon name="check" class="mt-0.5 h-4 w-4 text-brand" /><span>{{ item }}</span></li>
        </ul>
        <div v-else class="flex-1" />
        <Btn :href="`/book?service=${pkg.slug}`" :variant="highlight ? 'primary' : 'outline'" block class="mt-6">Book this clean</Btn>
    </div>
</template>
