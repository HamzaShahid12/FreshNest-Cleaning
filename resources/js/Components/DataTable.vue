<script setup>
import ReviewStars from '@/Components/ReviewStars.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { money } from '@/Composables/useFormat';

/**
 * columns: [{ key, label, type? }]  type: text | money | bool | date | datetime | badge | rating | limit
 * Override any cell with the slot  #cell-<key>="{ row, value }"; add a trailing actions column with #actions="{ row }".
 * Keys may use dot notation only for flat row keys supplied by the server (e.g. "category.name").
 */
const props = defineProps({
    columns: { type: Array, required: true },
    rows: { type: Array, default: () => [] },
    empty: { type: String, default: 'Nothing to show yet.' },
    hasActions: Boolean,
});

const fmtDate = (v, time) => {
    if (!v) return '-';
    const d = new Date(v);
    if (Number.isNaN(d.getTime())) return v;
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', ...(time ? { hour: 'numeric', minute: '2-digit' } : {}) });
};
const limit = (v) => (v && String(v).length > 70 ? String(v).slice(0, 70) + '…' : v ?? '-');
</script>

<template>
    <div class="overflow-x-auto rounded-2xl border border-gray-100 bg-white shadow-card">
        <table class="table-base">
            <thead>
                <tr>
                    <th v-for="c in columns" :key="c.key" scope="col">{{ c.label }}</th>
                    <th v-if="hasActions || $slots.actions" scope="col"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <tr v-if="!rows.length">
                    <td :colspan="columns.length + 1" class="py-10 text-center text-gray-500">{{ empty }}</td>
                </tr>
                <tr v-for="(row, i) in rows" :key="row.id ?? i" class="hover:bg-gray-50/60">
                    <td v-for="c in columns" :key="c.key">
                        <slot :name="`cell-${c.key}`" :row="row" :value="row[c.key]">
                            <template v-if="c.type === 'money'">{{ money(row[c.key]) }}</template>
                            <template v-else-if="c.type === 'bool'">
                                <StatusBadge :status="row[c.key] ? 'active' : 'inactive'" :label="row[c.key] ? 'Yes' : 'No'" />
                            </template>
                            <template v-else-if="c.type === 'date'">{{ fmtDate(row[c.key]) }}</template>
                            <template v-else-if="c.type === 'datetime'">{{ fmtDate(row[c.key], true) }}</template>
                            <StatusBadge v-else-if="c.type === 'badge'" :status="row[c.key]" />
                            <ReviewStars v-else-if="c.type === 'rating'" :rating="row[c.key]" size="h-4 w-4" />
                            <template v-else-if="c.type === 'limit'">{{ limit(row[c.key]) }}</template>
                            <template v-else>{{ row[c.key] ?? '-' }}</template>
                        </slot>
                    </td>
                    <td v-if="hasActions || $slots.actions" class="whitespace-nowrap text-right">
                        <slot name="actions" :row="row" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
