<script setup>
import { computed, ref, watch } from 'vue';

// user: { name, avatar_url }. Shows the profile photo, or initials when there is none (or it fails to load).
const props = defineProps({ user: { type: Object, default: null }, size: { type: String, default: 'md' } });

const failed = ref(false);
watch(() => props.user?.avatar_url, () => { failed.value = false; });

const sizes = { xs: 'h-6 w-6 text-[10px]', sm: 'h-8 w-8 text-xs', md: 'h-10 w-10 text-sm', lg: 'h-16 w-16 text-lg', xl: 'h-28 w-28 text-3xl' };
const initials = computed(() => (props.user?.name || '?').split(/\s+/).filter(Boolean).slice(0, 2).map((p) => p[0].toUpperCase()).join('') || '?');
</script>

<template>
    <span :class="['inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-brand-soft font-semibold text-brand-dark ring-2 ring-white', sizes[size] || sizes.md]">
        <img v-if="user?.avatar_url && !failed" :src="user.avatar_url" :alt="user.name ? `${user.name} profile photo` : 'Profile photo'" class="h-full w-full object-cover" @error="failed = true">
        <span v-else aria-hidden="true">{{ initials }}</span>
    </span>
</template>
