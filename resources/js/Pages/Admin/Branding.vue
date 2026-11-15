<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Btn from '@/Components/Btn.vue';
import BrandingSlot from '@/Components/BrandingSlot.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Seo from '@/Components/Seo.vue';

const props = defineProps({ items: Array, sizes: Object, limits: Object });
const form = useForm({ header: props.sizes.header, footer: props.sizes.footer });
const logo = computed(() => usePage().props.brand?.logo);
const save = () => form.post('/admin/branding-size', { preserveScroll: true });
</script>

<template>
    <Seo title="Branding & banners" noindex />
    <PageHeader title="Branding & banners" subtitle="Change your logo and the banner image of each public page at any time. Changes appear on the website immediately." />
    <form class="card card-pad mb-6 space-y-5" @submit.prevent="save">
        <div><h2 class="font-display text-lg font-semibold text-ink">Logo size</h2><p class="text-sm text-gray-500">Choose how big the logo appears in the website header and footer (height in pixels).</p></div>
        <div class="grid gap-6 md:grid-cols-2">
            <div v-for="place in ['header', 'footer']" :key="place" class="space-y-2">
                <div class="flex items-center justify-between"><label :for="`size-${place}`" class="label !mb-0 capitalize">{{ place }} logo</label><span class="text-sm font-semibold text-ink">{{ form[place] }} px</span></div>
                <input :id="`size-${place}`" v-model.number="form[place]" type="range" :min="limits[place].min" :max="limits[place].max" step="1" class="w-full accent-brand">
                <div :class="['flex h-20 items-center rounded-xl px-4', place === 'footer' ? 'bg-ink' : 'border border-gray-200 bg-white']">
                    <span v-if="logo" :class="['inline-flex items-center', place === 'footer' && 'rounded-lg bg-white px-2 py-1']"><img :src="logo" alt="Logo preview" :style="{ height: `${Math.min(form[place], 72)}px` }" class="w-auto max-w-full object-contain"></span>
                    <span v-else class="text-sm text-gray-400">Upload a logo below to preview its size.</span>
                </div>
                <p v-if="form.errors[place]" class="field-error">{{ form.errors[place] }}</p>
            </div>
        </div>
        <p class="text-xs text-gray-500">The header bar is 64 px tall, so the header logo is limited to {{ limits.header.max }} px. The preview is capped at 72 px.</p>
        <Btn type="submit" :loading="form.processing">Save logo size</Btn>
    </form>

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        <BrandingSlot v-for="i in items" :key="i.key" :item="i" />
    </div>
</template>
