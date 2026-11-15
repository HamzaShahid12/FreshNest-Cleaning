<script setup>
import { useForm } from '@inertiajs/vue3';
import AvatarUpload from '@/Components/AvatarUpload.vue';
import Btn from '@/Components/Btn.vue';
import FormInput from '@/Components/FormInput.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Seo from '@/Components/Seo.vue';

const props = defineProps({ profile: Object });
const info = useForm({ name: props.profile.name, email: props.profile.email, phone: props.profile.phone || '' });
const pass = useForm({ current_password: '', password: '', password_confirmation: '' });
const savePass = () => pass.put('/account/password', { preserveScroll: true, onSuccess: () => pass.reset() });
</script>

<template>
    <Seo title="Profile" noindex />
    <PageHeader title="Profile & security" />
    <div class="card card-pad mb-6"><AvatarUpload :user="profile" /></div>
    <div class="grid gap-6 lg:grid-cols-2">
        <form class="card card-pad space-y-4" @submit.prevent="info.put('/account/profile', { preserveScroll: true })">
            <h2 class="font-display text-lg font-semibold text-ink">Your details</h2>
            <FormInput v-model="info.name" label="Full name" autocomplete="name" required :error="info.errors.name" />
            <FormInput v-model="info.email" label="Email" type="email" autocomplete="email" required :error="info.errors.email" :hint="profile.verified ? 'Verified. Changing your email requires re-verification.' : 'Not verified yet. Check your inbox for the link.'" />
            <FormInput v-model="info.phone" label="Phone" type="tel" autocomplete="tel" :error="info.errors.phone" />
            <Btn type="submit" :loading="info.processing">Save changes</Btn>
        </form>

        <form class="card card-pad space-y-4" @submit.prevent="savePass">
            <h2 class="font-display text-lg font-semibold text-ink">Change password</h2>
            <FormInput v-model="pass.current_password" label="Current password" type="password" autocomplete="current-password" required :error="pass.errors.current_password" />
            <FormInput v-model="pass.password" label="New password" type="password" autocomplete="new-password" required hint="At least 8 characters with letters and numbers." :error="pass.errors.password" />
            <FormInput v-model="pass.password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" required />
            <Btn type="submit" :loading="pass.processing">Update password</Btn>
        </form>
    </div>
</template>
