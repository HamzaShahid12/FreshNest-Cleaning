<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import BookingCard from '@/Components/Booking/BookingCard.vue';
import Btn from '@/Components/Btn.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Seo from '@/Components/Seo.vue';
import StatCard from '@/Components/StatCard.vue';
import { money } from '@/Composables/useFormat';

defineProps({ stats: Object, upcoming: Array, recent: Array, needsReview: Array, addressCount: Number });
const name = computed(() => (usePage().props.auth?.user?.name || '').split(' ')[0]);
</script>

<template>
    <Seo title="My account" noindex />
    <PageHeader :title="`Welcome back, ${name}`" subtitle="Here is what is happening with your cleanings.">
        <Btn href="/book">Book a Cleaning</Btn>
    </PageHeader>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard label="Total bookings" :value="stats.total_bookings" icon="calendar" />
        <StatCard label="Upcoming" :value="stats.upcoming" icon="clock" tone="blue" />
        <StatCard label="Completed" :value="stats.completed" icon="check" tone="purple" />
        <StatCard label="Total spent" :value="money(stats.total_spent)" icon="dollar" tone="amber" />
    </div>

    <div v-if="needsReview.length" class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5">
        <p class="font-semibold text-amber-900">How did we do? Leave a review for your recent cleaning{{ needsReview.length > 1 ? 's' : '' }}.</p>
        <div class="mt-3 flex flex-wrap gap-2"><Btn v-for="b in needsReview" :key="b.reference" :href="`/account/bookings/${b.reference}`" size="sm" variant="outline">{{ b.service }} · {{ b.date }}</Btn></div>
    </div>

    <div class="mt-8 grid gap-8 lg:grid-cols-2">
        <section>
            <div class="mb-3 flex items-center justify-between"><h2 class="font-display text-xl font-semibold text-ink">Upcoming</h2><Link href="/account/bookings?tab=upcoming" class="text-sm font-medium text-brand hover:underline">View all</Link></div>
            <div class="space-y-3">
                <BookingCard v-for="b in upcoming" :key="b.reference" :booking="b" />
                <div v-if="!upcoming.length" class="card card-pad text-center text-sm text-gray-500">No upcoming cleanings. <Link href="/book" class="font-semibold text-brand hover:underline">Book one now</Link>.</div>
            </div>
        </section>
        <section>
            <div class="mb-3 flex items-center justify-between"><h2 class="font-display text-xl font-semibold text-ink">Recent</h2><Link href="/account/bookings?tab=past" class="text-sm font-medium text-brand hover:underline">View all</Link></div>
            <div class="space-y-3">
                <BookingCard v-for="b in recent" :key="b.reference" :booking="b" />
                <div v-if="!recent.length" class="card card-pad text-center text-sm text-gray-500">Your past bookings will appear here.</div>
            </div>
        </section>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-3">
        <Link href="/account/addresses" class="card card-pad text-sm transition hover:border-brand"><p class="font-semibold text-ink">Saved addresses</p><p class="text-gray-500">{{ addressCount }} saved</p></Link>
        <Link href="/account/invoices" class="card card-pad text-sm transition hover:border-brand"><p class="font-semibold text-ink">Invoices</p><p class="text-gray-500">View and download</p></Link>
        <Link href="/account/support" class="card card-pad text-sm transition hover:border-brand"><p class="font-semibold text-ink">Need help?</p><p class="text-gray-500">Contact support</p></Link>
    </div>
</template>
