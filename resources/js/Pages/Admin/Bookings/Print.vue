<script setup>
import { onMounted } from 'vue';
import { money, duration } from '@/Composables/useFormat';

const props = defineProps({ booking: Object });
onMounted(() => { setTimeout(() => window.print(), 400); });
</script>

<template>
    <div class="mx-auto max-w-2xl bg-white p-8 text-sm text-gray-800">
        <h1 class="text-2xl font-bold">FreshNest Cleaning: Job Sheet</h1>
        <p class="text-gray-500">Reference {{ booking.reference }}</p>
        <hr class="my-4">
        <table class="w-full">
            <tbody>
                <tr><th class="w-40 py-1 text-left">Service</th><td>{{ booking.service }}</td></tr>
                <tr><th class="py-1 text-left">When</th><td>{{ booking.date }}, {{ booking.time }} – {{ booking.ends_time }} ({{ duration(booking.duration_minutes) }})</td></tr>
                <tr v-if="booking.customer"><th class="py-1 text-left">Customer</th><td>{{ booking.customer.name }}, {{ booking.customer.phone }}</td></tr>
                <tr v-if="booking.customer"><th class="py-1 text-left">Address</th><td>{{ booking.customer.street }} {{ booking.customer.unit }}, {{ booking.customer.city }}, {{ booking.customer.state }} {{ booking.customer.zip }} {{ booking.customer.country }}</td></tr>
                <tr><th class="py-1 text-left">Property</th><td>{{ booking.property_type }}, {{ booking.bedrooms }} bd / {{ booking.bathrooms }} ba</td></tr>
                <tr><th class="py-1 text-left">Cleaner</th><td>{{ booking.employee || 'Unassigned' }}</td></tr>
                <tr><th class="py-1 text-left">Total</th><td>{{ money(booking.pricing.total) }} ({{ booking.payment_status }})</td></tr>
            </tbody>
        </table>
        <div v-if="booking.extras.length" class="mt-4"><p class="font-semibold">Extras</p><ul class="list-disc pl-5"><li v-for="(e, i) in booking.extras" :key="i">{{ e.name }} × {{ e.quantity }}</li></ul></div>
        <div v-if="booking.service_includes?.length" class="mt-4"><p class="font-semibold">Checklist</p><ul class="mt-1 space-y-1"><li v-for="(t, i) in booking.service_includes" :key="i">☐ {{ t }}</li></ul></div>
        <div v-if="booking.instructions" class="mt-4"><p class="font-semibold">Access instructions</p><p class="whitespace-pre-line">{{ booking.instructions }}</p></div>
        <div v-if="booking.work_notes" class="mt-4"><p class="font-semibold">Notes</p><p class="whitespace-pre-line">{{ booking.work_notes }}</p></div>
    </div>
</template>
