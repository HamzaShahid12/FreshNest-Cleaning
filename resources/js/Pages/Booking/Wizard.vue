<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import BookingProgress from '@/Components/BookingProgress.vue';
import Btn from '@/Components/Btn.vue';
import DatePicker from '@/Components/DatePicker.vue';
import FormCheckbox from '@/Components/FormCheckbox.vue';
import FormInput from '@/Components/FormInput.vue';
import FormSelect from '@/Components/FormSelect.vue';
import FormTextarea from '@/Components/FormTextarea.vue';
import Icon from '@/Components/Icon.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import LocationPicker from '@/Components/LocationPicker.vue';
import PriceSummary from '@/Components/PriceSummary.vue';
import Seo from '@/Components/Seo.vue';
import SmartImage from '@/Components/SmartImage.vue';
import { duration, money } from '@/Composables/useFormat';
import api, { errorMessage, validationErrors } from '@/Services/api';
import { useToastStore } from '@/Stores/toast';

const props = defineProps({
    services: Array, extras: Array, propertyTypes: Array, frequencies: Array, paymentMethods: Array,
    stripeEnabled: Boolean, preselectedServiceId: [Number, null], minDate: String, maxDate: String,
    idempotencyKey: String, savedAddresses: { type: Array, default: () => [] }, prefill: Object, cancellationHours: Number,
});

const toast = useToastStore();
const user = computed(() => usePage().props.auth?.user);

const steps = [
    { key: 'service', label: 'Service' }, { key: 'property', label: 'Property' }, { key: 'details', label: 'Details' },
    { key: 'frequency', label: 'Frequency' }, { key: 'extras', label: 'Extras' }, { key: 'address', label: 'Address' },
    { key: 'time', label: 'Date & time' }, { key: 'you', label: 'Your details' }, { key: 'summary', label: 'Summary' },
    { key: 'payment', label: 'Payment' },
];
const fieldStep = {
    service_id: 1, property_type: 2, bedrooms: 3, bathrooms: 3, sqft: 3, frequency: 4, extras: 5,
    street: 6, unit: 6, city: 6, state: 6, zip: 6, country: 6, country_code: 6, latitude: 6, longitude: 6, instructions: 6, date: 7, time: 7,
    contact_name: 8, contact_email: 8, contact_phone: 8, notes: 8, password: 8, coupon_code: 9, accept_terms: 9, payment_method: 10,
};

const step = ref(1);
const maxReached = ref(1);
const errors = reactive({});
const submitting = ref(false);

const f = reactive({
    service_id: props.preselectedServiceId || null,
    property_type: 'apartment', bedrooms: 1, bathrooms: 1, sqft: '',
    frequency: 'one_time', extras: [],
    street: '', unit: '', city: '', state: '', zip: '', country: 'United States', country_code: 'US', latitude: null, longitude: null, instructions: '',
    date: '', time: '',
    contact_name: props.prefill?.name || '', contact_email: props.prefill?.email || '', contact_phone: props.prefill?.phone || '', notes: '', password: '',
    coupon_code: '', accept_terms: false,
    payment_method: props.paymentMethods[0]?.key || 'pay_later',
});
if (props.preselectedServiceId) { step.value = 2; maxReached.value = 2; }

const service = computed(() => props.services.find((s) => s.id === f.service_id));
const sizeBased = computed(() => service.value?.pricing_method === 'size_based');

/* ------------------------------ live server quote ------------------------------ */
const quote = ref(null);
const quoteLoading = ref(false);
const quoteError = ref('');
const appliedCoupon = ref('');
let quoteCtl = null;
let quoteTimer = null;

const quotePayload = () => ({
    service_id: f.service_id, property_type: f.property_type, bedrooms: Number(f.bedrooms) || 1, bathrooms: Number(f.bathrooms) || 1,
    sqft: f.sqft === '' ? null : Number(f.sqft), frequency: f.frequency, extras: f.extras, coupon_code: appliedCoupon.value || null,
});

