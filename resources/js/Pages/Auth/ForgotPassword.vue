<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AuthCard from '@/Components/AuthCard.vue';
import Btn from '@/Components/Btn.vue';
import FormInput from '@/Components/FormInput.vue';

const form = useForm({ email: '' });
</script>

<template>
    <AuthCard title="Forgot your password?" subtitle="Enter your email and we will send you a reset link.">
        <form class="space-y-4" @submit.prevent="form.post('/forgot-password')">
            <FormInput v-model="form.email" label="Email" type="email" autocomplete="email" required :error="form.errors.email" />
            <Btn type="submit" block :loading="form.processing">Email reset link</Btn>
        </form>
        <template #footer><Link href="/login" class="font-semibold text-brand hover:underline">Back to sign in</Link></template>
    </AuthCard>
</template>
