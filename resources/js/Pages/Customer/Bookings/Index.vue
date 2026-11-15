<script setup>
import { Link } from '@inertiajs/vue3';
import BookingCard from '@/Components/Booking/BookingCard.vue';
import Btn from '@/Components/Btn.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import Seo from '@/Components/Seo.vue';

defineProps({ bookings: Object, tab: String });
const tabs = [{ key: 'all', label: 'All' }, { key: 'upcoming', label: 'Upcoming' }, { key: 'past', label: 'Past' }];
</script>

<template>
    <Seo title="My bookings" noindex />
    <PageHeader title="My bookings"><Btn href="/book">Book a Cleaning</Btn></PageHeader>

    <div class="mb-5 inline-flex rounded-xl bg-white p-1 shadow-card" role="tablist">
        <Link v-for="t in tabs" :key="t.key" :href="`/account/bookings?tab=${t.key}`" role="tab" :aria-selected="tab === t.key" :class="['rounded-lg px-4 py-1.5 text-sm font-medium', tab === t.key ? 'bg-brand text-white' : 'text-body hover:bg-gray-100']">{{ t.label }}</Link>
    </div>

    <div class="space-y-3">
        <BookingCard v-for="b in bookings.data" :key="b.reference" :booking="b" />
        <div v-if="!bookings.data.length" class="card card-pad text-center text-sm text-gray-500">No bookings here yet.</div>
    </div>
    <div class="mt-8"><Pagination :links="bookings.links" /></div>
</template>
