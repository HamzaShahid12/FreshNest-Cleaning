<script setup>
import ReviewStars from '@/Components/ReviewStars.vue';

// review: { name, rating, comment, date }
defineProps({ review: { type: Object, required: true } });
</script>

<template>
    <figure class="card flex h-full flex-col p-6">
        <ReviewStars :rating="review.rating" size="h-5 w-5" />
        <blockquote class="mt-4 flex-1 text-sm leading-relaxed text-body">&ldquo;{{ review.comment || 'Wonderful service, highly recommended.' }}&rdquo;</blockquote>
        <figcaption class="mt-5 flex items-center gap-3 border-t border-gray-100 pt-4">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-soft text-sm font-semibold text-brand-dark">{{ review.name?.charAt(0) }}</span>
            <span class="text-sm"><span class="block font-semibold text-ink">{{ review.name }}</span><span class="text-xs text-gray-500">{{ review.date }}</span></span>
        </figcaption>
    </figure>
</template>
