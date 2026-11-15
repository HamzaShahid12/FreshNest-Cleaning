<script setup>
import Icon from '@/Components/Icon.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { duration, money, titleCase } from '@/Composables/useFormat';

// booking: App\Support\BookingView::detail(). Contact/address blocks only render when the server included them.
defineProps({ booking: { type: Object, required: true }, history: { type: Boolean, default: true } });

// Exact pin when the customer chose one on the map, otherwise a text search of the address.
const mapUrl = (c) => 'https://www.google.com/maps/search/?api=1&query=' + (c.latitude !== null && c.latitude !== undefined && c.longitude !== null && c.longitude !== undefined
    ? `${c.latitude},${c.longitude}`
    : encodeURIComponent([c.street, c.city, c.state, c.zip, c.country].filter(Boolean).join(', ')));
</script>

<template>
    <div class="grid gap-5 lg:grid-cols-2">
        <section class="card card-pad">
            <h2 class="flex items-center gap-2 font-display text-lg font-semibold text-ink"><Icon name="calendar" class="text-brand" />Appointment</h2>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Service</dt><dd class="text-right font-medium text-ink">{{ booking.service }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Date</dt><dd class="text-right font-medium text-ink">{{ booking.date }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Time</dt><dd class="text-right font-medium text-ink">{{ booking.time }} – {{ booking.ends_time }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Estimated duration</dt><dd class="text-right text-ink">{{ duration(booking.duration_minutes) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Frequency</dt><dd class="text-right text-ink">{{ booking.frequency }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Property</dt><dd class="text-right text-ink">{{ booking.property_type }} · {{ booking.bedrooms }} bd · {{ booking.bathrooms }} ba<span v-if="booking.sqft"> · {{ booking.sqft }} sq ft</span></dd></div>
                <div v-if="booking.employee_detail" class="flex justify-between gap-4"><dt class="text-gray-500">Cleaner</dt><dd class="text-right text-ink">{{ booking.employee_detail.name }}<span v-if="booking.employee_detail.job_title" class="block text-xs text-gray-500">{{ booking.employee_detail.job_title }}</span></dd></div>
                <div class="flex items-center justify-between gap-4"><dt class="text-gray-500">Status</dt><dd><StatusBadge :status="booking.status" :label="booking.status_label" /></dd></div>
                <div v-if="booking.cancel_reason" class="flex justify-between gap-4"><dt class="text-gray-500">Reason</dt><dd class="text-right text-ink">{{ booking.cancel_reason }}</dd></div>
            </dl>
        </section>

        <section class="card card-pad">
            <h2 class="flex items-center gap-2 font-display text-lg font-semibold text-ink"><Icon name="map-pin" class="text-brand" />Location &amp; contact</h2>
            <dl v-if="booking.customer" class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Name</dt><dd class="text-right text-ink">{{ booking.customer.name }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Email</dt><dd class="break-all text-right text-ink">{{ booking.customer.email }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Phone</dt><dd class="text-right text-ink">{{ booking.customer.phone }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Address</dt><dd class="text-right text-ink">{{ booking.customer.street }}<span v-if="booking.customer.unit">, {{ booking.customer.unit }}</span><br>{{ booking.customer.city }}<span v-if="booking.customer.state">, {{ booking.customer.state }}</span> {{ booking.customer.zip }}<span v-if="booking.customer.country"><br>{{ booking.customer.country }}</span></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Map</dt><dd class="text-right"><a :href="mapUrl(booking.customer)" target="_blank" rel="noopener noreferrer" class="font-medium text-brand hover:underline">Open in Google Maps</a></dd></div>
                <div v-if="booking.instructions" class="pt-1"><dt class="text-gray-500">Access instructions</dt><dd class="mt-0.5 whitespace-pre-line text-ink">{{ booking.instructions }}</dd></div>
                <div v-if="booking.customer.notes" class="pt-1"><dt class="text-gray-500">Customer notes</dt><dd class="mt-0.5 whitespace-pre-line text-ink">{{ booking.customer.notes }}</dd></div>
            </dl>
            <p v-else class="mt-4 text-sm text-gray-500">Customer details are available once the job is assigned.</p>
        </section>

        <section class="card card-pad">
            <h2 class="flex items-center gap-2 font-display text-lg font-semibold text-ink"><Icon name="dollar" class="text-brand" />Price</h2>
            <p v-if="booking.requires_quote && !booking.pricing.total" class="mt-4 text-sm text-gray-500">Custom quotation: pricing is confirmed after review.</p>
            <dl v-else class="mt-4 space-y-2 text-sm">
                <div v-for="(e, i) in booking.extras" :key="i" class="flex justify-between gap-4"><dt class="text-body">{{ e.name }}</dt><dd class="text-ink">{{ money(e.price * e.quantity) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-body">Subtotal</dt><dd class="text-ink">{{ money(booking.pricing.subtotal) }}</dd></div>
                <div v-if="booking.pricing.discount_total > 0" class="flex justify-between gap-4 text-green-700"><dt>Discounts<span v-if="booking.pricing.coupon_code"> ({{ booking.pricing.coupon_code }})</span></dt><dd>-{{ money(booking.pricing.discount_total) }}</dd></div>
                <div v-if="booking.pricing.tax > 0" class="flex justify-between gap-4"><dt class="text-body">Tax</dt><dd class="text-ink">{{ money(booking.pricing.tax) }}</dd></div>
                <div class="flex justify-between gap-4 border-t border-gray-100 pt-2 text-base font-semibold"><dt class="text-ink">Total</dt><dd class="text-ink">{{ money(booking.pricing.total) }}</dd></div>
            </dl>
            <div v-if="booking.payments.length || booking.payment_status" class="mt-4 space-y-1.5 border-t border-gray-100 pt-3 text-sm">
                <div class="flex items-center justify-between"><span class="text-gray-500">Payment</span><span class="flex items-center gap-2"><span class="text-ink">{{ titleCase(booking.payment_method) }}</span><StatusBadge :status="booking.payment_status" /></span></div>
                <div v-if="booking.invoice" class="flex justify-between"><span class="text-gray-500">Invoice</span><span class="text-ink">{{ booking.invoice.number }}</span></div>
            </div>
        </section>

        <section v-if="history" class="card card-pad">
            <h2 class="flex items-center gap-2 font-display text-lg font-semibold text-ink"><Icon name="clock" class="text-brand" />Timeline</h2>
            <ol class="mt-4 space-y-4 border-l-2 border-brand-soft pl-4">
                <li v-for="(h, i) in booking.history" :key="i" class="relative text-sm">
                    <span class="absolute -left-[1.4rem] top-1 h-2.5 w-2.5 rounded-full bg-brand" />
                    <p class="font-medium text-ink">{{ titleCase(h.to) }}<span v-if="h.by" class="font-normal text-gray-500"> · {{ h.by }}</span></p>
                    <p v-if="h.note" class="text-body">{{ h.note }}</p>
                    <p class="text-xs text-gray-400">{{ h.at }}</p>
                </li>
            </ol>
        </section>
    </div>
</template>
