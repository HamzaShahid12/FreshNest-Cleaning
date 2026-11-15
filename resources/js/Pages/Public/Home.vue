<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import Btn from '@/Components/Btn.vue';
import FAQAccordion from '@/Components/FAQAccordion.vue';
import HeroSection from '@/Components/HeroSection.vue';
import Icon from '@/Components/Icon.vue';
import NewsletterForm from '@/Components/NewsletterForm.vue';
import PricingCard from '@/Components/PricingCard.vue';
import ReviewStars from '@/Components/ReviewStars.vue';
import Seo from '@/Components/Seo.vue';
import ServiceCard from '@/Components/ServiceCard.vue';
import SmartImage from '@/Components/SmartImage.vue';
import TestimonialCard from '@/Components/TestimonialCard.vue';
import api, { errorMessage } from '@/Services/api';

defineProps({
    services: { type: Array, default: () => [] },
    packages: { type: Array, default: () => [] },
    reviews: { type: Array, default: () => [] },
    reviewStats: { type: Object, default: () => ({ count: 0, average: 0 }) },
    faqs: { type: Array, default: () => [] },
    areas: { type: Array, default: () => [] },
    gallery: { type: Array, default: () => [] },
    posts: { type: Array, default: () => [] },
});

const trust = [
    { icon: 'shield', text: 'Background-checked cleaners' },
    { icon: 'check', text: 'Satisfaction guarantee' },
    { icon: 'heart', text: 'Eco-friendly products on request' },
];
const steps = [
    { icon: 'sparkles', title: 'Choose a service', text: 'Pick the clean that fits: standard, deep, move-in or office.' },
    { icon: 'home', title: 'Tell us about your space', text: 'Bedrooms, bathrooms, size and any extras. Your price updates instantly.' },
    { icon: 'calendar', title: 'Pick a time', text: 'Choose from real, available appointment slots that suit your schedule.' },
    { icon: 'heart', title: 'Relax', text: 'Our vetted team arrives, cleans to a checklist and leaves your space fresh.' },
];
const benefits = [
    { icon: 'shield', title: 'Vetted & trained team', text: 'Every cleaner is background-checked and trained to our standards.' },
    { icon: 'dollar', title: 'Transparent pricing', text: 'See the full price before you book. No hidden fees, ever.' },
    { icon: 'calendar', title: 'Flexible scheduling', text: 'Book online in minutes, reschedule or cancel from your account.' },
    { icon: 'clipboard', title: 'Checklist-based quality', text: 'A room-by-room checklist keeps quality consistent on every visit.' },
    { icon: 'heart', title: 'Eco-friendly options', text: 'Gentle, safe products for families and pets on request.' },
    { icon: 'check', title: 'Satisfaction guarantee', text: 'Not happy? Tell us within 24 hours and we will make it right.' },
];

// ZIP availability checker
const zip = ref('');
const zipResult = ref(null);
const zipLoading = ref(false);
async function checkZip() {
    zipResult.value = null;
    zipLoading.value = true;
    try {
        const { data } = await api.get('/api/check-area', { params: { q: zip.value.trim() } });
        zipResult.value = data.served
            ? { ok: true, text: `Great news! We serve ${data.q}.` }
            : { ok: false, text: `Sorry, we do not serve ${data.q} yet. Contact us and we will let you know when we expand.` };
    } catch (e) {
        zipResult.value = { ok: false, text: errorMessage(e, 'Enter your city or ZIP code.') };
    } finally {
        zipLoading.value = false;
    }
}
</script>

