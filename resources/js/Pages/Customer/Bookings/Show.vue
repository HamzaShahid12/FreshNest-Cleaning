<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import BookingDetails from '@/Components/Booking/BookingDetails.vue';
import Btn from '@/Components/Btn.vue';
import DatePicker from '@/Components/DatePicker.vue';
import FormModal from '@/Components/FormModal.vue';
import FormTextarea from '@/Components/FormTextarea.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ReviewStars from '@/Components/ReviewStars.vue';
import Seo from '@/Components/Seo.vue';
import api, { errorMessage } from '@/Services/api';

const props = defineProps({ booking: Object, cancellationHours: Number, minDate: String });
const base = `/account/bookings/${props.booking.reference}`;

/* reschedule */
const showReschedule = ref(false);
const rs = useForm({ date: '', time: '' });
const slots = ref([]);
const slotsLoading = ref(false);
const slotsError = ref('');
async function loadSlots() {
    rs.time = '';
    if (!rs.date) return;
    slotsLoading.value = true; slotsError.value = '';
    try { slots.value = (await api.get(`${base}/slots`, { params: { date: rs.date } })).data.slots; }
    catch (e) { slots.value = []; slotsError.value = errorMessage(e); }
    finally { slotsLoading.value = false; }
}
const doReschedule = () => rs.post(`${base}/reschedule`, { preserveScroll: true, onSuccess: () => { showReschedule.value = false; rs.reset(); slots.value = []; } });

/* cancel */
const showCancel = ref(false);
const cancel = useForm({ reason: '' });
const doCancel = () => cancel.post(`${base}/cancel`, { preserveScroll: true, onSuccess: () => { showCancel.value = false; } });

/* review */
const review = useForm({ rating: 0, comment: '' });
const doReview = () => review.post(`${base}/review`, { preserveScroll: true });

const active = ['pending', 'confirmed', 'assigned'].includes(props.booking.status);
</script>

<template>
    <Seo :title="`Booking ${booking.reference}`" noindex />
    <Link href="/account/bookings" class="text-sm font-medium text-brand hover:underline">&larr; All bookings</Link>
    <PageHeader class="mt-3" :title="booking.service" :subtitle="`Reference ${booking.reference}`">
        <template v-if="booking.can_modify">
            <Btn variant="outline" @click="showReschedule = true">Reschedule</Btn>
            <Btn variant="danger" @click="showCancel = true">Cancel booking</Btn>
        </template>
    </PageHeader>

    <p v-if="active && !booking.can_modify" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        Changes can be made online up to {{ cancellationHours }} hours before your appointment. For anything inside that window please <Link href="/account/support" class="font-semibold underline">contact us</Link>.
    </p>

    <BookingDetails :booking="booking" />

    <section v-if="booking.can_review" class="card card-pad mt-5">
        <h2 class="font-display text-lg font-semibold text-ink">How was your cleaning?</h2>
        <form class="mt-3 space-y-3" @submit.prevent="doReview">
            <ReviewStars v-model="review.rating" interactive size="h-8 w-8" />
            <p v-if="review.errors.rating" class="field-error">{{ review.errors.rating }}</p>
            <FormTextarea v-model="review.comment" label="Comments (optional)" rows="3" :error="review.errors.comment" />
            <Btn type="submit" :loading="review.processing" :disabled="!review.rating">Submit review</Btn>
        </form>
    </section>
    <section v-else-if="booking.review" class="card card-pad mt-5">
        <h2 class="font-display text-lg font-semibold text-ink">Your review</h2>
        <ReviewStars :rating="booking.review.rating" class="mt-2" />
        <p v-if="booking.review.comment" class="mt-2 text-sm text-body">{{ booking.review.comment }}</p>
        <p v-if="!booking.review.is_approved" class="mt-2 text-xs text-gray-500">Awaiting moderation. It will appear on our website once approved.</p>
    </section>

    <FormModal :show="showReschedule" title="Reschedule booking" submit-label="Confirm new time" :processing="rs.processing" @close="showReschedule = false" @submit="doReschedule">
        <DatePicker v-model="rs.date" label="New date" :min="minDate" :error="rs.errors.date" required @update:model-value="loadSlots" />
        <p v-if="slotsLoading" class="flex items-center gap-2 text-sm text-gray-500"><LoadingSpinner /> Checking availability…</p>
        <p v-else-if="slotsError" class="text-sm text-red-600">{{ slotsError }}</p>
        <div v-else-if="rs.date" class="grid grid-cols-3 gap-2">
            <button v-for="s in slots" :key="s.time" type="button" :disabled="!s.available" :aria-pressed="rs.time === s.time"
                :class="['rounded-lg border px-2 py-2 text-sm font-medium', rs.time === s.time ? 'border-brand bg-brand text-white' : s.available ? 'border-gray-200 hover:border-brand' : 'cursor-not-allowed bg-gray-50 text-gray-300 line-through']"
                @click="rs.time = s.time">{{ s.label }}</button>
        </div>
        <p v-if="rs.errors.time" class="field-error">{{ rs.errors.time }}</p>
    </FormModal>

    <FormModal :show="showCancel" title="Cancel this booking?" submit-label="Yes, cancel booking" :processing="cancel.processing" @close="showCancel = false" @submit="doCancel">
        <p class="text-sm text-body">This cannot be undone. You can book again at any time.</p>
        <FormTextarea v-model="cancel.reason" label="Reason (optional)" rows="2" :error="cancel.errors.reason" />
    </FormModal>
</template>
