<script setup>
import Seo from '@/Components/Seo.vue';

defineProps({ title: String, subtitle: String });
</script>

<template>
    <Seo :title="title" noindex />
    <section class="flex min-h-[70vh] items-center bg-brand-light py-12">
        <div class="container-x">
            <div class="mx-auto w-full max-w-md">
                <div class="card card-pad sm:p-8">
                    <h1 class="font-display text-2xl font-semibold text-ink">{{ title }}</h1>
                    <p v-if="subtitle" class="mt-1.5 text-sm text-gray-500">{{ subtitle }}</p>
                    <div class="mt-6"><slot /></div>
                </div>
                <div class="mt-5 text-center text-sm text-gray-600"><slot name="footer" /></div>
            </div>
        </div>
    </section>
</template>
