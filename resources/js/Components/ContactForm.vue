<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import Btn from '@/Components/Btn.vue';
import FormInput from '@/Components/FormInput.vue';
import FormTextarea from '@/Components/FormTextarea.vue';

const user = usePage().props.auth?.user;
const form = useForm({
    name: user?.name || '', email: user?.email || '', phone: '', subject: '', message: '',
    website: '', // honeypot: real visitors never fill this in
});
const submit = () => form.post('/contact', { preserveScroll: true, onSuccess: () => form.reset('subject', 'message') });
</script>

<template>
    <form class="space-y-4" novalidate @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-2">
            <FormInput v-model="form.name" label="Full name" autocomplete="name" required :error="form.errors.name" />
            <FormInput v-model="form.email" label="Email" type="email" autocomplete="email" required :error="form.errors.email" />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <FormInput v-model="form.phone" label="Phone (optional)" type="tel" autocomplete="tel" :error="form.errors.phone" />
            <FormInput v-model="form.subject" label="Subject" required :error="form.errors.subject" />
        </div>
        <FormTextarea v-model="form.message" label="How can we help?" :rows="5" required :error="form.errors.message" />
        <div class="absolute -left-[9999px] h-0 w-0 overflow-hidden" aria-hidden="true">
            <label>Leave this field empty<input v-model="form.website" type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>
        <Btn type="submit" size="lg" :loading="form.processing">Send message</Btn>
    </form>
</template>
