<script setup>
import PageBanner from '@/Components/PageBanner.vue';
import { computed } from 'vue';
import Btn from '@/Components/Btn.vue';
import Icon from '@/Components/Icon.vue';
import PricingCard from '@/Components/PricingCard.vue';
import Seo from '@/Components/Seo.vue';
import { duration, money } from '@/Composables/useFormat';

const props = defineProps({
    services: Array, packages: Array, extras: Array, frequencies: Array, rates: Object,
});

const cols = computed(() => props.services.filter((s) => s.pricing_method !== 'quote').slice(0, 4));
const priced = (base, pct) => money(base * (1 - pct / 100));
const fromPrice = (s) => (s.pricing_method === 'quote' ? 'Custom quote' : `${s.pricing_method === 'size_based' ? 'From ' : ''}${money(s.base_price)}`);
</script>

<template>
    <Seo title="Pricing" description="Transparent cleaning prices. See base prices, recurring discounts and add-on costs, then get an exact quote online." />

    <PageBanner page="pricing" eyebrow="Pricing" title="Simple, transparent pricing" subtitle="No hidden fees. Start with a base price, add the size of your home and any extras, and save more with a recurring plan. You always see your exact total before you book." />

    <section v-if="packages.length" class="section">
        <div class="container-x">
            <h2 class="h-section text-center">Popular packages</h2>
            <div class="mt-10 grid gap-6 md:grid-cols-3">
                <PricingCard v-for="(p, i) in packages" :key="p.slug" :pkg="p" :highlight="i === 1" />
            </div>
        </div>
    </section>

    <section class="section bg-brand-light">
        <div class="container-x">
            <h2 class="h-section text-center">Save with a recurring plan</h2>
            <p class="lead mx-auto mt-3 max-w-2xl text-center">The more often we visit, the more you save. Discounts apply to the full cleaning price.</p>
            <div class="mt-10 overflow-x-auto rounded-2xl border border-gray-100 bg-white shadow-card">
                <table class="table-base">
                    <caption class="sr-only">Price by cleaning frequency</caption>
                    <thead>
                        <tr>
                            <th scope="col">Service</th>
                            <th v-for="f in frequencies" :key="f.key" scope="col">
                                {{ f.label }}<span v-if="f.discount" class="ml-1 normal-case text-brand">-{{ f.discount }}%</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in cols" :key="s.slug">
                            <th scope="row" class="bg-white px-4 py-3 text-left font-medium normal-case tracking-normal text-ink">{{ s.name }}</th>
                            <td v-for="f in frequencies" :key="f.key">{{ priced(s.base_price, f.discount) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-center text-xs text-gray-500">Base prices shown for a standard-sized home. Larger homes and extras are added in the booking quote.</p>
        </div>
    </section>

    <section class="section">
        <div class="container-x grid gap-10 lg:grid-cols-2">
            <div>
                <h2 class="h-section">How we calculate your price</h2>
                <ul class="mt-6 space-y-3 text-body">
                    <li class="flex gap-3"><Icon name="check" class="mt-0.5 text-brand" /><span><strong class="text-ink">Base price</strong> for the service you choose.</span></li>
                    <li class="flex gap-3"><Icon name="check" class="mt-0.5 text-brand" /><span><strong class="text-ink">{{ money(rates.bedroom) }}</strong> for each bedroom after the first.</span></li>
                    <li class="flex gap-3"><Icon name="check" class="mt-0.5 text-brand" /><span><strong class="text-ink">{{ money(rates.bathroom) }}</strong> for each bathroom after the first.</span></li>
                    <li class="flex gap-3"><Icon name="check" class="mt-0.5 text-brand" /><span><strong class="text-ink">{{ money(rates.sqft_block_rate) }}</strong> for every additional {{ rates.sqft_block_size }} sq ft above {{ rates.sqft_included }} sq ft.</span></li>
                    <li class="flex gap-3"><Icon name="check" class="mt-0.5 text-brand" /><span>Optional <strong class="text-ink">extras</strong> and a recurring-plan or promo-code discount.</span></li>
                </ul>
                <Btn href="/book" class="mt-8" size="lg">Get my exact price<Icon name="arrow-right" class="h-4 w-4" /></Btn>
            </div>
            <div>
                <h2 class="h-section">Additional services</h2>
                <ul class="mt-6 divide-y divide-gray-100 rounded-2xl border border-gray-100 bg-white shadow-card">
                    <li v-for="e in extras" :key="e.id" class="flex items-center justify-between gap-4 px-5 py-3.5">
                        <div><p class="font-medium text-ink">{{ e.name }}</p><p v-if="e.description" class="text-xs text-gray-500">{{ e.description }}</p></div>
                        <p class="font-semibold text-ink">{{ money(e.price) }}</p>
                    </li>
                    <li v-if="!extras.length" class="px-5 py-6 text-sm text-gray-500">No add-ons are currently available.</li>
                </ul>
            </div>
        </div>
    </section>

    <section class="section bg-brand-light">
        <div class="container-x">
            <h2 class="h-section text-center">Every service at a glance</h2>
            <div class="mt-10 overflow-x-auto rounded-2xl border border-gray-100 bg-white shadow-card">
                <table class="table-base">
                    <thead><tr><th scope="col">Service</th><th scope="col">Price</th><th scope="col">Typical time</th><th scope="col"><span class="sr-only">Book</span></th></tr></thead>
                    <tbody>
                        <tr v-for="s in services" :key="s.slug">
                            <td class="font-medium text-ink">{{ s.name }}</td>
                            <td>{{ fromPrice(s) }}</td>
                            <td>{{ duration(s.duration_minutes) }}</td>
                            <td class="text-right"><Btn :href="`/book?service=${s.slug}`" size="sm" variant="soft">Book</Btn></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</template>
