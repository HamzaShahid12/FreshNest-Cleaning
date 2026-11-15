<script setup>
import PageBanner from '@/Components/PageBanner.vue';
import { Link } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import Seo from '@/Components/Seo.vue';
import SmartImage from '@/Components/SmartImage.vue';

defineProps({ posts: Object, categories: Array, activeCategory: String });
</script>

<template>
    <Seo title="Cleaning Tips & News" description="Practical cleaning tips, checklists and news from the FreshNest team." />

    <PageBanner page="blog" eyebrow="Blog" title="Cleaning tips &amp; news">
        <div class="flex flex-wrap justify-center gap-2">
            <Link href="/blog" :class="['rounded-full px-4 py-1.5 text-sm font-medium', !activeCategory ? 'bg-brand text-white' : 'bg-white text-body hover:bg-brand-soft']">All</Link>
            <Link v-for="c in categories" :key="c.id" :href="`/blog?category=${c.slug}`" :class="['rounded-full px-4 py-1.5 text-sm font-medium', activeCategory === c.slug ? 'bg-brand text-white' : 'bg-white text-body hover:bg-brand-soft']">{{ c.name }}</Link>
        </div>
    </PageBanner>

    <section class="section">
        <div class="container-x">
            <div v-if="posts.data.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <article v-for="p in posts.data" :key="p.id" class="card overflow-hidden transition hover:-translate-y-0.5">
                    <Link :href="`/blog/${p.slug}`">
                        <SmartImage :src="p.image_url" :alt="p.title" icon="document" class="aspect-[16/9]" />
                        <div class="p-5">
                            <p class="text-xs font-semibold uppercase tracking-wide text-brand">{{ p.category || 'Article' }} &middot; {{ p.date }}</p>
                            <h2 class="mt-2 font-display text-lg font-semibold text-ink">{{ p.title }}</h2>
                            <p v-if="p.excerpt" class="mt-2 line-clamp-3 text-sm text-body">{{ p.excerpt }}</p>
                        </div>
                    </Link>
                </article>
            </div>
            <p v-else class="text-center text-gray-500">No articles yet. Check back soon!</p>
            <div class="mt-10"><Pagination :links="posts.links" /></div>
        </div>
    </section>
</template>
