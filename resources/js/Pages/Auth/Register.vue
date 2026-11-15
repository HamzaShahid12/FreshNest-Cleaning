<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AuthCard from '@/Components/AuthCard.vue';
import Btn from '@/Components/Btn.vue';
import FormInput from '@/Components/FormInput.vue';

const form = useForm({ name: '', email: '', phone: '', password: '', password_confirmation: '' });
const submit = () => form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <AuthCard title="Create your account" subtitle="Book faster and manage your cleanings in one place.">
        <form class="space-y-4" @submit.prevent="submit">
            <FormInput v-model="form.name" label="Full name" autocomplete="name" required :error="form.errors.name" />
            <FormInput v-model="form.email" label="Email" type="email" autocomplete="email" required :error="form.errors.email" />
            <FormInput v-model="form.phone" label="Phone (optional)" type="tel" autocomplete="tel" :error="form.errors.phone" />
            <FormInput v-model="form.password" label="Password" type="password" autocomplete="new-password" required hint="At least 8 characters with letters and numbers." :error="form.errors.password" />
            <FormInput v-model="form.password_confirmation" label="Confirm password" type="password" autocomplete="new-password" required />
            <Btn type="submit" block :loading="form.processing">Create account</Btn>
        </form>
        <template #footer><p>Already have an account? <Link href="/login" class="font-semibold text-brand hover:underline">Sign in</Link></p></template>
    </AuthCard>
</template>