async function fetchQuote() {
    if (!f.service_id) { quote.value = null; return; }
    quoteCtl?.abort();
    quoteCtl = new AbortController();
    quoteLoading.value = true;
    quoteError.value = '';
    try {
        const { data } = await api.post('/api/booking/quote', quotePayload(), { signal: quoteCtl.signal });
        quote.value = data;
        if (data.coupon_error) { errors.coupon_code = data.coupon_error; appliedCoupon.value = ''; }
        else if (appliedCoupon.value) delete errors.coupon_code;
    } catch (e) {
        if (e.code === 'ERR_CANCELED') return;
        quoteError.value = Object.values(validationErrors(e))[0] || errorMessage(e);
    } finally {
        quoteLoading.value = false;
    }
}
const scheduleQuote = () => { clearTimeout(quoteTimer); quoteTimer = setTimeout(fetchQuote, 250); };
watch(() => [f.service_id, f.property_type, f.bedrooms, f.bathrooms, f.sqft, f.frequency, [...f.extras], appliedCoupon.value], scheduleQuote, { immediate: true });
onBeforeUnmount(() => { quoteCtl?.abort(); clearTimeout(quoteTimer); });

/* ----------------------------------- slots ------------------------------------ */
const slots = ref([]);
const slotsLoading = ref(false);
const slotsError = ref('');
const slotsLoaded = ref(false);
let slotsCtl = null;

async function loadSlots() {
    if (!f.date || !f.service_id) return;
    slotsCtl?.abort();
    slotsCtl = new AbortController();
    slotsLoading.value = true;
    slotsError.value = '';
    try {
        const { data } = await api.get('/api/booking/availability', {
            params: { ...quotePayload(), coupon_code: undefined, date: f.date }, signal: slotsCtl.signal,
        });
        slots.value = data.slots;
        slotsLoaded.value = true;
        if (f.time && !data.slots.some((s) => s.time === f.time && s.available)) f.time = '';
    } catch (e) {
        if (e.code === 'ERR_CANCELED') return;
        slots.value = [];
        slotsError.value = Object.values(validationErrors(e))[0] || errorMessage(e, 'Could not load times. Please try again.');
    } finally {
        slotsLoading.value = false;
    }
}
watch(() => f.date, () => { f.time = ''; slotsLoaded.value = false; loadSlots(); });
watch(() => [f.service_id, f.property_type, f.bedrooms, f.bathrooms, f.sqft, f.frequency, [...f.extras]], () => { if (f.date) loadSlots(); });

const anyAvailable = computed(() => slots.value.some((s) => s.available));
const slotLabel = computed(() => slots.value.find((s) => s.time === f.time)?.label || f.time);
const prettyDate = computed(() => {
    if (!f.date) return '';
    const [y, m, d] = f.date.split('-').map(Number);

    return new Date(y, m - 1, d).toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
});
const schedule = computed(() => (f.date && f.time ? `${prettyDate.value} at ${slotLabel.value}` : ''));
const addressLine = computed(() => (f.street ? `${f.street}${f.unit ? ', ' + f.unit : ''}, ${f.city}${f.state ? ', ' + f.state : ''}${f.zip ? ' ' + f.zip : ''}, ${f.country}` : ''));

/* ------------------------------- validation ------------------------------- */
const clear = () => Object.keys(errors).forEach((k) => delete errors[k]);

