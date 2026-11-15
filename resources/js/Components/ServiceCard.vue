<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';
import SmartImage from '@/Components/SmartImage.vue';
import { duration, money } from '@/Composables/useFormat';

// service: { slug, name, short_description, image_url, base_price, pricing_method, duration_minutes, category }
defineProps({ service: { type: Object, required: true } });
</script>

<template>
    <Link :href="`/services/${service.slug}`" class="card group flex h-full flex-col overflow-hidden transition duration-200 hover:-translate-y-0.5 hover:shadow-lg">
        <SmartImage :src="service.image_url" :alt="service.name" class="aspect-[16/10] w-full" />
        <div class="flex flex-1 flex-col p-5">
            <p v-if="service.category" class="text-xs font-semibold uppercase tracking-wide text-brand">{{ service.category }}</p>
            <h3 class="mt-1 font-display text-lg font-semibold text-ink group-hover:text-brand">{{ service.name }}</h3>
            <p class="mt-2 flex-1 text-sm leading-relaxed text-body">{{ service.short_description }}</p>
            <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4 text-sm">
                <span class="font-semibold text-ink">
                    <template v-if="service.pricing_method === 'quote'">Custom quote</template>
                    <template v-else>From {{ money(service.base_price) }}</template>
                </span>
                <span class="flex items-center gap-1 text-gray-500"><Icon name="clock" class="h-4 w-4" />{{ duration(service.duration_minutes) }}</span>
            </div>
        </div>
    </Link>
</template>
