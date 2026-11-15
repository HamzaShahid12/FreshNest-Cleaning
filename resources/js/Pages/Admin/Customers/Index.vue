<script setup>
import { Link } from '@inertiajs/vue3';
import Btn from '@/Components/Btn.vue';
import DataTable from '@/Components/DataTable.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import SearchFilter from '@/Components/SearchFilter.vue';
import Seo from '@/Components/Seo.vue';

defineProps({ customers: Object, filters: Object });
</script>

<template>
    <Seo title="Customers" noindex />
    <PageHeader title="Customers"><Btn href="/admin/customers/create">Add customer</Btn></PageHeader>
    <SearchFilter url="/admin/customers" :filters="filters" placeholder="Name, email or phone…" />
    <DataTable :columns="[{ key: 'name', label: 'Name' }, { key: 'email', label: 'Email' }, { key: 'phone', label: 'Phone' }, { key: 'bookings_count', label: 'Bookings' }, { key: 'is_active', label: 'Active', type: 'bool' }]" :rows="customers.data" empty="No customers found.">
        <template #cell-name="{ row }"><Link :href="`/admin/customers/${row.id}`" class="font-medium text-brand hover:underline">{{ row.name }}</Link></template>
    </DataTable>
    <div class="mt-6"><Pagination :links="customers.links" /></div>
</template>
