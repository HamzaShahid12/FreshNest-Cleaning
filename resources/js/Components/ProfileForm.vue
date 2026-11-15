<script setup>
import { useForm } from '@inertiajs/vue3';
import AvatarUpload from '@/Components/AvatarUpload.vue';
import Btn from '@/Components/Btn.vue';
import FormInput from '@/Components/FormInput.vue';

// Profile editor for staff and admin accounts (customers have their own page). profile: { name, email, phone, avatar_url, role_label }
const props = defineProps({ profile: { type: Object, required: true } });

const info = useForm({ name: props.profile.name, phone: props.profile.phone || '' });
const pass = useForm({ current_password: '', password: '', password_confirmation: '' });
const savePass = () => pass.put('/profile/password', { preserveScroll: true, onSuccess: () => pass.reset() });
</script>

<template>
    <div class="space-y-6">
        <div class="card card-pad"><AvatarUpload :user="profile" /></div>
        <div class="grid gap-6 lg:grid-cols-2">
            <form class="card card-pad space-y-4" @submit.prevent="info.put('/profile', { preserveScroll: true })">
                <h2 class="font-display text-lg font-semibold text-ink">Your details</h2>
                <FormInput v-model="info.name" label="Full name" autocomplete="name" required :error="info.errors.name" />
                <FormInput :model-value="profile.email" label="Email" type="email" disabled hint="Ask an administrator to change your sign-in email." />
                <FormInput v-model="info.phone" label="Phone" type="tel" autocomplete="tel" :error="info.errors.phone" />
                <p class="text-xs text-gray-500">Role: {{ profile.role_label }}</p>
                <Btn type="submit" :loading="info.processing">Save changes</Btn>
            </form>
            <form class="card card-pad space-y-4" @submit.prevent="savePass">
                <h2 class="font-display text-lg font-semibold text-ink">Change password</h2>
                <FormInput v-model="pass.current_password" label="Current password" type="password" autocomplete="current-password" required :error="pass.errors.current_password" />
                <FormInput v-model="pass.password" label="New password" type="password" autocomplete="new-password" required hint="At least 10 characters with upper and lower case letters and a number." :error="pass.errors.password" />
                <FormInput v-model="pass.password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" required />
                <Btn type="submit" :loading="pass.processing">Update password</Btn>
            </form>
        </div>
    </div>
</template>
