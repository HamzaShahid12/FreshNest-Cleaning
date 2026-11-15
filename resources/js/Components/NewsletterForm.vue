<script setup>
import { useForm } from '@inertiajs/vue3';
import Btn from '@/Components/Btn.vue';

defineProps({ dark: Boolean });
const form = useForm({ email: '' });
const submit = () => form.post('/newsletter', { preserveScroll: true, onSuccess: () => form.reset() });
</script>

<template>
    <form class="w-full" @submit.prevent="submit">
        <div class="flex flex-col gap-2 sm:flex-row">
            <label for="newsletter-email" class="sr-only">Email address</label>
            <input
                id="newsletter-email" v-model="form.email" type="email" required autocomplete="email" placeholder="Your email address"
                :class="['input flex-1', dark && 'border-white/20 bg-white/10 text-white placeholder-white/60']"
            >
            <Btn type="submit" :variant="dark ? 'outline' : 'primary'" :loading="form.processing">Subscribe</Btn>
        </div>
        <p v-if="form.errors.email" class="field-error">{{ form.errors.email }}</p>
    </form>
</template>
