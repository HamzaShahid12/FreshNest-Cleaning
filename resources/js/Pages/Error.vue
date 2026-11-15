<script setup>
import { computed } from 'vue';
import Btn from '@/Components/Btn.vue';
import Seo from '@/Components/Seo.vue';

const props = defineProps({ status: { type: Number, default: 404 } });

const copy = computed(() => ({
    403: ['Access denied', 'You do not have permission to open this page.'],
    404: ['Page not found', 'The page you are looking for does not exist or has moved.'],
    500: ['Something went wrong', 'We hit an unexpected problem. Please try again in a moment.'],
    503: ['Back soon', 'We are doing a little maintenance. Please check back shortly.'],
}[props.status] || ['Something went wrong', 'Please try again.']));
</script>

<template>
    <Seo :title="copy[0]" noindex />
    <section class="container-x flex min-h-[60vh] flex-col items-center justify-center py-20 text-center">
        <p class="eyebrow">Error {{ status }}</p>
        <h1 class="h-section mt-2">{{ copy[0] }}</h1>
        <p class="lead mt-3 max-w-md">{{ copy[1] }}</p>
        <div class="mt-8 flex gap-3"><Btn href="/">Back to home</Btn><Btn href="/contact" variant="outline">Contact us</Btn></div>
    </section>
</template>
