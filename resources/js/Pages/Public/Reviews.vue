<script setup>
import PageBanner from '@/Components/PageBanner.vue';
import Btn from '@/Components/Btn.vue';
import ReviewStars from '@/Components/ReviewStars.vue';
import Seo from '@/Components/Seo.vue';
import TestimonialCard from '@/Components/TestimonialCard.vue';

defineProps({ reviews: Array, stats: Object });
</script>

<template>
    <Seo title="Customer Reviews" description="Read what FreshNest customers say about our cleaning services." />

    <PageBanner page="reviews" eyebrow="Reviews" title="Loved by our customers">
        <div v-if="stats.count" class="inline-flex items-center gap-3 rounded-2xl bg-white px-6 py-4 shadow-card">
            <span class="font-display text-4xl font-semibold text-ink">{{ stats.average.toFixed(1) }}</span>
            <div class="text-left"><ReviewStars :rating="stats.average" /><p class="text-xs text-gray-500">{{ stats.count }} verified review{{ stats.count === 1 ? '' : 's' }}</p></div>
        </div>
    </PageBanner>

    <section class="section">
        <div class="container-x">
            <div v-if="reviews.length" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <TestimonialCard v-for="r in reviews" :key="r.id" :review="r" />
            </div>
            <p v-else class="text-center text-gray-500">No reviews have been published yet. Be the first after your cleaning!</p>

            <div class="mx-auto mt-14 max-w-2xl rounded-2xl bg-brand-soft p-6 text-center sm:p-8">
                <h2 class="font-display text-xl font-semibold text-ink">Had a cleaning with us?</h2>
                <p class="mt-2 text-sm text-body">Reviews can be left by customers with a completed booking. Sign in to your account, open the booking and share your feedback. Reviews appear after a quick moderation check.</p>
                <div class="mt-5 flex flex-wrap justify-center gap-3"><Btn href="/account/bookings">Go to my bookings</Btn><Btn href="/book" variant="outline">Book a cleaning</Btn></div>
            </div>
        </div>
    </section>
</template>