function validate(n) {
    const e = {};
    const need = (k, msg) => { if (!String(f[k] ?? '').trim()) e[k] = msg; };
    if (n === 1 && !f.service_id) e.service_id = 'Please choose a service.';
    if (n === 2 && !f.property_type) e.property_type = 'Please choose a property type.';
    if (n === 3) {
        if (!(Number(f.bedrooms) >= 1 && Number(f.bedrooms) <= 12)) e.bedrooms = 'Enter 1 to 12 bedrooms.';
        if (!(Number(f.bathrooms) >= 1 && Number(f.bathrooms) <= 12)) e.bathrooms = 'Enter 1 to 12 bathrooms.';
        if (f.sqft !== '' && !(Number(f.sqft) >= 0 && Number(f.sqft) <= 30000)) e.sqft = 'Enter a size up to 30,000 sq ft.';
    }
    if (n === 6) {
        need('street', 'Street address is required.'); need('city', 'City is required.'); need('country', 'Country is required.');
        if (f.zip.trim() && !/^[A-Za-z0-9][A-Za-z0-9 -]{1,19}$/.test(f.zip.trim())) e.zip = 'Enter a valid postal code.';
    }
    if (n === 7) { if (!f.date) e.date = 'Pick a date.'; else if (!f.time) e.time = 'Pick an available time.'; }
    if (n === 8) {
        need('contact_name', 'Your name is required.'); need('contact_phone', 'A phone number is required.');
        if (!/^\S+@\S+\.\S+$/.test(f.contact_email.trim())) e.contact_email = 'Enter a valid email address.';
        if (!user.value && f.password.length < 8) e.password = 'Choose a password with at least 8 characters.';
    }
    if (n === 9 && !f.accept_terms) e.accept_terms = 'Please accept the terms and cancellation policy.';
    if (n === 10 && !f.payment_method) e.payment_method = 'Choose how you would like to pay.';
    return e;
}

