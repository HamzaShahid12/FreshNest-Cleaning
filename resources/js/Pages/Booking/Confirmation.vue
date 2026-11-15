<script setup>
import { computed } from 'vue';
import BookingDetails from '@/Components/Booking/BookingDetails.vue';
import Btn from '@/Components/Btn.vue';
import Icon from '@/Components/Icon.vue';
import Seo from '@/Components/Seo.vue';

const props = defineProps({ booking: Object });
const params = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : new URLSearchParams();
const paid = params.get('paid') === '1';
const cancelled = params.get('cancelled') === '1';
const awaitingPayment = computed(() => props.booking.payment_method === 'stripe' && props.booking.payment_status !== 'paid');
</script>

<template>
    <Seo title="Booking received" noindex />

    <section class="bg-brand-light py-12">
        <div class="container-x max-w-5xl">
            <div class="text-center">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-brand text-white"><Icon name="check" class="h-7 w-7" /></span>
                <h1 class="h-section mt-4">Thank you, your booking request is in!</h1>
                <p class="lead mt-2">Reference <strong class="text-ink">{{ booking.reference }}</strong>. We have emailed you the details.</p>
            </div>

            <div v-if="paid && booking.payment_status !== 'paid'" class="mt-6 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">Thanks! Your payment is being confirmed by Stripe. This page will show “Paid” shortly; refresh in a moment.</div>
            <div v-else-if="cancelled" class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">Payment was not completed. Your booking is saved; contact us to arrange payment.</div>
            <div v-else-if="awaitingPayment" class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">Online payment has not been received yet.</div>

            <div class="mt-8"><BookingDetails :booking="booking" :history="false" /></div>

            <div class="card card-pad mt-5">
                <h2 class="font-display text-lg font-semibold text-ink">What happens next</h2>
                <ol class="mt-3 list-decimal space-y-1.5 pl-5 text-sm text-body">
                    <li>Our team reviews your request and confirms your appointment by email.</li>
                    <li>A cleaner is assigned and you receive their name.</li>
                    <li>We arrive at your scheduled time and clean to our checklist.</li>
                </ol>
            </div>

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <Btn :href="`/account/bookings/${booking.reference}`">View in my account</Btn>
                <Btn href="/" variant="outline">Back to home</Btn>
            </div>
        </div>
    </section>
</template>
