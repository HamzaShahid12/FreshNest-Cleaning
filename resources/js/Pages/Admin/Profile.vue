<script setup>
import ProfileForm from '@/Components/ProfileForm.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Seo from '@/Components/Seo.vue';

defineProps({ profile: Object });
</script>

<template>
    <Seo title="My profile" noindex />
    <PageHeader title="My profile" subtitle="Your photo and details." />
    <ProfileForm :profile="profile" />
</template>
