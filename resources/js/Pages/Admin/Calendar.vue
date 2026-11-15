<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageHeader from '@/Components/PageHeader.vue';
import Seo from '@/Components/Seo.vue';

const props = defineProps({ month: String, label: String, prev: String, next: String, firstWeekday: Number, daysInMonth: Number, today: String, events: Array });
const cells = computed(() => {
    const out = [];
    for (let i = 1; i < props.firstWeekday; i++) out.push(null);
    for (let d = 1; d <= props.daysInMonth; d++) {
        const date = `${props.month}-${String(d).padStart(2, '0')}`;
        out.push({ d, date, items: props.events.filter((e) => e.date === date) });
    }
    return out;
});
const tone = { pending: 'bg-amber-100 text-amber-900', confirmed: 'bg-blue-100 text-blue-900', assigned: 'bg-indigo-100 text-indigo-900', in_progress: 'bg-purple-100 text-purple-900', completed: 'bg-green-100 text-green-900', cancelled: 'bg-gray-200 text-gray-600', rejected: 'bg-gray-200 text-gray-600' };
</script>

<template>
    <Seo title="Calendar" noindex />
    <PageHeader :title="label" subtitle="All appointments by day">
        <Link :href="`/admin/calendar?month=${prev}`" class="btn btn-outline btn-sm">&larr; Prev</Link>
        <Link href="/admin/calendar" class="btn btn-outline btn-sm">Today</Link>
        <Link :href="`/admin/calendar?month=${next}`" class="btn btn-outline btn-sm">Next &rarr;</Link>
    </PageHeader>
    <div class="overflow-x-auto">
        <div class="min-w-[56rem]">
            <div class="grid grid-cols-7 gap-px rounded-t-xl bg-gray-200 text-center text-xs font-semibold uppercase text-gray-500">
                <div v-for="d in ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']" :key="d" class="bg-gray-50 py-2">{{ d }}</div>
            </div>
            <div class="grid grid-cols-7 gap-px bg-gray-200">
                <div v-for="(c, i) in cells" :key="i" :class="['min-h-[7rem] bg-white p-1.5', !c && 'bg-gray-50']">
                    <template v-if="c">
                        <p :class="['mb-1 text-xs font-semibold', c.date === today ? 'inline-flex h-5 w-5 items-center justify-center rounded-full bg-brand text-white' : 'text-gray-500']">{{ c.d }}</p>
                        <Link v-for="e in c.items" :key="e.reference" :href="`/admin/bookings/${e.reference}`" :class="['mb-1 block truncate rounded px-1.5 py-0.5 text-xs', tone[e.status] || tone.pending]" :title="`${e.time} ${e.service}, ${e.customer}${e.employee ? ' (' + e.employee + ')' : ''}`">{{ e.time }} {{ e.customer }}</Link>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
