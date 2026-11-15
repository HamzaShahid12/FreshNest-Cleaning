<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { money } from '@/Composables/useFormat';

// One booking summary row (shape: BookingView::summary).
const props = defineProps({
    booking: { type: Object, required: true },
    prefix: { type: String, default: '/account/bookings' },
});

const parts = computed(() => {
    const [y, m, d] = String(props.booking.date_iso || '').split('-').map(Number);
    if (!y) return { month: '', day: '' };
    const dt = new Date(y, m - 1, d);

    return { month: dt.toLocaleDateString('en-US', { month: 'short' }), day: dt.getDate() };
});
</script>

<template>
    <Link :href="`${prefix}/${booking.reference}`" class="card flex flex-wrap items-center gap-4 p-4 transition hover:border-brand/40 hover:shadow-md">
        <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-xl bg-brand-soft text-brand-dark">
            <span class="text-[11px] font-semibold uppercase leading-none">{{ parts.month }}</span>
            <span class="text-xl font-semibold leading-tight">{{ parts.day }}</span>
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate font-semibold text-ink">{{ booking.service }}</p>
            <p class="text-sm text-gray-500">{{ booking.time }} – {{ booking.ends_time }} · {{ booking.city }}</p>
            <p v-if="booking.employee" class="text-xs text-gray-500">Cleaner: {{ booking.employee }}</p>
            <p class="text-xs text-gray-400">{{ booking.reference }}</p>
        </div>
        <div class="flex flex-col items-end gap-1">
            <StatusBadge :status="booking.status" :label="booking.status_label" />
            <span class="text-sm font-semibold text-ink">{{ booking.requires_quote && !booking.total ? 'Quote' : money(booking.total) }}</span>
        </div>
    </Link>
</template>
