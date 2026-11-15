<script setup>
import { Link } from '@inertiajs/vue3';
import Btn from '@/Components/Btn.vue';
import DataTable from '@/Components/DataTable.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Seo from '@/Components/Seo.vue';

defineProps({ employee: Object, upcoming: Array, history: Array });
const days = ['', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const cols = [{ key: 'reference', label: 'Ref' }, { key: 'service', label: 'Service' }, { key: 'date', label: 'Date' }, { key: 'time', label: 'Time' }, { key: 'status', label: 'Status', type: 'badge' }];
</script>

<template>
    <Seo :title="employee.name" noindex />
    <Link href="/admin/employees" class="text-sm font-medium text-brand hover:underline">&larr; Employees</Link>
    <PageHeader class="mt-3" :title="employee.name" :subtitle="`${employee.profile?.job_title || 'Cleaner'} · ${employee.email}`">
        <Btn :href="`/admin/employees/${employee.id}/edit`" variant="outline">Edit</Btn>
    </PageHeader>
    <div class="card card-pad grid gap-3 text-sm sm:grid-cols-2">
        <p><span class="text-gray-500">Phone:</span> {{ employee.phone || '—' }}</p>
        <p><span class="text-gray-500">Status:</span> {{ employee.is_active ? 'Active' : 'Deactivated' }}</p>
        <p><span class="text-gray-500">Working days:</span> {{ (employee.profile?.working_days || []).map((d) => days[d]).join(', ') || '—' }}</p>
        <p><span class="text-gray-500">Hours:</span> {{ employee.profile?.work_start || '08:00' }} – {{ employee.profile?.work_end || '17:00' }}</p>
        <p class="sm:col-span-2"><span class="text-gray-500">Services:</span> {{ employee.skill_names.length ? employee.skill_names.join(', ') : 'All services' }}</p>
        <p v-if="employee.profile?.bio" class="sm:col-span-2 text-body">{{ employee.profile.bio }}</p>
    </div>
    <h2 class="mb-2 mt-8 font-display text-lg font-semibold text-ink">Upcoming jobs</h2>
    <DataTable :columns="cols" :rows="upcoming" empty="No upcoming jobs.">
        <template #cell-reference="{ row }"><Link :href="`/admin/bookings/${row.reference}`" class="font-medium text-brand hover:underline">{{ row.reference }}</Link></template>
    </DataTable>
    <h2 class="mb-2 mt-8 font-display text-lg font-semibold text-ink">Completed jobs</h2>
    <DataTable :columns="cols" :rows="history" empty="No completed jobs.">
        <template #cell-reference="{ row }"><Link :href="`/admin/bookings/${row.reference}`" class="font-medium text-brand hover:underline">{{ row.reference }}</Link></template>
    </DataTable>
</template>
