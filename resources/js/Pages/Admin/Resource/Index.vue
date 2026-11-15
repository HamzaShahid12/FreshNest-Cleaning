<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Btn from '@/Components/Btn.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DataTable from '@/Components/DataTable.vue';
import Icon from '@/Components/Icon.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import SearchFilter from '@/Components/SearchFilter.vue';
import Seo from '@/Components/Seo.vue';

const props = defineProps({ resource: String, def: Object, rows: Object, filters: Object });
const base = `/admin/m/${props.resource}`;
const removing = ref(null);
const del = useForm({});
const confirmDelete = () => del.delete(`${base}/${removing.value}`, { preserveScroll: true, onFinish: () => { removing.value = null; } });
</script>

<template>
    <Seo :title="def.title" noindex />
    <PageHeader :title="def.title">
        <Btn v-if="def.can_create !== false && !def.readonly" :href="`${base}/create`"><Icon name="plus" class="h-4 w-4" />Add {{ def.singular }}</Btn>
    </PageHeader>
    <SearchFilter :url="base" :filters="filters" />
    <DataTable :columns="def.columns" :rows="rows.data" :has-actions="!def.readonly" :empty="`No ${def.title.toLowerCase()} yet.`">
        <template v-if="!def.readonly" #actions="{ row }">
            <div class="flex justify-end gap-1">
                <Link :href="`${base}/${row.id}/edit`" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" :aria-label="`Edit ${def.singular}`"><Icon name="pencil" class="h-4 w-4" /></Link>
                <button type="button" class="rounded-lg p-2 text-red-500 hover:bg-red-50" :aria-label="`Delete ${def.singular}`" @click="removing = row.id"><Icon name="trash" class="h-4 w-4" /></button>
            </div>
        </template>
    </DataTable>
    <div class="mt-6"><Pagination :links="rows.links" /></div>
    <ConfirmationModal :show="removing !== null" :title="`Delete this ${def.singular}?`" message="This cannot be undone." confirm-label="Delete" danger :processing="del.processing" @close="removing = null" @confirm="confirmDelete" />
</template>
