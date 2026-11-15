<script setup>
import { ref } from 'vue';
import api, { errorMessage } from '@/Services/api';
import Btn from '@/Components/Btn.vue';

const zip = ref('');
const result = ref(null); // { ok: boolean, message: string }
const loading = ref(false);

const check = async () => {
    result.value = null;
    if (zip.value.trim().length < 2) {
        result.value = { ok: false, message: 'Enter your ZIP / postal code or city.' };
        return;
    }
    loading.value = true;
    try {
        const { data } = await api.get('/api/check-area', { params: { q: zip.value.trim() } });
        result.value = data.served
            ? { ok: true, message: 'Great news, we serve your area!' }
            : { ok: false, message: 'We do not serve that location yet. Contact us and we will let you know when we expand.' };
    } catch (e) {
        result.value = { ok: false, message: errorMessage(e, 'Could not check availability right now.') };
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <form class="w-full max-w-md" @submit.prevent="check">
        <label for="zip-check" class="label">Check your city or ZIP / postal code</label>
        <div class="flex gap-2">
            <input id="zip-check" v-model="zip" class="input" maxlength="100" autocomplete="off" placeholder="City or ZIP, e.g. Lahore or 78701">
            <Btn type="submit" :loading="loading">Check</Btn>
        </div>
        <p v-if="result" :class="['mt-2 text-sm font-medium', result.ok ? 'text-brand' : 'text-red-600']" role="status">{{ result.message }}</p>
    </form>
</template>
