<script setup>
import { router, useForm } from '@inertiajs/vue3';
import AuthCard from '@/Components/AuthCard.vue';
import Btn from '@/Components/Btn.vue';

const form = useForm({});
</script>

<template>
    <AuthCard title="Verify your email" subtitle="We sent a verification link to your email address. Click it to confirm your account.">
        <div class="space-y-3">
            <Btn block :loading="form.processing" @click="form.post('/email/verification-notification')">Resend verification email</Btn>
            <Btn variant="ghost" block @click="router.post('/logout')">Sign out</Btn>
        </div>
    </AuthCard>
</template>
