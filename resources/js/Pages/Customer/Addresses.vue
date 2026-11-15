<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Btn from '@/Components/Btn.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import FormCheckbox from '@/Components/FormCheckbox.vue';
import FormInput from '@/Components/FormInput.vue';
import FormModal from '@/Components/FormModal.vue';
import FormTextarea from '@/Components/FormTextarea.vue';
import Icon from '@/Components/Icon.vue';
import LocationPicker from '@/Components/LocationPicker.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Seo from '@/Components/Seo.vue';

defineProps({ addresses: Array });

const show = ref(false);
const editing = ref(null);
const blank = { label: '', street: '', unit: '', city: '', state: '', zip: '', country: 'United States', country_code: 'US', latitude: null, longitude: null, instructions: '', is_default: false };
const form = useForm({ ...blank });
let codedCountry = 'United States';
watch(() => form.country, (v) => { if (v !== codedCountry) form.country_code = ''; });
const onMapSelect = (p) => {
    if (p.street !== undefined) {
        codedCountry = p.country || form.country;
        Object.assign(form, { street: p.street || form.street, city: p.city, state: p.state, zip: p.zip, country: codedCountry, country_code: p.country_code || '' });
    }
    form.latitude = p.latitude; form.longitude = p.longitude;
};

const openNew = () => { editing.value = null; codedCountry = blank.country; form.defaults({ ...blank }).reset(); form.clearErrors(); show.value = true; };
const openEdit = (a) => {
    editing.value = a.id;
    codedCountry = a.country || 'United States';
    form.defaults({
        label: a.label || '', street: a.street, unit: a.unit || '', city: a.city, state: a.state || '', zip: a.zip || '',
        country: codedCountry, country_code: a.country_code || (a.country ? '' : 'US'),
        latitude: a.latitude !== null && a.latitude !== undefined ? Number(a.latitude) : null, longitude: a.longitude !== null && a.longitude !== undefined ? Number(a.longitude) : null,
        instructions: a.instructions || '', is_default: !!a.is_default,
    }).reset();
    form.clearErrors();
    show.value = true;
};
const save = () => {
    const opts = { preserveScroll: true, onSuccess: () => { show.value = false; } };
    editing.value ? form.put(`/account/addresses/${editing.value}`, opts) : form.post('/account/addresses', opts);
};

const removing = ref(null);
const del = useForm({});
const confirmDelete = () => del.delete(`/account/addresses/${removing.value}`, { preserveScroll: true, onSuccess: () => { removing.value = null; } });
</script>

<template>
    <Seo title="Saved addresses" noindex />
    <PageHeader title="Saved addresses" subtitle="Faster checkout for your next booking."><Btn @click="openNew"><Icon name="plus" class="h-4 w-4" />Add address</Btn></PageHeader>

    <div class="grid gap-4 sm:grid-cols-2">
        <div v-for="a in addresses" :key="a.id" class="card card-pad">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-semibold text-ink">{{ a.label || 'Address' }} <span v-if="a.is_default" class="badge ml-1 bg-brand-soft text-brand-dark">Default</span></p>
                    <p class="mt-1 text-sm text-body">{{ a.street }}<span v-if="a.unit">, {{ a.unit }}</span><br>{{ a.city }}<span v-if="a.state">, {{ a.state }}</span> {{ a.zip }}<span v-if="a.country"><br>{{ a.country }}</span></p>
                    <p v-if="a.instructions" class="mt-1 text-xs text-gray-500">{{ a.instructions }}</p>
                </div>
                <div class="flex gap-1">
                    <button type="button" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" :aria-label="`Edit ${a.label || 'address'}`" @click="openEdit(a)"><Icon name="pencil" class="h-4 w-4" /></button>
                    <button type="button" class="rounded-lg p-2 text-red-500 hover:bg-red-50" :aria-label="`Delete ${a.label || 'address'}`" @click="removing = a.id"><Icon name="trash" class="h-4 w-4" /></button>
                </div>
            </div>
        </div>
        <div v-if="!addresses.length" class="card card-pad text-center text-sm text-gray-500 sm:col-span-2">No saved addresses yet.</div>
    </div>

    <FormModal :show="show" :title="editing ? 'Edit address' : 'Add address'" :processing="form.processing" max-width="max-w-2xl" @close="show = false" @submit="save">
        <LocationPicker :latitude="form.latitude" :longitude="form.longitude" @select="onMapSelect" />
        <FormInput v-model="form.label" label="Label (e.g. Home)" :error="form.errors.label" />
        <FormInput v-model="form.street" label="Street address" autocomplete="address-line1" required :error="form.errors.street" />
        <FormInput v-model="form.unit" label="Apartment / unit" :error="form.errors.unit" />
        <div class="grid grid-cols-2 gap-3">
            <FormInput v-model="form.city" label="City" required :error="form.errors.city" />
            <FormInput v-model="form.state" label="State / province / region (optional)" :error="form.errors.state" />
            <FormInput v-model="form.zip" label="ZIP / postal code (optional)" :error="form.errors.zip" />
            <FormInput v-model="form.country" label="Country" :error="form.errors.country" />
        </div>
        <FormTextarea v-model="form.instructions" label="Access instructions" rows="2" :error="form.errors.instructions" />
        <FormCheckbox v-model="form.is_default" label="Make this my default address" />
    </FormModal>

    <ConfirmationModal :show="removing !== null" title="Delete address?" message="This removes the saved address. Existing bookings are not affected." confirm-label="Delete" danger :processing="del.processing" @close="removing = null" @confirm="confirmDelete" />
</template>
