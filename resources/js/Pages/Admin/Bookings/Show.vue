<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BookingDetails from '@/Components/Booking/BookingDetails.vue';
import Btn from '@/Components/Btn.vue';
import DatePicker from '@/Components/DatePicker.vue';
import FormInput from '@/Components/FormInput.vue';
import FormModal from '@/Components/FormModal.vue';
import FormSelect from '@/Components/FormSelect.vue';
import FormTextarea from '@/Components/FormTextarea.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Seo from '@/Components/Seo.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import api, { errorMessage } from '@/Services/api';
import { money, titleCase } from '@/Composables/useFormat';

const props = defineProps({ booking: Object, staff: Array, minDate: String, payment: Object });
const base = `/admin/bookings/${props.booking.reference}`;
const opts = { preserveScroll: true };

/* status */
const statusForm = useForm({ status: '', note: '' });
const statusModal = ref(false);
const openStatus = (s) => { statusForm.status = s; statusForm.note = ''; statusModal.value = true; };
const saveStatus = () => statusForm.post(`${base}/status`, { ...opts, onSuccess: () => { statusModal.value = false; } });

/* assign */
const assign = useForm({ employee_id: props.booking.employee_id || '' });
const staffOptions = computed(() => props.staff.map((s) => ({ value: s.id, label: s.name + (s.free ? '' : ' (busy at this time)') })));

/* reschedule */
const rsModal = ref(false);
const rs = useForm({ date: '', time: '' });
const slots = ref([]);
const slotsLoading = ref(false);
async function loadSlots() {
    rs.time = '';
    if (!rs.date) return;
    slotsLoading.value = true;
    try { slots.value = (await api.get(`${base}/slots`, { params: { date: rs.date } })).data.slots; }
    catch (e) { slots.value = []; rs.setError('date', errorMessage(e)); }
    finally { slotsLoading.value = false; }
}
const saveReschedule = () => rs.post(`${base}/reschedule`, { ...opts, onSuccess: () => { rsModal.value = false; rs.reset(); } });

/* notes, payment, quote, refund */
const notes = useForm({ work_notes: props.booking.work_notes || '' });
const pay = useForm({ reference: '' });
const quote = useForm({ amount: '' });
const refund = useForm({ amount: '', reason: '' });
const refundModal = ref(false);
const saveRefund = () => refund.post(`${base}/refund`, { ...opts, onSuccess: () => { refundModal.value = false; refund.reset(); } });
const canRefund = computed(() => props.payment && ['paid', 'partially_refunded'].includes(props.payment.status));
</script>

