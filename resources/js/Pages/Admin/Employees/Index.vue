<script setup>
import { Link } from '@inertiajs/vue3';
import Btn from '@/Components/Btn.vue';
import DataTable from '@/Components/DataTable.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import SearchFilter from '@/Components/SearchFilter.vue';
import Seo from '@/Components/Seo.vue';

defineProps({ employees: Object, filters: Object });
</script>

<template>
    <Seo title="Employees" noindex />
    <PageHeader title="Employees"><Btn href="/admin/employees/create">Add employee</Btn></PageHeader>
    <SearchFilter url="/admin/employees" :filters="filters" placeholder="Name or email…" />
    <DataTable :columns="[{ key: 'name', label: 'Name' }, { key: 'job_title', label: 'Role' }, { key: 'email', label: 'Email' }, { key: 'hours', label: 'Hours' }, { key: 'completed_jobs', label: 'Jobs done' }, { key: 'is_active', label: 'Active', type: 'bool' }]" :rows="employees.data" empty="No employees yet.">
        <template #cell-name="{ row }"><Link :href="`/admin/employees/${row.id}`" class="font-medium text-brand hover:underline">{{ row.name }}</Link></template>
    </DataTable>
    <div class="mt-6"><Pagination :links="employees.links" /></div>
</template>
