<script setup>
import { useForm } from '@inertiajs/vue3';
import Btn from '@/Components/Btn.vue';
import FormInput from '@/Components/FormInput.vue';
import FormSelect from '@/Components/FormSelect.vue';
import FormTextarea from '@/Components/FormTextarea.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Seo from '@/Components/Seo.vue';

const props = defineProps({ bookings: Array });
const form = useForm({ subject: '', booking_reference: '', message: '' });
const submit = () => form.post('/account/support', { preserveScroll: true, onSuccess: () => form.reset() });
</script>

<template>
    <Seo title="Contact support" noindex />
    <PageHeader title="Contact support" subtitle="Tell us what you need and we will reply by email." />
    <form class="card card-pad max-w-2xl space-y-4" @submit.prevent="submit">
        <FormInput v-model="form.subject" label="Subject" required :error="form.errors.subject" />
        <FormSelect v-model="form.booking_reference" label="Related booking (optional)" placeholder="None" :options="props.bookings.map((r) => ({ value: r, label: r }))" :error="form.errors.booking_reference" />
        <FormTextarea v-model="form.message" label="Message" rows="6" required :error="form.errors.message" />
        <Btn type="submit" :loading="form.processing">Send message</Btn>
    </form>
</template>
