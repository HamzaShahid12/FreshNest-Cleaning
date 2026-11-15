<script setup>
import { Link } from '@inertiajs/vue3';
import Btn from '@/Components/Btn.vue';
import DataTable from '@/Components/DataTable.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Seo from '@/Components/Seo.vue';
import StatCard from '@/Components/StatCard.vue';
import { money } from '@/Composables/useFormat';

defineProps({ customer: Object, stats: Object, bookings: Array, payments: Array, addresses: Array });
</script>

<template>
    <Seo :title="customer.name" noindex />
    <Link href="/admin/customers" class="text-sm font-medium text-brand hover:underline">&larr; Customers</Link>
    <PageHeader class="mt-3" :title="customer.name" :subtitle="`${customer.email}${customer.phone ? ' · ' + customer.phone : ''}`">
        <Btn :href="`/admin/customers/${customer.id}/edit`" variant="outline">Edit</Btn>
    </PageHeader>

    <div class="grid gap-4 sm:grid-cols-3">
        <StatCard label="Bookings" :value="stats.bookings" icon="calendar" />
        <StatCard label="Total paid" :value="money(stats.spent)" icon="dollar" tone="purple" />
        <StatCard label="Customer since" :value="customer.joined" icon="users" tone="gray" :hint="customer.verified ? 'Email verified' : 'Email not verified'" />
    </div>

    <p v-if="customer.admin_notes" class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"><strong>Internal notes:</strong> {{ customer.admin_notes }}</p>
    <p v-if="!customer.is_active" class="mt-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">This account is deactivated and cannot sign in.</p>

    <h2 class="mb-2 mt-8 font-display text-lg font-semibold text-ink">Bookings</h2>
    <DataTable :columns="[{ key: 'reference', label: 'Ref' }, { key: 'service', label: 'Service' }, { key: 'date', label: 'Date' }, { key: 'total', label: 'Total', type: 'money' }, { key: 'status', label: 'Status', type: 'badge' }]" :rows="bookings" empty="No bookings.">
        <template #cell-reference="{ row }"><Link :href="`/admin/bookings/${row.reference}`" class="font-medium text-brand hover:underline">{{ row.reference }}</Link></template>
    </DataTable>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section>
            <h2 class="mb-2 font-display text-lg font-semibold text-ink">Payments</h2>
            <DataTable :columns="[{ key: 'date', label: 'Date' }, { key: 'booking', label: 'Booking' }, { key: 'method', label: 'Method' }, { key: 'amount', label: 'Amount', type: 'money' }, { key: 'status', label: 'Status', type: 'badge' }]" :rows="payments" empty="No payments." />
        </section>
        <section>
            <h2 class="mb-2 font-display text-lg font-semibold text-ink">Addresses</h2>
            <div class="space-y-2">
                <div v-for="a in addresses" :key="a.id" class="card card-pad text-sm"><p class="font-medium text-ink">{{ a.label || 'Address' }}</p><p class="text-body">{{ a.street }}<span v-if="a.unit">, {{ a.unit }}</span>, {{ a.city }}, {{ a.state }} {{ a.zip }}</p></div>
                <p v-if="!addresses.length" class="text-sm text-gray-500">No saved addresses.</p>
            </div>
        </section>
    </div>
</template>
