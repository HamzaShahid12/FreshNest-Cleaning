<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';

const props = defineProps({
    href: String,            // internal Inertia link
    external: Boolean,       // plain <a> (mailto:, tel:, downloads, other sites)
    type: { type: String, default: 'button' },
    variant: { type: String, default: 'primary' }, // primary | dark | outline | soft | ghost | danger
    size: { type: String, default: 'md' },         // sm | md | lg
    loading: Boolean,
    disabled: Boolean,
    block: Boolean,
    method: String,          // for Inertia links that must POST/DELETE
});

const classes = computed(() => [
    'btn', `btn-${props.variant}`,
    props.size === 'sm' ? 'btn-sm' : props.size === 'lg' ? 'btn-lg' : '',
    props.block ? 'w-full' : '',
]);
</script>

<template>
    <Link v-if="href && !external" :href="href" :method="method" :as="method ? 'button' : 'a'" :class="classes">
        <slot />
    </Link>
    <a v-else-if="href" :href="href" :class="classes"><slot /></a>
    <button v-else :type="type" :class="classes" :disabled="disabled || loading">
        <LoadingSpinner v-if="loading" size="h-4 w-4" />
        <slot />
    </button>
</template>
