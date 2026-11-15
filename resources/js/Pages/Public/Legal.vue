<script setup>
import PageBanner from '@/Components/PageBanner.vue';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Icon from '@/Components/Icon.vue';
import Seo from '@/Components/Seo.vue';

const props = defineProps({ slug: String, cancellationHours: { type: Number, default: 24 } });
const biz = computed(() => usePage().props.site?.name || 'FreshNest Cleaning Services');
const email = computed(() => usePage().props.site?.email || 'our support email');

const docs = computed(() => ({
    privacy: {
        title: 'Privacy Policy',
        sections: [
            ['Information we collect', `${biz.value} collects the details you give us when you book or contact us: name, email address, phone number, service address, booking notes and payment status. Card details are entered on our payment provider's secure page and are never stored on our servers.`],
            ['How we use your information', 'We use your information to provide and schedule cleaning services, send booking confirmations and reminders, process payments, respond to your enquiries, and improve our service. We only send marketing emails if you subscribe to our newsletter.'],
            ['Who we share it with', 'Your service address and contact details are shared only with the cleaner assigned to your booking. We use trusted providers for payments and email delivery. We do not sell your personal information.'],
            ['Cookies', 'We use essential cookies to keep you signed in and protect your session. We do not use advertising cookies.'],
            ['Retention and your rights', `We keep booking records as long as needed for service, accounting and legal purposes. You can update your details from your account, or email ${email.value} to request access, correction or deletion of your information.`],
            ['Security', 'Passwords are stored using strong one-way hashing, and access to customer data is limited to staff who need it to do their work.'],
        ],
    },
    terms: {
        title: 'Terms & Conditions',
        sections: [
            ['Our services', `${biz.value} provides residential and commercial cleaning services as described on our website. The tasks included in each service are listed on its service page.`],
            ['Bookings and pricing', 'The price shown at checkout is calculated from the options you choose. A booking is a request until we confirm it. Custom-quote services are priced after we review your requirements.'],
            ['Access and preparation', 'Please make sure our team can access the property at the scheduled time and secure valuables and fragile items. Tell us about pets, alarms and special instructions when you book.'],
            ['Payment', 'Payment methods offered at checkout may include card payment, cash on completion, or invoice. Amounts are due as described at booking.'],
            ['Satisfaction guarantee', 'If you are not satisfied, tell us within 24 hours of the visit and we will arrange to re-clean the areas of concern at no extra cost.'],
            ['Damage and liability', 'Please report any damage within 24 hours so we can investigate. Our liability is limited to the extent permitted by law.'],
            ['Changes to these terms', 'We may update these terms from time to time. The version on this page applies to bookings made after the update.'],
        ],
    },
    cancellation: {
        title: 'Cancellation Policy',
        sections: [
            ['Free cancellation and rescheduling', `You can cancel or reschedule free of charge from your account up to ${props.cancellationHours} hours before your appointment.`],
            ['Late changes', `Within ${props.cancellationHours} hours of the appointment, online changes are disabled. Please contact us and we will do our best to help. Late cancellations may be charged.`],
            ['If we need to cancel', 'If we cannot attend, we will contact you as soon as possible to reschedule at your convenience or give a full refund of any amount paid.'],
            ['Refunds', 'Eligible refunds are returned to the original payment method. Card refunds can take several business days to appear.'],
            ['Lockouts and no-access', 'If our team cannot access the property at the scheduled time, the visit may be treated as a late cancellation.'],
        ],
    },
}[props.slug]));
</script>

<template>
    <Seo :title="docs.title" :description="`${docs.title} for ${biz}.`" />

    <PageBanner page="legal" eyebrow="Legal" :title="docs.title" />

    <section class="section">
        <div class="container-x max-w-3xl">
            <div class="mt-4 flex gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <Icon name="info" class="mt-0.5" /><p>This is template text provided with the platform. The business owner should review it (ideally with legal counsel) and adapt it before going live.</p>
            </div>
            <div class="mt-8 space-y-8">
                <section v-for="([heading, body], i) in docs.sections" :key="i">
                    <h2 class="font-display text-xl font-semibold text-ink">{{ i + 1 }}. {{ heading }}</h2>
                    <p class="mt-2 leading-relaxed text-body">{{ body }}</p>
                </section>
            </div>
        </div>
    </section>
</template>
