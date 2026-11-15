<script setup>
import { computed } from 'vue';
import { titleCase } from '@/Composables/useFormat';

const props = defineProps({ status: String, label: String });

const colors = {
    pending: 'bg-amber-100 text-amber-800', confirmed: 'bg-blue-100 text-blue-800', assigned: 'bg-indigo-100 text-indigo-800',
    in_progress: 'bg-purple-100 text-purple-800', completed: 'bg-green-100 text-green-800', cancelled: 'bg-gray-200 text-gray-700',
    rejected: 'bg-red-100 text-red-800',
    paid: 'bg-green-100 text-green-800', unpaid: 'bg-amber-100 text-amber-800', failed: 'bg-red-100 text-red-800',
    refunded: 'bg-gray-200 text-gray-700', partially_refunded: 'bg-orange-100 text-orange-800',
    new: 'bg-blue-100 text-blue-800', read: 'bg-gray-100 text-gray-700', replied: 'bg-green-100 text-green-800', closed: 'bg-gray-200 text-gray-700',
    active: 'bg-green-100 text-green-800', inactive: 'bg-gray-200 text-gray-700', void: 'bg-gray-200 text-gray-700',
    residential: 'bg-green-100 text-green-800', commercial: 'bg-blue-100 text-blue-800',
    super_admin: 'bg-purple-100 text-purple-800', admin: 'bg-indigo-100 text-indigo-800', manager: 'bg-blue-100 text-blue-800',
    staff: 'bg-green-100 text-green-800', customer: 'bg-gray-100 text-gray-700',
};
const cls = computed(() => colors[props.status] || 'bg-gray-100 text-gray-700');
const text = computed(() => props.label || titleCase(props.status));
</script>

<template>
    <span :class="['badge', cls]">{{ text }}</span>
</template>
