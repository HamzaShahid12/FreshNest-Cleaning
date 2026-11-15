<script setup>
import DataTable from '@/Components/DataTable.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import SearchFilter from '@/Components/SearchFilter.vue';
import Seo from '@/Components/Seo.vue';

defineProps({ logs: Object, filters: Object });
</script>

<template>
    <Seo title="Audit log" noindex />
    <PageHeader title="Audit log" subtitle="Who changed what, and when." />
    <SearchFilter url="/admin/audit-logs" :filters="filters" placeholder="Action or description…" />
    <DataTable :columns="[{ key: 'at', label: 'When' }, { key: 'user', label: 'User' }, { key: 'action', label: 'Action' }, { key: 'description', label: 'Details' }, { key: 'ip', label: 'IP' }]" :rows="logs.data" empty="No activity recorded yet." />
    <div class="mt-6"><Pagination :links="logs.links" /></div>
</template>
