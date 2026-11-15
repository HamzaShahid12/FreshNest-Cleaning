<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Btn from '@/Components/Btn.vue';
import Seo from '@/Components/Seo.vue';
import SmartImage from '@/Components/SmartImage.vue';
import { paragraphs } from '@/Composables/useFormat';

const props = defineProps({ post: Object, related: Array });
const paras = computed(() => paragraphs(props.post.body));
const ld = computed(() => ({
    '@context': 'https://schema.org', '@type': 'BlogPosting', headline: props.post.title,
    description: props.post.excerpt, datePublished: props.post.date, author: { '@type': 'Person', name: props.post.author || 'FreshNest' },
    ...(props.post.image_url ? { image: props.post.image_url } : {}),
}));
</script>

<template>
    <Seo :title="post.seo_title || post.title" :description="post.seo_description || post.excerpt" :image="post.image_url" type="article" :json-ld="ld" />

    <article class="section">
        <div class="container-x max-w-3xl">
            <Link href="/blog" class="text-sm font-medium text-brand hover:underline">&larr; Back to blog</Link>
            <p class="mt-6 text-xs font-semibold uppercase tracking-wide text-brand">{{ post.category || 'Article' }} &middot; {{ post.date }}<span v-if="post.author"> &middot; {{ post.author }}</span></p>
            <h1 class="h-section mt-2 !text-4xl sm:!text-5xl">{{ post.title }}</h1>
            <SmartImage v-if="post.image_url" :src="post.image_url" :alt="post.title" icon="document" class="mt-8 aspect-[16/9] rounded-2xl" />
            <div class="mt-8 space-y-5 text-lg leading-relaxed text-body"><p v-for="(p, i) in paras" :key="i">{{ p }}</p></div>
            <div class="mt-12 rounded-2xl bg-brand-soft p-6 text-center">
                <p class="font-display text-xl font-semibold text-ink">Ready for a cleaner home?</p>
                <div class="mt-4"><Btn href="/book" size="lg">Book a Cleaning</Btn></div>
            </div>
        </div>
    </article>

    <section v-if="related.length" class="bg-brand-light py-14">
        <div class="container-x">
            <h2 class="font-display text-2xl font-semibold text-ink">Keep reading</h2>
            <div class="mt-6 grid gap-5 md:grid-cols-3">
                <Link v-for="r in related" :key="r.id" :href="`/blog/${r.slug}`" class="card card-pad transition hover:border-brand">
                    <h3 class="font-display text-lg font-semibold text-ink">{{ r.title }}</h3>
                    <p v-if="r.excerpt" class="mt-2 line-clamp-3 text-sm text-body">{{ r.excerpt }}</p>
                </Link>
            </div>
        </div>
    </section>
</template>
