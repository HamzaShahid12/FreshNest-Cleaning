<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import BookingDetails from '@/Components/Booking/BookingDetails.vue';
import Btn from '@/Components/Btn.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import FormTextarea from '@/Components/FormTextarea.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Seo from '@/Components/Seo.vue';

const props = defineProps({ job: Object, nextStatuses: Array });
const form = useForm({ status: '' });
const confirming = ref(null);
const noteForm = useForm({ note: '' });
const addNote = () => noteForm.post(`/staff/jobs/${props.job.reference}/notes`, { preserveScroll: true, onSuccess: () => noteForm.reset() });
const labels = { in_progress: 'Start job', completed: 'Mark completed' };
const go = () => {
    form.status = confirming.value;
    form.post(`/staff/jobs/${props.job.reference}/status`, { preserveScroll: true, onFinish: () => { confirming.value = null; } });
};
</script>

<template>
    <Seo :title="`Job ${job.reference}`" noindex />
    <Link href="/staff" class="text-sm font-medium text-brand hover:underline">&larr; My jobs</Link>
    <PageHeader class="mt-3" :title="job.service" :subtitle="`${job.date} · ${job.time} – ${job.ends_time}`">
        <Btn v-for="s in nextStatuses" :key="s" :variant="s === 'completed' ? 'primary' : 'dark'" @click="confirming = s">{{ labels[s] || s }}</Btn>
    </PageHeader>
    <BookingDetails :booking="job" />
    <section class="card card-pad mt-5">
        <h2 class="font-display text-lg font-semibold text-ink">Job notes</h2>
        <p v-if="job.work_notes" class="mt-2 whitespace-pre-line text-sm text-body">{{ job.work_notes }}</p>
        <form class="mt-3 space-y-3" @submit.prevent="addNote">
            <FormTextarea v-model="noteForm.note" label="Add a note" rows="2" :error="noteForm.errors.note" />
            <Btn type="submit" size="sm" :loading="noteForm.processing" :disabled="!noteForm.note">Add note</Btn>
        </form>
    </section>
    <ConfirmationModal :show="!!confirming" :title="labels[confirming] + '?'" message="This updates the job status for the customer and office." :confirm-label="labels[confirming]" :processing="form.processing" @close="confirming = null" @confirm="go" />
</template>
