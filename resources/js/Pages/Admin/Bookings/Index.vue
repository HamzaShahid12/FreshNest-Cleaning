<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DataTable from '@/Components/DataTable.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import SearchFilter from '@/Components/SearchFilter.vue';
import Seo from '@/Components/Seo.vue';

const props = defineProps({ bookings: Object, filters: Object, services: Array });
const page = usePage();
const fields = computed(() => [
    { key: 'status', label: 'Status', type: 'select', options: Object.entries(page.props.statuses || {}).map(([value, label]) => ({ value, label })) },
    { key: 'service', label: 'Service', type: 'select', options: props.services.map((s) => ({ value: s.id, label: s.name })) },
    { key: 'from', label: 'From', type: 'date' },
    { key: 'to', label: 'To', type: 'date' },
]);
const columns = [
    { key: 'reference', label: 'Reference' }, { key: 'customer', label: 'Customer' }, { key: 'service', label: 'Service' },
    { key: 'date', label: 'Date' }, { key: 'time', label: 'Time' }, { key: 'employee', label: 'Cleaner' },
    { key: 'total', label: 'Total', type: 'money' }, { key: 'status', label: 'Status', type: 'badge' }, { key: 'payment_status', label: 'Payment', type: 'badge' },
];
</script>

<template>
    <Seo title="Bookings" noindex />
    <PageHeader title="Bookings" />
    <SearchFilter url="/admin/bookings" :filters="filters" :fields="fields" placeholder="Reference, name, email or phone…" />
    <DataTable :columns="columns" :rows="bookings.data" empty="No bookings match these filters.">
        <template #cell-reference="{ row }"><Link :href="`/admin/bookings/${row.reference}`" class="font-medium text-brand hover:underline">{{ row.reference }}</Link></template>
        <template #cell-employee="{ row }">{{ row.employee || '—' }}</template>
    </DataTable>
    <div class="mt-6"><Pagination :links="bookings.links" /></div>
</template>
