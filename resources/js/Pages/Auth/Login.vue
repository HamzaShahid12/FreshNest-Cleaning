<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AuthCard from '@/Components/AuthCard.vue';
import Btn from '@/Components/Btn.vue';
import FormCheckbox from '@/Components/FormCheckbox.vue';
import FormInput from '@/Components/FormInput.vue';

const props = defineProps({ portal: { type: String, default: 'customer' } });
const form = useForm({ email: '', password: '', remember: false, portal: props.portal });
const submit = () => form.post('/login', { onFinish: () => form.reset('password') });
</script>

<template>
    <AuthCard
        :title="portal === 'staff' ? 'Staff sign in' : 'Welcome back'"
        :subtitle="portal === 'staff' ? 'For FreshNest cleaners, managers and administrators.' : 'Sign in to manage your bookings.'"
    >
        <form class="space-y-4" @submit.prevent="submit">
            <FormInput v-model="form.email" label="Email" type="email" autocomplete="email" required :error="form.errors.email" />
            <FormInput v-model="form.password" label="Password" type="password" autocomplete="current-password" required :error="form.errors.password" />
            <div class="flex items-center justify-between">
                <FormCheckbox v-model="form.remember" label="Remember me" />
                <Link href="/forgot-password" class="text-sm font-medium text-brand hover:underline">Forgot password?</Link>
            </div>
            <Btn type="submit" block :loading="form.processing">Sign in</Btn>
        </form>
        <template #footer>
            <p v-if="portal === 'customer'">New to FreshNest? <Link href="/register" class="font-semibold text-brand hover:underline">Create an account</Link></p>
            <p v-else>Are you a customer? <Link href="/login" class="font-semibold text-brand hover:underline">Customer sign in</Link></p>
        </template>
    </AuthCard>
</template>
