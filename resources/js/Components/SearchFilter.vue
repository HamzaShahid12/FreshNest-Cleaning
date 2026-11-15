<script setup>
import { router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';
import Icon from '@/Components/Icon.vue';

/**
 * Filter bar that applies via Inertia GET (state preserved, history replaced).
 * fields: [{ key, label, type: 'select' | 'date', options?: [{ value, label }], placeholder? }]
 */
const props = defineProps({
    url: { type: String, required: true },
    filters: { type: Object, default: () => ({}) },
    fields: { type: Array, default: () => [] },
    searchKey: { type: String, default: 'search' },
    placeholder: { type: String, default: 'Search…' },
});

const state = reactive({ [props.searchKey]: props.filters[props.searchKey] ?? '' });
props.fields.forEach((f) => { state[f.key] = props.filters[f.key] ?? ''; });

let timer = null;
const apply = () => {
    const query = Object.fromEntries(Object.entries(state).filter(([, v]) => v !== '' && v !== null));
    router.get(props.url, query, { preserveState: true, replace: true, preserveScroll: true });
};
watch(() => state[props.searchKey], () => { clearTimeout(timer); timer = setTimeout(apply, 350); });
props.fields.forEach((f) => watch(() => state[f.key], apply));

const clear = () => { Object.keys(state).forEach((k) => { state[k] = ''; }); };
const dirty = () => Object.values(state).some((v) => v !== '');
</script>

<template>
    <form class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:flex-wrap sm:items-end" role="search" @submit.prevent="apply">
        <div class="relative min-w-0 flex-1 sm:min-w-[14rem]">
            <label :for="`sf-${searchKey}`" class="label">Search</label>
            <Icon name="search" class="pointer-events-none absolute bottom-3 left-3 h-4 w-4 text-gray-400" />
            <input :id="`sf-${searchKey}`" v-model="state[searchKey]" type="search" :placeholder="placeholder" class="input pl-9">
        </div>
        <div v-for="f in fields" :key="f.key" class="sm:w-44">
            <label :for="`sf-${f.key}`" class="label">{{ f.label }}</label>
            <select v-if="f.type === 'select'" :id="`sf-${f.key}`" v-model="state[f.key]" class="input">
                <option value="">{{ f.placeholder || 'All' }}</option>
                <option v-for="o in f.options" :key="o.value" :value="o.value">{{ o.label }}</option>
            </select>
            <input v-else :id="`sf-${f.key}`" v-model="state[f.key]" :type="f.type || 'text'" class="input">
        </div>
        <button v-if="dirty()" type="button" class="btn btn-ghost btn-sm self-end" @click="clear">Clear</button>
    </form>
</template>
