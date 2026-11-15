<script setup>
import Modal from '@/Components/Modal.vue';
import Btn from '@/Components/Btn.vue';

// A modal wrapping a <form>. Parent handles @submit.
defineProps({
    show: Boolean, title: String, submitLabel: { type: String, default: 'Save' },
    processing: Boolean, maxWidth: { type: String, default: 'max-w-lg' },
});
defineEmits(['close', 'submit']);
</script>

<template>
    <Modal :show="show" :title="title" :max-width="maxWidth" @close="$emit('close')">
        <form class="space-y-4" @submit.prevent="$emit('submit')">
            <slot />
            <div class="flex justify-end gap-3 pt-2">
                <Btn variant="outline" @click="$emit('close')">Cancel</Btn>
                <Btn type="submit" :loading="processing">{{ submitLabel }}</Btn>
            </div>
        </form>
    </Modal>
</template>