<template>
    <Seo :title="`Booking ${booking.reference}`" noindex />
    <Link href="/admin/bookings" class="text-sm font-medium text-brand hover:underline">&larr; All bookings</Link>
    <PageHeader class="mt-3" :title="`${booking.reference} · ${booking.service}`" :subtitle="`${booking.date} at ${booking.time}`">
        <a :href="`${base}/print`" target="_blank" rel="noopener" class="btn btn-outline">Print</a>
        <Btn v-for="s in booking.transitions" :key="s" :variant="['cancelled', 'rejected'].includes(s) ? 'danger' : 'primary'" @click="openStatus(s)">{{ titleCase(s) }}</Btn>
        <Btn v-if="['pending', 'confirmed', 'assigned'].includes(booking.status)" variant="outline" @click="rsModal = true">Reschedule</Btn>
    </PageHeader>

    <BookingDetails :booking="booking" />

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <form v-if="['confirmed', 'assigned'].includes(booking.status)" class="card card-pad space-y-3" @submit.prevent="assign.post(`${base}/assign`, opts)">
            <h2 class="font-display text-lg font-semibold text-ink">Assign cleaner</h2>
            <FormSelect v-model="assign.employee_id" label="Cleaner" placeholder="Select…" :options="staffOptions" :error="assign.errors.employee_id" />
            <Btn type="submit" :loading="assign.processing" :disabled="!assign.employee_id">Assign</Btn>
        </form>

        <section v-if="booking.requires_quote && !['cancelled', 'rejected', 'completed'].includes(booking.status)" class="card card-pad">
            <h2 class="font-display text-lg font-semibold text-ink">Custom quotation</h2>
            <form class="mt-3 space-y-3" @submit.prevent="quote.post(`${base}/quote`, opts)">
                <FormInput v-model="quote.amount" label="Quoted total ($)" type="number" step="0.01" :error="quote.errors.amount" />
                <Btn type="submit" :loading="quote.processing" :disabled="!quote.amount">Save quote</Btn>
            </form>
        </section>

        <section class="card card-pad">
            <h2 class="font-display text-lg font-semibold text-ink">Payment</h2>
            <div v-if="payment" class="mt-3 flex items-center justify-between text-sm">
                <span class="text-body">{{ titleCase(payment.method) }} · {{ money(payment.amount) }}<span v-if="payment.refund_amount > 0"> (refunded {{ money(payment.refund_amount) }})</span></span>
                <StatusBadge :status="payment.status" />
            </div>
            <p v-else class="mt-3 text-sm text-gray-500">No payment record yet.</p>
            <form v-if="payment && payment.status === 'pending'" class="mt-3 space-y-3 border-t border-gray-100 pt-3" @submit.prevent="pay.post(`${base}/payment`, opts)">
                <FormInput v-model="pay.reference" label="Receipt / reference (optional)" hint="Marks a cash or offline payment as received." :error="pay.errors.reference" />
                <Btn type="submit" :loading="pay.processing">Record payment received</Btn>
            </form>
            <Btn v-if="canRefund" class="mt-3" variant="outline" @click="refundModal = true">Refund…</Btn>
        </section>

        <form class="card card-pad space-y-3" @submit.prevent="notes.post(`${base}/notes`, opts)">
            <h2 class="font-display text-lg font-semibold text-ink">Internal notes</h2>
            <FormTextarea v-model="notes.work_notes" label="Visible to staff only" rows="4" :error="notes.errors.work_notes" />
            <Btn type="submit" :loading="notes.processing">Save notes</Btn>
        </form>
    </div>

    <FormModal :show="statusModal" :title="`Change status to ${titleCase(statusForm.status)}`" submit-label="Update status" :processing="statusForm.processing" @close="statusModal = false" @submit="saveStatus">
        <FormInput v-model="statusForm.note" label="Note (optional, saved in the timeline)" :error="statusForm.errors.note" />
        <p v-if="statusForm.errors.status" class="field-error">{{ statusForm.errors.status }}</p>
    </FormModal>

    <FormModal :show="rsModal" title="Reschedule booking" submit-label="Save new time" :processing="rs.processing" @close="rsModal = false" @submit="saveReschedule">
        <DatePicker v-model="rs.date" label="New date" :min="minDate" :error="rs.errors.date" @update:model-value="loadSlots" />
        <p v-if="slotsLoading" class="flex items-center gap-2 text-sm text-gray-500"><LoadingSpinner /> Checking availability…</p>
        <div v-else-if="rs.date" class="grid grid-cols-3 gap-2">
            <button v-for="s in slots" :key="s.time" type="button" :disabled="!s.available" :aria-pressed="rs.time === s.time"
                :class="['rounded-lg border px-2 py-2 text-sm font-medium', rs.time === s.time ? 'border-brand bg-brand text-white' : s.available ? 'border-gray-200 hover:border-brand' : 'cursor-not-allowed bg-gray-50 text-gray-300 line-through']"
                @click="rs.time = s.time">{{ s.label }}</button>
        </div>
        <p v-if="rs.errors.time" class="field-error">{{ rs.errors.time }}</p>
    </FormModal>

    <FormModal :show="refundModal" title="Refund payment" submit-label="Issue refund" :processing="refund.processing" @close="refundModal = false" @submit="saveRefund">
        <p class="text-sm text-body">Stripe refunds are sent to Stripe; offline refunds are recorded only. Paid: {{ payment ? money(payment.amount) : '' }}.</p>
        <FormInput v-model="refund.amount" label="Amount ($)" type="number" step="0.01" :error="refund.errors.amount" />
        <FormInput v-model="refund.reason" label="Reason" :error="refund.errors.reason" />
    </FormModal>
</template>
