<script setup>
import { computed } from 'vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import { duration, money } from '@/Composables/useFormat';

// Display-only: every figure comes from the server quote (POST /api/booking/quote).
const props = defineProps({
    quote: Object,
    loading: Boolean,
    error: String,
    serviceName: String,
    schedule: String,
    address: String,
    compact: Boolean, // no card chrome (used inside the mobile drawer)
});

const hasDiscount = computed(() => Number(props.quote?.discount_total) > 0);
const showTax = computed(() => Number(props.quote?.tax) > 0);
</script>

<template>
    <div :class="compact ? '' : 'card card-pad'" :aria-busy="loading">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-lg font-semibold text-ink">Your estimate</h2>
            <LoadingSpinner v-if="loading" size="h-4 w-4" label="Updating price" />
        </div>
        <p v-if="serviceName" class="mt-0.5 text-sm text-gray-500">{{ serviceName }}</p>

        <p v-if="error" class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700" role="alert">{{ error }}</p>

        <p v-if="!quote && !loading && !error" class="mt-4 text-sm text-gray-500">Choose a service to see your price.</p>

        <div v-else-if="quote?.requires_quote" class="mt-4 rounded-xl bg-brand-soft p-4 text-sm text-brand-dark">
            <p class="font-semibold">Custom quotation</p>
            <p class="mt-1">This service is priced individually. Send your request and we will confirm a quote before any payment.</p>
            <p v-if="quote.duration_minutes" class="mt-2 text-xs">Estimated duration: {{ duration(quote.duration_minutes) }}</p>
        </div>

        <dl v-else-if="quote" :class="['mt-4 space-y-2 text-sm transition-opacity', loading && 'opacity-60']">
            <div v-for="(l, i) in quote.lines" :key="i" class="flex justify-between gap-4">
                <dt class="text-body">{{ l.label }}</dt>
                <dd class="whitespace-nowrap font-medium text-ink">{{ money(l.amount) }}</dd>
            </div>
            <div v-if="hasDiscount || showTax" class="flex justify-between gap-4 border-t border-gray-100 pt-2">
                <dt class="text-body">Subtotal</dt>
                <dd class="font-medium text-ink">{{ money(quote.subtotal) }}</dd>
            </div>
            <div v-if="Number(quote.frequency_discount) > 0" class="flex justify-between gap-4 text-green-700">
                <dt>Recurring discount ({{ quote.frequency_discount_percent }}%)</dt>
                <dd class="whitespace-nowrap font-medium">-{{ money(quote.frequency_discount) }}</dd>
            </div>
            <div v-if="Number(quote.coupon_discount) > 0" class="flex justify-between gap-4 text-green-700">
                <dt>Coupon</dt>
                <dd class="whitespace-nowrap font-medium">-{{ money(quote.coupon_discount) }}</dd>
            </div>
            <div v-if="showTax" class="flex justify-between gap-4">
                <dt class="text-body">Tax ({{ quote.tax_rate }}%)</dt>
                <dd class="font-medium text-ink">{{ money(quote.tax) }}</dd>
            </div>
            <div class="flex items-baseline justify-between gap-4 border-t border-gray-200 pt-3">
                <dt class="text-base font-semibold text-ink">Total</dt>
                <dd class="text-2xl font-semibold text-ink">{{ money(quote.total) }}</dd>
            </div>
            <div class="flex justify-between gap-4 text-xs text-gray-500">
                <dt>Estimated duration</dt>
                <dd>{{ duration(quote.duration_minutes) }}</dd>
            </div>
        </dl>

        <div v-if="schedule || address" class="mt-4 space-y-2 border-t border-gray-100 pt-4 text-sm">
            <p v-if="schedule" class="text-body"><span class="font-medium text-ink">When:</span> {{ schedule }}</p>
            <p v-if="address" class="text-body"><span class="font-medium text-ink">Where:</span> {{ address }}</p>
        </div>

        <slot />
    </div>
</template>
