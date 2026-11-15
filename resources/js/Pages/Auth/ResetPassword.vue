<script setup>
import { useForm } from '@inertiajs/vue3';
import AuthCard from '@/Components/AuthCard.vue';
import Btn from '@/Components/Btn.vue';
import FormInput from '@/Components/FormInput.vue';

const props = defineProps({ token: String, email: String });
const form = useForm({ token: props.token, email: props.email || '', password: '', password_confirmation: '' });
const submit = () => form.post('/reset-password', { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <AuthCard title="Choose a new password">
        <form class="space-y-4" @submit.prevent="submit">
            <FormInput v-model="form.email" label="Email" type="email" autocomplete="email" required :error="form.errors.email" />
            <FormInput v-model="form.password" label="New password" type="password" autocomplete="new-password" required hint="At least 8 characters with letters and numbers." :error="form.errors.password" />
            <FormInput v-model="form.password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" required />
            <Btn type="submit" block :loading="form.processing">Reset password</Btn>
        </form>
    </AuthCard>
</template>
