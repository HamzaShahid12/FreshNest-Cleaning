<script setup>
import { Link } from '@inertiajs/vue3';
import DataTable from '@/Components/DataTable.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import Seo from '@/Components/Seo.vue';

defineProps({ invoices: Object });
const columns = [
    { key: 'number', label: 'Invoice' }, { key: 'issued_on', label: 'Issued' }, { key: 'service', label: 'Service' },
    { key: 'booking_reference', label: 'Booking' }, { key: 'total', label: 'Total', type: 'money' }, { key: 'status', label: 'Status', type: 'badge' },
];
</script>

<template>
    <Seo title="Invoices" noindex />
    <PageHeader title="Invoice history" />
    <DataTable :columns="columns" :rows="invoices.data" empty="No invoices yet.">
        <template #cell-booking_reference="{ row }"><Link :href="`/account/bookings/${row.booking_reference}`" class="font-medium text-brand hover:underline">{{ row.booking_reference }}</Link></template>
    </DataTable>
    <div class="mt-6"><Pagination :links="invoices.links" /></div>
</template>
