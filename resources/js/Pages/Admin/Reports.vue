<script setup>
import { router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import Btn from '@/Components/Btn.vue';
import DataTable from '@/Components/DataTable.vue';
import FormInput from '@/Components/FormInput.vue';
import FormSelect from '@/Components/FormSelect.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Seo from '@/Components/Seo.vue';

const props = defineProps({ types: Array, filters: Object, columns: Array, rows: Array });
const state = reactive({ ...props.filters });
const query = () => new URLSearchParams(Object.entries(state).filter(([, v]) => v)).toString();
const run = () => router.get('/admin/reports', Object.fromEntries(Object.entries(state).filter(([, v]) => v)), { preserveState: true, replace: true });
</script>

<template>
    <Seo title="Reports" noindex />
    <PageHeader title="Reports"><a :href="`/admin/reports/export?${query()}`" class="btn btn-outline">Export CSV</a></PageHeader>
    <form class="card mb-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end" @submit.prevent="run">
        <FormSelect v-model="state.type" label="Report" :options="types.map((t) => ({ value: t.key, label: t.label }))" />
        <FormInput v-model="state.from" label="From" type="date" />
        <FormInput v-model="state.to" label="To" type="date" />
        <FormSelect v-model="state.group" label="Group by" :options="[{ value: 'day', label: 'Day' }, { value: 'week', label: 'Week' }, { value: 'month', label: 'Month' }, { value: 'year', label: 'Year' }]" />
        <Btn type="submit">Run report</Btn>
    </form>
    <DataTable :columns="columns" :rows="rows" empty="No data for this period." />
</template>