<template>
    <Seo title="Professional Home & Office Cleaning" description="FreshNest Cleaning Services: trusted residential and commercial cleaning. See your price online and book in minutes." />

    <HeroSection :rating="reviewStats.average" :review-count="reviewStats.count" />

    <!-- Trust indicators -->
    <section class="border-y border-gray-100 bg-white" aria-label="Why customers trust us">
        <ul class="container-x grid gap-4 py-6 sm:grid-cols-3">
            <li v-for="t in trust" :key="t.text" class="flex items-center justify-center gap-3 text-sm font-medium text-ink">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-soft text-brand"><Icon :name="t.icon" class="h-5 w-5" /></span>{{ t.text }}
            </li>
        </ul>
    </section>

    <!-- Services -->
    <section class="section">
        <div class="container-x">
            <div class="mx-auto max-w-2xl text-center">
                <p class="eyebrow">Our cleaning services</p>
                <h2 class="h-section mt-2">A clean for every space and every schedule</h2>
                <p class="lead mt-3">From a weekly refresh to a move-out deep clean, choose the service that fits.</p>
            </div>
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <ServiceCard v-for="s in services" :key="s.id" :service="s" />
            </div>
            <div class="mt-10 text-center"><Btn href="/services" variant="outline">View all services<Icon name="arrow-right" class="h-4 w-4" /></Btn></div>
        </div>
    </section>

    <!-- How it works -->
    <section class="section bg-brand-light">
        <div class="container-x">
            <div class="mx-auto max-w-2xl text-center">
                <p class="eyebrow">How it works</p>
                <h2 class="h-section mt-2">Booking takes about two minutes</h2>
            </div>
            <ol class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <li v-for="(s, i) in steps" :key="s.title" class="card p-6">
                    <div class="flex items-center justify-between">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand text-white"><Icon :name="s.icon" /></span>
                        <span class="font-display text-3xl font-semibold text-brand/30">{{ i + 1 }}</span>
                    </div>
                    <h3 class="mt-4 font-semibold text-ink">{{ s.title }}</h3>
                    <p class="mt-1.5 text-sm text-body">{{ s.text }}</p>
                </li>
            </ol>
        </div>
    </section>

    <!-- Why FreshNest -->
    <section class="section">
        <div class="container-x">
            <div class="mx-auto max-w-2xl text-center">
                <p class="eyebrow">Why choose FreshNest</p>
                <h2 class="h-section mt-2">Cleaning you can count on</h2>
            </div>
            <div class="mt-12 grid gap-x-8 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="b in benefits" :key="b.title" class="flex gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-soft text-brand"><Icon :name="b.icon" /></span>
                    <div><h3 class="font-semibold text-ink">{{ b.title }}</h3><p class="mt-1 text-sm text-body">{{ b.text }}</p></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Packages -->
    <section v-if="packages.length" class="section bg-brand-light">
        <div class="container-x">
            <div class="mx-auto max-w-2xl text-center">
                <p class="eyebrow">Featured packages</p>
                <h2 class="h-section mt-2">Simple, honest pricing</h2>
                <p class="lead mt-3">Final price depends on your home size, extras and how often you clean. You see it before you book.</p>
            </div>
            <div class="mt-12 grid gap-6 md:grid-cols-3">
                <PricingCard v-for="(p, i) in packages" :key="p.id" :pkg="p" :highlight="i === 1" />
            </div>
            <div class="mt-8 text-center"><Link href="/pricing" class="text-sm font-semibold text-brand hover:underline">Compare all prices &rarr;</Link></div>
        </div>
    </section>

    <!-- Before / after -->
    <section v-if="gallery.length" class="section">
        <div class="container-x">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div><p class="eyebrow">Before &amp; after</p><h2 class="h-section mt-2">See the FreshNest difference</h2></div>
                <Btn href="/gallery" variant="outline">Full gallery</Btn>
            </div>
            <div class="mt-10 grid gap-6 md:grid-cols-3">
                <figure v-for="g in gallery" :key="g.id" class="card overflow-hidden">
                    <div class="grid grid-cols-2 gap-px bg-gray-100">
                        <div class="relative"><SmartImage :src="g.before_url" :alt="`${g.title} before`" icon="photo" class="aspect-square w-full" /><span class="absolute left-2 top-2 rounded-full bg-ink px-2.5 py-0.5 text-xs font-semibold text-white">Before</span></div>
                        <div class="relative"><SmartImage :src="g.after_url" :alt="`${g.title} after`" class="aspect-square w-full" /><span class="absolute left-2 top-2 rounded-full bg-brand px-2.5 py-0.5 text-xs font-semibold text-white">After</span></div>
                    </div>
                    <figcaption class="p-4"><span class="font-semibold text-ink">{{ g.title }}</span><span v-if="g.description" class="block text-sm text-body">{{ g.description }}</span></figcaption>
                </figure>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section v-if="reviews.length" class="section bg-brand-light">
        <div class="container-x">
            <div class="mx-auto max-w-2xl text-center">
                <p class="eyebrow">Customer love</p>
                <h2 class="h-section mt-2">What our customers say</h2>
                <p v-if="reviewStats.count" class="mt-3 flex items-center justify-center gap-2 text-sm text-gray-600">
                    <ReviewStars :rating="reviewStats.average" /><span><strong class="text-ink">{{ reviewStats.average }}</strong> from {{ reviewStats.count }} reviews</span>
                </p>
            </div>
            <div class="mt-12 grid gap-6 md:grid-cols-3">
                <TestimonialCard v-for="r in reviews.slice(0, 3)" :key="r.id" :review="r" />
            </div>
            <div class="mt-8 text-center"><Link href="/reviews" class="text-sm font-semibold text-brand hover:underline">Read all reviews &rarr;</Link></div>
        </div>
    </section>

    <!-- Areas -->
    <section class="section">
        <div class="container-x grid items-center gap-10 lg:grid-cols-2">
            <div>
                <p class="eyebrow">Service areas</p>
                <h2 class="h-section mt-2">Proudly serving your neighbourhood</h2>
                <p class="lead mt-3">Check whether we clean in your city or ZIP / postal code.</p>
                <form class="mt-6 flex max-w-md gap-2" @submit.prevent="checkZip">
                    <label for="zip" class="sr-only">City or ZIP / postal code</label>
                    <input id="zip" v-model="zip" class="input" maxlength="100" autocomplete="off" placeholder="Enter city or ZIP code" required>
                    <Btn type="submit" :loading="zipLoading">Check</Btn>
                </form>
                <p v-if="zipResult" :class="['mt-3 text-sm font-medium', zipResult.ok ? 'text-brand' : 'text-red-600']" role="status">{{ zipResult.text }}</p>
            </div>
            <div>
                <ul class="flex flex-wrap gap-2">
                    <li v-for="a in areas" :key="a.id"><Link :href="`/areas/${a.slug}`" class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-ink transition hover:border-brand hover:text-brand"><Icon name="map-pin" class="h-4 w-4 text-brand" />{{ a.name }}<span v-if="a.state">, {{ a.state }}</span></Link></li>
                </ul>
                <p v-if="!areas.length" class="text-sm text-gray-500">Service areas coming soon.</p>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section v-if="faqs.length" class="section bg-brand-light">
        <div class="container-x max-w-3xl">
            <div class="text-center"><p class="eyebrow">FAQ</p><h2 class="h-section mt-2">Questions, answered</h2></div>
            <div class="mt-10"><FAQAccordion :items="faqs" /></div>
            <div class="mt-6 text-center"><Link href="/faq" class="text-sm font-semibold text-brand hover:underline">See all questions &rarr;</Link></div>
        </div>
    </section>

    <!-- Blog -->
    <section v-if="posts.length" class="section">
        <div class="container-x">
            <div class="flex items-end justify-between gap-4"><div><p class="eyebrow">From the blog</p><h2 class="h-section mt-2">Cleaning tips &amp; news</h2></div><Link href="/blog" class="text-sm font-semibold text-brand hover:underline">All articles &rarr;</Link></div>
            <div class="mt-10 grid gap-6 md:grid-cols-3">
                <Link v-for="p in posts" :key="p.id" :href="`/blog/${p.slug}`" class="card group overflow-hidden transition hover:-translate-y-0.5 hover:shadow-lg">
                    <SmartImage :src="p.image_url" :alt="p.title" icon="document" class="aspect-[16/9] w-full" />
                    <div class="p-5"><h3 class="font-display text-lg font-semibold text-ink group-hover:text-brand">{{ p.title }}</h3><p class="mt-2 line-clamp-3 text-sm text-body">{{ p.excerpt }}</p></div>
                </Link>
            </div>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="bg-ink py-14">
        <div class="container-x grid items-center gap-6 md:grid-cols-2">
            <div><h2 class="font-display text-2xl font-semibold text-white sm:text-3xl">Fresh tips in your inbox</h2><p class="mt-2 text-gray-300">Occasional cleaning advice and offers. Unsubscribe anytime.</p></div>
            <NewsletterForm dark />
        </div>
    </section>

    <!-- Final CTA -->
    <section class="section">
        <div class="container-x">
            <div class="rounded-3xl bg-brand px-6 py-14 text-center text-white sm:px-12">
                <h2 class="font-display text-3xl font-semibold sm:text-4xl">Ready for a cleaner home?</h2>
                <p class="mx-auto mt-3 max-w-xl text-green-50">Get an instant price and book your cleaning in just a few clicks.</p>
                <div class="mt-8 flex flex-wrap justify-center gap-3"><Btn href="/book" variant="outline" size="lg">Book a Cleaning</Btn><Btn href="/contact" variant="ghost" size="lg" class="!text-white hover:!bg-white/10">Talk to us</Btn></div>
            </div>
        </div>
    </section>
</template>
