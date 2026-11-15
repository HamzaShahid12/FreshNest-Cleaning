<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import Btn from '@/Components/Btn.vue';
import FormCheckbox from '@/Components/FormCheckbox.vue';
import FormInput from '@/Components/FormInput.vue';
import FormSelect from '@/Components/FormSelect.vue';
import FormTextarea from '@/Components/FormTextarea.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Seo from '@/Components/Seo.vue';

const props = defineProps({ resource: String, def: Object, options: Object, record: Object, values: Object, display: { type: Object, default: () => ({}) } });
const base = `/admin/m/${props.resource}`;
const editable = props.def.fields.filter((f) => f.type !== 'display');
const form = useForm(Object.fromEntries(editable.map((f) => [f.key, props.values[f.key] ?? (f.type === 'checkbox' ? false : '')])));

const submit = () => {
    if (props.record) {
        // Multipart uploads need POST + method spoofing for PUT.
        form.transform((d) => ({ ...d, _method: 'put' })).post(`${base}/${props.record.id}`, { forceFormData: true, preserveScroll: true });
    } else {
        form.post(base, { forceFormData: true });
    }
};
const onFile = (key, e) => { form[key] = e.target.files[0] || null; };
const wide = (f) => ['textarea', 'lines', 'image'].includes(f.type);
</script>

<template>
    <Seo :title="`${record ? 'Edit' : 'Add'} ${def.singular}`" noindex />
    <Link :href="base" class="text-sm font-medium text-brand hover:underline">&larr; {{ def.title }}</Link>
    <PageHeader class="mt-3" :title="`${record ? 'Edit' : 'Add'} ${def.singular}`" />

    <form class="card card-pad grid max-w-4xl gap-4 sm:grid-cols-2" @submit.prevent="submit">
        <template v-for="f in def.fields" :key="f.key">
            <div v-if="f.type === 'display'" class="sm:col-span-2">
                <p class="label">{{ f.label }}</p>
                <p class="whitespace-pre-line rounded-lg bg-gray-50 px-3 py-2 text-sm text-body">{{ display[f.key] || '—' }}</p>
            </div>
            <div v-else-if="f.type === 'checkbox'" class="sm:col-span-2"><FormCheckbox v-model="form[f.key]" :label="f.label" :error="form.errors[f.key]" /></div>
            <div v-else-if="f.type === 'image'" class="sm:col-span-2">
                <label class="label" :for="`f-${f.key}`">{{ f.label }}</label>
                <img v-if="display[f.key + '_url']" :src="display[f.key + '_url']" alt="" class="mb-2 h-24 rounded-lg object-cover">
                <input :id="`f-${f.key}`" type="file" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm" @change="onFile(f.key, $event)">
                <p class="mt-1 text-xs text-gray-500">JPG, PNG or WebP, max 2 MB. Leave empty to keep the current image.</p>
                <p v-if="form.errors[f.key]" class="field-error">{{ form.errors[f.key] }}</p>
            </div>
            <div v-else-if="f.type === 'textarea' || f.type === 'lines'" class="sm:col-span-2"><FormTextarea v-model="form[f.key]" :label="f.label" :rows="f.type === 'lines' ? 5 : 4" :error="form.errors[f.key]" /></div>
            <FormSelect v-else-if="f.type === 'select'" v-model="form[f.key]" :label="f.label" placeholder="—" :options="options[f.key] || []" :error="form.errors[f.key]" />
            <FormInput v-else v-model="form[f.key]" :label="f.label" :type="f.type === 'number' ? 'number' : f.type === 'password' ? 'password' : f.type === 'date' ? 'date' : 'text'" :step="f.step" :autocomplete="f.type === 'password' ? 'new-password' : undefined" :class="wide(f) ? 'sm:col-span-2' : ''" :error="form.errors[f.key]" />
        </template>
        <div class="flex gap-3 sm:col-span-2"><Btn type="submit" :loading="form.processing">Save</Btn><Btn :href="base" variant="outline">Cancel</Btn></div>
    </form>
</template>
