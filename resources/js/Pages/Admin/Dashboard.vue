<script setup>
import { Link } from '@inertiajs/vue3';
import ChartCard from '@/Components/ChartCard.vue';
import DataTable from '@/Components/DataTable.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Seo from '@/Components/Seo.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { money } from '@/Composables/useFormat';

defineProps({ stats: Object, chart: Array, statusBreakdown: Array, recentBookings: Array, recentCustomers: Array, upcoming: Array });
const cols = [{ key: 'reference', label: 'Ref' }, { key: 'customer', label: 'Customer' }, { key: 'service', label: 'Service' }, { key: 'date', label: 'Date' }, { key: 'status', label: 'Status', type: 'badge' }];
</script>

<template>
    <Seo title="Dashboard" noindex />
    <PageHeader title="Dashboard" subtitle="Business overview" />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard label="Revenue (this month)" :value="money(stats.revenue_month)" icon="dollar" />
        <StatCard label="Revenue (all time)" :value="money(stats.revenue_total)" icon="dollar" tone="purple" />
        <StatCard label="Bookings today" :value="stats.bookings_today" icon="calendar" tone="blue" />
        <StatCard label="Pending approval" :value="stats.pending" icon="clock" tone="amber" />
        <StatCard label="Confirmed" :value="stats.confirmed" icon="check" tone="blue" />
        <StatCard label="Completed" :value="stats.completed" icon="check" />
        <StatCard label="Customers" :value="stats.customers" icon="users" tone="gray" />
        <StatCard label="Active cleaners" :value="stats.employees" icon="users" tone="gray" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2"><ChartCard title="Revenue, last 6 months" subtitle="Paid bookings" :data="chart" type="bar" /></div>
        <div class="card card-pad">
            <h2 class="font-display text-lg font-semibold text-ink">By status</h2>
            <ul class="mt-3 space-y-2 text-sm">
                <li v-for="s in statusBreakdown" :key="s.key" class="flex items-center justify-between"><StatusBadge :status="s.key" :label="s.label" /><span class="font-semibold text-ink">{{ s.count }}</span></li>
            </ul>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section>
            <div class="mb-2 flex items-center justify-between"><h2 class="font-display text-lg font-semibold text-ink">Latest bookings</h2><Link href="/admin/bookings" class="text-sm font-medium text-brand hover:underline">View all</Link></div>
            <DataTable :columns="cols" :rows="recentBookings" empty="No bookings yet.">
                <template #cell-reference="{ row }"><Link :href="`/admin/bookings/${row.reference}`" class="font-medium text-brand hover:underline">{{ row.reference }}</Link></template>
            </DataTable>
        </section>
        <section>
            <h2 class="mb-2 font-display text-lg font-semibold text-ink">Upcoming appointments</h2>
            <DataTable :columns="cols" :rows="upcoming" empty="Nothing upcoming.">
                <template #cell-reference="{ row }"><Link :href="`/admin/bookings/${row.reference}`" class="font-medium text-brand hover:underline">{{ row.reference }}</Link></template>
            </DataTable>
        </section>
    </div>

    <section class="mt-6">
        <h2 class="mb-2 font-display text-lg font-semibold text-ink">New customers</h2>
        <DataTable :columns="[{ key: 'name', label: 'Name' }, { key: 'email', label: 'Email' }, { key: 'joined', label: 'Joined' }]" :rows="recentCustomers">
            <template #cell-name="{ row }"><Link :href="`/admin/customers/${row.id}`" class="font-medium text-brand hover:underline">{{ row.name }}</Link></template>
        </DataTable>
    </section>
</template>
