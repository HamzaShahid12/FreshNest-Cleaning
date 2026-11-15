<script setup>
import JobCard from '@/Components/Booking/JobCard.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import Seo from '@/Components/Seo.vue';

defineProps({ jobs: Object });
</script>

<template>
    <Seo title="Job history" noindex />
    <PageHeader title="Completed jobs" />
    <div class="space-y-3">
        <JobCard v-for="j in jobs.data" :key="j.reference" :job="j" />
        <div v-if="!jobs.data.length" class="card card-pad text-center text-sm text-gray-500">No completed jobs yet.</div>
    </div>
    <div class="mt-6"><Pagination :links="jobs.links" /></div>
</template>
