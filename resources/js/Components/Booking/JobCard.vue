<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

// Staff job card (shape: StaffController::card). Large tap target for phones.
defineProps({ job: { type: Object, required: true } });
</script>

<template>
    <Link :href="`/staff/jobs/${job.reference}`" class="card block p-4 transition hover:border-brand/40 hover:shadow-md active:bg-brand-soft/40 sm:p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-lg font-semibold text-ink">{{ job.time }} – {{ job.ends_time }}</p>
                <p class="text-sm text-gray-500">{{ job.date }}</p>
            </div>
            <StatusBadge :status="job.status" :label="job.status_label" />
        </div>
        <p class="mt-3 font-medium text-ink">{{ job.service }}</p>
        <p class="mt-1 flex items-center gap-1.5 text-sm text-body">
            <Icon name="map-pin" class="h-4 w-4 text-brand" />
            <span>{{ job.address || job.city }}</span>
        </p>
        <p class="mt-2 flex items-center gap-1 text-sm font-semibold text-brand">Open job <Icon name="chevron-right" class="h-4 w-4" /></p>
    </Link>
</template>
