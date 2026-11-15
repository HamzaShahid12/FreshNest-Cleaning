<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import Btn from '@/Components/Btn.vue';
import FormCheckbox from '@/Components/FormCheckbox.vue';
import FormInput from '@/Components/FormInput.vue';
import FormTextarea from '@/Components/FormTextarea.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Seo from '@/Components/Seo.vue';

const props = defineProps({ customer: Object });
const form = useForm({
    name: props.customer?.name ?? '', email: props.customer?.email ?? '', phone: props.customer?.phone ?? '',
    admin_notes: props.customer?.admin_notes ?? '', is_active: props.customer?.is_active ?? true,
});
const submit = () => (props.customer ? form.put(`/admin/customers/${props.customer.id}`) : form.post('/admin/customers'));
</script>

<template>
    <Seo :title="customer ? 'Edit customer' : 'Add customer'" noindex />
    <PageHeader :title="customer ? 'Edit customer' : 'Add customer'" />
    <form class="card card-pad max-w-2xl space-y-4" @submit.prevent="submit">
        <FormInput v-model="form.name" label="Full name" required :error="form.errors.name" />
        <FormInput v-model="form.email" label="Email" type="email" required :error="form.errors.email" />
        <FormInput v-model="form.phone" label="Phone" type="tel" :error="form.errors.phone" />
        <FormTextarea v-model="form.admin_notes" label="Internal notes (never shown to the customer)" rows="3" :error="form.errors.admin_notes" />
        <FormCheckbox v-if="customer" v-model="form.is_active" label="Account is active (can sign in)" />
        <p v-else class="text-xs text-gray-500">The customer sets their own password using “Forgot password”.</p>
        <div class="flex gap-3"><Btn type="submit" :loading="form.processing">Save</Btn><Btn :href="customer ? `/admin/customers/${customer.id}` : '/admin/customers'" variant="outline">Cancel</Btn></div>
    </form>
</template>
