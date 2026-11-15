<script setup>
import Modal from '@/Components/Modal.vue';
import Btn from '@/Components/Btn.vue';

defineProps({
    show: Boolean,
    title: { type: String, default: 'Are you sure?' },
    message: String,
    confirmLabel: { type: String, default: 'Confirm' },
    danger: Boolean,
    processing: Boolean,
});
defineEmits(['confirm', 'close']);
</script>

<template>
    <Modal :show="show" :title="title" max-width="max-w-md" @close="$emit('close')">
        <p class="text-sm text-body"><slot>{{ message }}</slot></p>
        <div class="mt-6 flex justify-end gap-3">
            <Btn variant="outline" @click="$emit('close')">Cancel</Btn>
            <Btn :variant="danger ? 'danger' : 'primary'" :loading="processing" @click="$emit('confirm')">{{ confirmLabel }}</Btn>
        </div>
    </Modal>
</template>
