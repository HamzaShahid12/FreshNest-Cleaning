<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import JobCard from '@/Components/Booking/JobCard.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Seo from '@/Components/Seo.vue';
import StatCard from '@/Components/StatCard.vue';

defineProps({ today: Array, upcoming: Array, stats: Object });
const name = computed(() => (usePage().props.auth?.user?.name || '').split(' ')[0]);
</script>

<template>
    <Seo title="My jobs" noindex />
    <PageHeader :title="`Hello, ${name}`" subtitle="Your assigned cleanings." />
    <div class="grid grid-cols-3 gap-3">
        <StatCard label="Today" :value="stats.today" icon="calendar" />
        <StatCard label="Upcoming" :value="stats.upcoming" icon="clock" tone="blue" />
        <StatCard label="Completed" :value="stats.completed" icon="check" tone="purple" />
    </div>
    <h2 class="mb-3 mt-8 font-display text-xl font-semibold text-ink">Today</h2>
    <div class="space-y-3">
        <JobCard v-for="j in today" :key="j.reference" :job="j" />
        <div v-if="!today.length" class="card card-pad text-center text-sm text-gray-500">No jobs scheduled for today.</div>
    </div>
    <h2 class="mb-3 mt-8 font-display text-xl font-semibold text-ink">Upcoming</h2>
    <div class="space-y-3">
        <JobCard v-for="j in upcoming" :key="j.reference" :job="j" />
        <div v-if="!upcoming.length" class="card card-pad text-center text-sm text-gray-500">Nothing else scheduled yet.</div>
    </div>
</template>
