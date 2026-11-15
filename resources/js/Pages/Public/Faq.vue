<script setup>
import PageBanner from '@/Components/PageBanner.vue';
import { computed, ref } from 'vue';
import Btn from '@/Components/Btn.vue';
import FAQAccordion from '@/Components/FAQAccordion.vue';
import Icon from '@/Components/Icon.vue';
import Seo from '@/Components/Seo.vue';

const props = defineProps({ faqs: Array });
const search = ref('');
const category = ref('All');

const categories = computed(() => ['All', ...new Set(props.faqs.map((f) => f.category))]);
const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();

    return props.faqs.filter((f) => (category.value === 'All' || f.category === category.value)
        && (!q || f.question.toLowerCase().includes(q) || f.answer.toLowerCase().includes(q)));
});
const ld = computed(() => ({
    '@context': 'https://schema.org', '@type': 'FAQPage',
    mainEntity: props.faqs.slice(0, 20).map((f) => ({ '@type': 'Question', name: f.question, acceptedAnswer: { '@type': 'Answer', text: f.answer } })),
}));
</script>

<template>
    <Seo title="Frequently Asked Questions" description="Answers about booking, pricing, payments and our cleaning services." :json-ld="ld" />

    <PageBanner page="faq" eyebrow="FAQ" title="Frequently asked questions">
        <div class="relative mx-auto max-w-xl">
            <Icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" />
            <label for="faq-search" class="sr-only">Search questions</label>
            <input id="faq-search" v-model="search" type="search" class="input pl-11" placeholder="Search questions…">
        </div>
    </PageBanner>

    <section class="section">
        <div class="container-x max-w-3xl">
            <div class="mb-6 flex flex-wrap gap-2">
                <button
                    v-for="c in categories" :key="c" type="button" :aria-pressed="category === c"
                    :class="['rounded-full px-4 py-1.5 text-sm font-medium transition', category === c ? 'bg-brand text-white' : 'bg-gray-100 text-body hover:bg-brand-soft']"
                    @click="category = c"
                >{{ c }}</button>
            </div>
            <FAQAccordion :items="filtered" />
            <div class="mt-12 rounded-2xl bg-brand-soft p-6 text-center">
                <p class="font-display text-lg font-semibold text-ink">Still have a question?</p>
                <div class="mt-4"><Btn href="/contact">Contact us</Btn></div>
            </div>
        </div>
    </section>
</template>