function go(n) {
    if (n < step.value) { step.value = n; return; }
    for (let i = step.value; i < n; i++) {
        const e = validate(i);
        if (Object.keys(e).length) { clear(); Object.assign(errors, e); step.value = i; return; }
    }
    clear();
    step.value = n;
    maxReached.value = Math.max(maxReached.value, n);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
const next = () => {
    const e = validate(step.value);
    clear();
    if (Object.keys(e).length) { Object.assign(errors, e); return; }
    step.value += 1;
    maxReached.value = Math.max(maxReached.value, step.value);
    window.scrollTo({ top: 0, behavior: 'smooth' });
};
const back = () => { clear(); step.value = Math.max(1, step.value - 1); };

// If the customer hand-edits the country, the code that came from the map/saved address no longer applies.
let codedCountry = 'United States';
watch(() => f.country, (v) => { if (v !== codedCountry) f.country_code = ''; });
const onMapSelect = (p) => {
    if (p.street !== undefined) {
        codedCountry = p.country || f.country;
        Object.assign(f, { street: p.street || f.street, city: p.city, state: p.state, zip: p.zip, country: codedCountry, country_code: p.country_code || '' });
    }
    f.latitude = p.latitude; f.longitude = p.longitude;
};
watch(step, (n) => { if (n === 7 && f.date) loadSlots(); });

const pickSaved = (id) => {
    const a = props.savedAddresses.find((x) => String(x.id) === String(id));
    if (!a) return;
    codedCountry = a.country || 'United States';
    Object.assign(f, {
        street: a.street, unit: a.unit || '', city: a.city, state: a.state || '', zip: a.zip || '', country: a.country || 'United States', country_code: a.country_code || (a.country ? '' : 'US'),
        latitude: a.latitude !== null && a.latitude !== undefined ? Number(a.latitude) : null, longitude: a.longitude !== null && a.longitude !== undefined ? Number(a.longitude) : null,
        instructions: a.instructions || f.instructions,
    });
};
const toggleExtra = (id) => { f.extras = f.extras.includes(id) ? f.extras.filter((x) => x !== id) : [...f.extras, id]; };
const applyCoupon = () => {
    delete errors.coupon_code;
    appliedCoupon.value = f.coupon_code.trim();
    if (!appliedCoupon.value) fetchQuote();
};

/* -------------------------------- submit -------------------------------- */
async function submit() {
    for (let i = 1; i <= 10; i++) {
        const e = validate(i);
        if (Object.keys(e).length) { clear(); Object.assign(errors, e); step.value = i; return; }
    }
    clear();
    submitting.value = true;
    try {
        const { data } = await api.post('/api/booking', {
            ...f, sqft: f.sqft === '' ? null : Number(f.sqft),
            coupon_code: appliedCoupon.value || null, idempotency_key: props.idempotencyKey,
            password: user.value ? undefined : f.password,
        });
        if (data.payment_notice) toast.error(data.payment_notice);
        if (data.checkout_url) { window.location.href = data.checkout_url; return; }
        router.visit(data.redirect);
    } catch (e) {
        const errs = validationErrors(e);
        if (Object.keys(errs).length) {
            Object.assign(errors, errs);
            const first = Object.keys(errs).map((k) => fieldStep[k.split('.')[0]] || 1).sort((a, b) => a - b)[0];
            step.value = first;
            toast.error(Object.values(errs)[0]);
            if (errs.time || errs.date) { slotsLoaded.value = false; loadSlots(); }
        } else {
            toast.error(errorMessage(e));
        }
        submitting.value = false;
    }
}

const showMobileSummary = ref(false);
const stepTitles = ['Which service do you need?', 'What type of property?', 'Tell us about the property', 'How often should we clean?', 'Anything extra?', 'Where should we come?', 'Pick a date and time', 'Your contact details', 'Review your booking', 'How would you like to pay?'];
</script>

<template>
    <Seo title="Book a Cleaning" description="Book a home or office cleaning online in a few steps and see your exact price before you confirm." />

    <section class="bg-brand-light pb-28 pt-8 lg:pb-12">
        <div class="container-x">
            <h1 class="font-display text-2xl font-semibold text-ink sm:text-3xl">Book a cleaning</h1>
            <div class="mt-5"><BookingProgress :steps="steps" :current="step" :max-reached="maxReached" @go="go" /></div>

            <div class="mt-8 grid gap-8 lg:grid-cols-3">
                <form class="card card-pad lg:col-span-2" novalidate @submit.prevent="step === 10 ? submit() : next()">
                    <h2 class="font-display text-xl font-semibold text-ink">{{ stepTitles[step - 1] }}</h2>

                    <!-- 1 Service -->
                    <div v-if="step === 1" class="mt-5">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <button
                                v-for="s in services" :key="s.id" type="button" :aria-pressed="f.service_id === s.id"
                                :class="['flex gap-3 rounded-xl border p-3 text-left transition', f.service_id === s.id ? 'border-brand bg-brand-soft ring-2 ring-brand/30' : 'border-gray-200 hover:border-brand/50']"
                                @click="f.service_id = s.id"
                            >
                                <SmartImage :src="s.image_url" :alt="s.name" class="h-16 w-16 shrink-0 rounded-lg" />
                                <span class="min-w-0">
                                    <span class="block font-semibold text-ink">{{ s.name }}</span>
                                    <span class="block text-xs text-gray-500">{{ s.pricing_method === 'quote' ? 'Custom quote' : (s.pricing_method === 'size_based' ? 'From ' : '') + money(s.base_price) }} · {{ duration(s.duration_minutes) }}</span>
                                    <span v-if="s.short_description" class="mt-1 line-clamp-2 block text-xs text-body">{{ s.short_description }}</span>
                                </span>
                            </button>
                        </div>
                        <p v-if="errors.service_id" class="field-error" role="alert">{{ errors.service_id }}</p>
                    </div>

                    <!-- 2 Property type -->
                    <div v-if="step === 2" class="mt-5">
                        <div class="grid gap-3 sm:grid-cols-3">
                            <button
                                v-for="p in propertyTypes" :key="p.key" type="button" :aria-pressed="f.property_type === p.key"
                                :class="['flex flex-col items-center gap-2 rounded-xl border p-5 transition', f.property_type === p.key ? 'border-brand bg-brand-soft ring-2 ring-brand/30' : 'border-gray-200 hover:border-brand/50']"
                                @click="f.property_type = p.key"
                            >
                                <Icon :name="p.key === 'office' ? 'building' : 'home'" class="h-8 w-8 text-brand" />
                                <span class="font-semibold text-ink">{{ p.label }}</span>
                            </button>
                        </div>
                        <p v-if="errors.property_type" class="field-error" role="alert">{{ errors.property_type }}</p>
                    </div>

                    <!-- 3 Details -->
                    <div v-if="step === 3" class="mt-5 space-y-4">
                        <p v-if="!sizeBased" class="rounded-lg bg-brand-soft px-3 py-2 text-sm text-brand-dark">The price of this service does not depend on the property size, but these details help our team prepare.</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <FormInput v-model="f.bedrooms" label="Bedrooms" type="number" min="1" max="12" inputmode="numeric" :error="errors.bedrooms" required />
                            <FormInput v-model="f.bathrooms" label="Bathrooms" type="number" min="1" max="12" inputmode="numeric" :error="errors.bathrooms" required />
                        </div>
                        <FormInput v-model="f.sqft" label="Approximate size (sq ft)" type="number" min="0" max="30000" inputmode="numeric" placeholder="e.g. 1400" hint="Optional. Larger homes take longer and may cost more." :error="errors.sqft" />
                    </div>

                    <!-- 4 Frequency -->
                    <div v-if="step === 4" class="mt-5 grid gap-3 sm:grid-cols-2">
                        <button
                            v-for="q in frequencies" :key="q.key" type="button" :aria-pressed="f.frequency === q.key"
                            :class="['flex items-center justify-between rounded-xl border p-4 text-left transition', f.frequency === q.key ? 'border-brand bg-brand-soft ring-2 ring-brand/30' : 'border-gray-200 hover:border-brand/50']"
                            @click="f.frequency = q.key"
                        >
                            <span class="font-semibold text-ink">{{ q.label }}</span>
                            <span v-if="q.discount" class="badge bg-green-100 text-green-800">Save {{ q.discount }}%</span>
                        </button>
                    </div>

                    <!-- 5 Extras -->
                    <div v-if="step === 5" class="mt-5">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <button
                                v-for="x in extras" :key="x.id" type="button" :aria-pressed="f.extras.includes(x.id)"
                                :class="['flex items-start justify-between gap-3 rounded-xl border p-4 text-left transition', f.extras.includes(x.id) ? 'border-brand bg-brand-soft ring-2 ring-brand/30' : 'border-gray-200 hover:border-brand/50']"
                                @click="toggleExtra(x.id)"
                            >
                                <span><span class="block font-semibold text-ink">{{ x.name }}</span><span v-if="x.description" class="text-xs text-gray-500">{{ x.description }}</span></span>
                                <span class="whitespace-nowrap font-semibold text-ink">+{{ money(x.price) }}</span>
                            </button>
                        </div>
                        <p v-if="!extras.length" class="text-sm text-gray-500">No add-ons available right now. You can continue.</p>
                    </div>

                    <!-- 6 Address -->
                    <div v-if="step === 6" class="mt-5 space-y-4">
                        <FormSelect
                            v-if="savedAddresses.length" label="Use a saved address" placeholder="Choose…" :model-value="''"
                            :options="savedAddresses.map((a) => ({ value: a.id, label: `${a.label ? a.label + ': ' : ''}${a.street}, ${a.city}` }))" @update:model-value="pickSaved"
                        />
                        <LocationPicker :latitude="f.latitude" :longitude="f.longitude" @select="onMapSelect" />
                        <FormInput v-model="f.street" label="Street address" autocomplete="address-line1" required :error="errors.street" />
                        <FormInput v-model="f.unit" label="Apartment / unit (optional)" autocomplete="address-line2" :error="errors.unit" />
                        <div class="grid gap-4 sm:grid-cols-6">
                            <div class="sm:col-span-3"><FormInput v-model="f.city" label="City" autocomplete="address-level2" required :error="errors.city" /></div>
                            <div class="sm:col-span-3"><FormInput v-model="f.state" label="State / province / region (optional)" autocomplete="address-level1" :error="errors.state" /></div>
                            <div class="sm:col-span-3"><FormInput v-model="f.zip" label="ZIP / postal code (optional)" autocomplete="postal-code" :error="errors.zip" /></div>
                            <div class="sm:col-span-3"><FormInput v-model="f.country" label="Country" autocomplete="country-name" required :error="errors.country" /></div>
                        </div>
                        <FormTextarea v-model="f.instructions" label="Special instructions (optional)" rows="3" placeholder="Entry code, pets, parking, areas to focus on…" :error="errors.instructions" />
                    </div>

                    <!-- 7 Date & time -->
                    <div v-if="step === 7" class="mt-5 space-y-5">
                        <DatePicker v-model="f.date" label="Preferred date" :min="minDate" :max="maxDate" required :error="errors.date" hint="Times shown reflect real availability for your job length." />
                        <div v-if="f.date" aria-live="polite">
                            <p class="label">Available start times<span v-if="quote" class="font-normal text-gray-500"> · about {{ duration(quote.duration_minutes) }}</span></p>
                            <p v-if="slotsLoading" class="flex items-center gap-2 text-sm text-gray-500"><LoadingSpinner /> Checking availability…</p>
                            <p v-else-if="slotsError" class="text-sm text-red-600">{{ slotsError }}</p>
                            <template v-else-if="slotsLoaded">
                                <div v-if="anyAvailable" class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4">
                                    <button
                                        v-for="s in slots" :key="s.time" type="button" :disabled="!s.available" :aria-pressed="f.time === s.time"
                                        :class="['rounded-lg border px-3 py-2.5 text-sm font-medium transition', f.time === s.time ? 'border-brand bg-brand text-white' : s.available ? 'border-gray-200 text-ink hover:border-brand' : 'cursor-not-allowed border-gray-100 bg-gray-50 text-gray-300 line-through']"
                                        @click="f.time = s.time"
                                    >{{ s.label }}</button>
                                </div>
                                <p v-else class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900">No times are free on this day. Please try another date.</p>
                            </template>
                            <p v-if="errors.time" class="field-error" role="alert">{{ errors.time }}</p>
                        </div>
                    </div>

                    <!-- 8 Customer details -->
                    <div v-if="step === 8" class="mt-5 space-y-4">
                        <FormInput v-model="f.contact_name" label="Full name" autocomplete="name" required :error="errors.contact_name" />
                        <div class="grid gap-4 sm:grid-cols-2">
                            <FormInput v-model="f.contact_email" label="Email" type="email" autocomplete="email" required :disabled="!!user" :error="errors.contact_email" />
                            <FormInput v-model="f.contact_phone" label="Phone" type="tel" autocomplete="tel" required :error="errors.contact_phone" />
                        </div>
                        <FormTextarea v-model="f.notes" label="Notes for our team (optional)" rows="3" :error="errors.notes" />
                        <div v-if="!user" class="rounded-xl bg-brand-soft p-4">
                            <p class="text-sm text-brand-dark">We will create your FreshNest account so you can manage this booking. Already have one? <Link href="/login" class="font-semibold underline">Sign in first</Link>.</p>
                            <div class="mt-3"><FormInput v-model="f.password" label="Choose a password" type="password" autocomplete="new-password" required hint="At least 8 characters." :error="errors.password" /></div>
                        </div>
                    </div>

                    <!-- 9 Summary -->
                    <div v-if="step === 9" class="mt-5 space-y-5">
                        <PriceSummary :quote="quote" :loading="quoteLoading" :error="quoteError" :service-name="service?.name" :schedule="schedule" :address="addressLine" compact />
                        <div class="flex gap-2">
                            <div class="flex-1"><FormInput v-model="f.coupon_code" label="Promo code" placeholder="Enter code" :error="errors.coupon_code" @keydown.enter.prevent="applyCoupon" /></div>
                            <Btn variant="outline" class="mt-[1.65rem]" @click="applyCoupon">Apply</Btn>
                        </div>
                        <p class="text-xs text-gray-500">Your final price is confirmed by our server when you submit. Prices may change if you edit your selections.</p>
                        <FormCheckbox v-model="f.accept_terms" :error="errors.accept_terms">
                            I agree to the <Link href="/legal/terms" target="_blank" class="font-semibold text-brand underline">Terms</Link> and the
                            <Link href="/legal/cancellation" target="_blank" class="font-semibold text-brand underline">Cancellation Policy</Link> (free changes up to {{ cancellationHours }} hours before).
                        </FormCheckbox>
                    </div>

                    <!-- 10 Payment -->
                    <div v-if="step === 10" class="mt-5 space-y-4">
                        <p v-if="quote?.requires_quote" class="rounded-lg bg-brand-soft px-3 py-2 text-sm text-brand-dark">This service is quoted individually. No payment is taken now; we will confirm your quote first.</p>
                        <div class="space-y-3">
                            <label
                                v-for="m in paymentMethods" :key="m.key"
                                :class="['flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition', f.payment_method === m.key ? 'border-brand bg-brand-soft ring-2 ring-brand/30' : 'border-gray-200 hover:border-brand/50']"
                            >
                                <input v-model="f.payment_method" type="radio" name="payment" :value="m.key" class="h-4 w-4 text-brand focus:ring-brand">
                                <Icon :name="m.key === 'stripe' ? 'card' : 'dollar'" class="text-brand" />
                                <span class="font-semibold text-ink">{{ m.label }}</span>
                            </label>
                        </div>
                        <p v-if="errors.payment_method" class="field-error" role="alert">{{ errors.payment_method }}</p>
                        <p v-if="f.payment_method === 'stripe'" class="text-xs text-gray-500">You will be redirected to Stripe's secure page to pay. We never see or store your card details.</p>
                        <p v-else class="text-xs text-gray-500">No payment is taken online. You will pay as arranged after your cleaning.</p>
                        <p class="text-sm font-medium text-ink">Total: {{ quote?.requires_quote ? 'Custom quote' : money(quote?.total) }}</p>
                    </div>

                    <div class="mt-8 flex items-center justify-between gap-3 border-t border-gray-100 pt-5">
                        <Btn v-if="step > 1" variant="outline" @click="back"><Icon name="arrow-left" class="h-4 w-4" />Back</Btn>
                        <span v-else />
                        <Btn v-if="step < 10" type="submit">Continue<Icon name="arrow-right" class="h-4 w-4" /></Btn>
                        <Btn v-else type="submit" size="lg" :loading="submitting">{{ f.payment_method === 'stripe' && !quote?.requires_quote ? 'Confirm & pay' : 'Confirm booking' }}</Btn>
                    </div>
                </form>

                <aside class="hidden lg:block">
                    <div class="sticky top-24"><PriceSummary :quote="quote" :loading="quoteLoading" :error="quoteError" :service-name="service?.name" :schedule="schedule" :address="addressLine" /></div>
                </aside>
            </div>
        </div>

        <!-- Mobile price bar -->
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-gray-200 bg-white shadow-[0_-4px_16px_rgba(0,0,0,.06)] lg:hidden">
            <button type="button" class="flex w-full items-center justify-between px-4 py-3 text-left" :aria-expanded="showMobileSummary" @click="showMobileSummary = !showMobileSummary">
                <span class="text-sm text-gray-500">Estimated total <Icon name="chevron-down" :class="['inline h-4 w-4 transition', showMobileSummary && 'rotate-180']" /></span>
                <span class="text-lg font-semibold text-ink">{{ quote?.requires_quote ? 'Custom quote' : quote ? money(quote.total) : '—' }}</span>
            </button>
            <div v-if="showMobileSummary" class="max-h-[60vh] overflow-y-auto border-t border-gray-100 px-4 pb-4">
                <PriceSummary :quote="quote" :loading="quoteLoading" :error="quoteError" :service-name="service?.name" :schedule="schedule" :address="addressLine" compact />
            </div>
        </div>
    </section>
</template>
