<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Btn from '@/Components/Btn.vue';
import FormInput from '@/Components/FormInput.vue';
import FormTextarea from '@/Components/FormTextarea.vue';
import LocationPicker from '@/Components/LocationPicker.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Seo from '@/Components/Seo.vue';

const props = defineProps({ groups: Array });
const active = ref(props.groups[0]?.key);
const form = useForm(Object.fromEntries(props.groups.flatMap((g) => g.fields.map((f) => [f.key, f.value ?? '']))));

// Business location: picked on the map; address text, latitude and longitude are saved with the other settings.
const hidden = ['business_lat', 'business_lng'];
const num = (v) => (v === '' || v === null || v === undefined || Number.isNaN(Number(v)) ? null : Number(v));
const onLocation = (p) => {
    if (p.street !== undefined) {
        const line2 = [p.state, p.zip].filter(Boolean).join(' ');
        form.business_address = [p.street, p.city, line2, p.country].filter(Boolean).join(', ');
    }
    form.business_lat = p.latitude; form.business_lng = p.longitude;
};
const clearLocation = () => { form.business_lat = ''; form.business_lng = ''; };
const save = () => form.put('/admin/settings', { preserveScroll: true });
</script>

<template>
    <Seo title="Settings" noindex />
    <PageHeader title="Settings" subtitle="Business details, pricing, booking rules and email templates." />
    <form @submit.prevent="save">
        <div class="mb-5 flex flex-wrap gap-2" role="tablist">
            <button v-for="g in groups" :key="g.key" type="button" role="tab" :aria-selected="active === g.key" :class="['rounded-lg px-4 py-1.5 text-sm font-medium', active === g.key ? 'bg-brand text-white' : 'bg-white text-body shadow-card hover:bg-gray-50']" @click="active = g.key">{{ g.label }}</button>
        </div>
        <template v-for="g in groups" :key="g.key">
            <div v-show="active === g.key" class="card card-pad grid gap-4 sm:grid-cols-2">
                <div v-if="g.key === 'business'" class="space-y-3 sm:col-span-2">
                    <div class="flex items-center justify-between gap-3">
                        <div><h2 class="font-display text-lg font-semibold text-ink">Business location</h2><p class="text-sm text-gray-500">Search your address or tap the map. It fills in the business address below and shows a map and “Get directions” on your Contact page. Works in any country.</p></div>
                        <button v-if="num(form.business_lat) !== null" type="button" class="btn btn-ghost btn-sm shrink-0" @click="clearLocation">Remove pin</button>
                    </div>
                    <LocationPicker v-if="active === 'business'" :latitude="num(form.business_lat)" :longitude="num(form.business_lng)" @select="onLocation" />
                    <p v-if="num(form.business_lat) !== null" class="text-xs text-gray-500">Pin: {{ Number(form.business_lat).toFixed(5) }}, {{ Number(form.business_lng).toFixed(5) }}. Remember to press “Save settings”.</p>
                    <p v-if="form.errors.business_lat || form.errors.business_lng" class="field-error">{{ form.errors.business_lat || form.errors.business_lng }}</p>
                </div>
                <template v-for="f in g.fields.filter((x) => !hidden.includes(x.key))" :key="f.key">
                    <div v-if="f.type === 'textarea'" class="sm:col-span-2"><FormTextarea v-model="form[f.key]" :label="f.label" rows="4" :error="form.errors[f.key]" /></div>
                    <FormInput v-else v-model="form[f.key]" :label="f.label" :type="f.type === 'number' ? 'number' : 'text'" :step="f.type === 'number' ? 'any' : undefined" :error="form.errors[f.key]" />
                </template>
            </div>
        </template>
        <p v-if="Object.keys(form.errors).length" class="mt-3 text-sm text-red-600">Some values are invalid. Check each tab.</p>
        <div class="mt-5"><Btn type="submit" :loading="form.processing">Save settings</Btn></div>
    </form>
</template>
