<script setup>
import { router, useForm } from '@inertiajs/vue3';
import AuthCard from '@/Components/AuthCard.vue';
import Btn from '@/Components/Btn.vue';
import FormInput from '@/Components/FormInput.vue';

const form = useForm({ current_password: '', password: '', password_confirmation: '' });
const submit = () => form.post('/password/change', { onFinish: () => form.reset() });
</script>

<template>
    <AuthCard title="Set a new password" subtitle="For your security this account must choose a new password before continuing.">
        <form class="space-y-4" @submit.prevent="submit">
            <FormInput v-model="form.current_password" label="Current password" type="password" autocomplete="current-password" required :error="form.errors.current_password" />
            <FormInput v-model="form.password" label="New password" type="password" autocomplete="new-password" required hint="At least 10 characters with upper and lower case letters and a number." :error="form.errors.password" />
            <FormInput v-model="form.password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" required />
            <Btn type="submit" block :loading="form.processing">Update password</Btn>
        </form>
        <template #footer><button type="button" class="font-semibold text-brand hover:underline" @click="router.post('/logout')">Sign out</button></template>
    </AuthCard>
</template>
