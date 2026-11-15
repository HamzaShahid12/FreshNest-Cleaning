<script setup>
import { router, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';
import Btn from '@/Components/Btn.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';

// One editable image (logo or page banner) in Admin → Branding.
const props = defineProps({ item: { type: Object, required: true } });

const form = useForm({ image: null });
const input = ref(null);
const preview = ref(null);
const localError = ref('');
const confirmReset = ref(false);
const resetting = ref(false);
const isLogo = computed(() => props.item.kind === 'logo');
const maxMb = computed(() => (isLogo.value ? 2 : 4));
const current = computed(() => preview.value || props.item.url);
const broken = ref(false);

function revoke() { if (preview.value) { URL.revokeObjectURL(preview.value); preview.value = null; } }
function cancel() { revoke(); form.reset(); form.clearErrors(); localError.value = ''; if (input.value) input.value.value = ''; }
function pick(e) {
    const file = e.target.files?.[0];
    cancel();
    if (!file) return;
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) { localError.value = 'Please choose a JPG, PNG or WebP image.'; e.target.value = ''; return; }
    if (file.size > maxMb.value * 1024 * 1024) { localError.value = `The image is too large (maximum ${maxMb.value} MB).`; e.target.value = ''; return; }
    form.image = file;
    preview.value = URL.createObjectURL(file);
    broken.value = false;
}
const upload = () => form.post(`/admin/branding/${props.item.key}`, { forceFormData: true, preserveScroll: true, onSuccess: cancel });
const reset = () => {
    resetting.value = true;
    router.delete(`/admin/branding/${props.item.key}`, { preserveScroll: true, onFinish: () => { resetting.value = false; confirmReset.value = false; broken.value = false; } });
};
onBeforeUnmount(revoke);
</script>

<template>
    <div class="card overflow-hidden">
        <div :class="isLogo ? 'flex h-40 items-center justify-center bg-gray-100' : 'aspect-[16/5] bg-brand-soft'">
            <img v-if="current && !broken" :src="current" :alt="`${item.label} preview`" :class="isLogo ? 'max-h-24 max-w-[80%] object-contain' : 'h-full w-full object-cover'" @error="broken = true">
            <p v-else class="px-4 text-center text-sm text-gray-500">{{ isLogo ? 'No logo uploaded. The default mark and business name are shown.' : 'No image' }}</p>
        </div>
        <div class="space-y-3 p-4">
            <div class="flex items-center justify-between gap-2">
                <h2 class="font-display text-base font-semibold text-ink">{{ item.label }}</h2>
                <span :class="['badge', item.custom ? 'bg-brand-soft text-brand-dark' : 'bg-gray-100 text-gray-600']">{{ item.custom ? 'Custom image' : 'Default' }}</span>
            </div>
            <p class="text-xs text-gray-500">{{ item.hint }}</p>
            <div class="flex flex-wrap items-center gap-2">
                <label class="btn btn-outline btn-sm cursor-pointer">Choose image<input ref="input" type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" @change="pick"></label>
                <Btn v-if="form.image" size="sm" :loading="form.processing" @click="upload">Save</Btn>
                <Btn v-if="form.image" size="sm" variant="ghost" @click="cancel">Cancel</Btn>
                <Btn v-else-if="item.custom" size="sm" variant="ghost" @click="confirmReset = true">Reset to default</Btn>
            </div>
            <p v-if="localError || form.errors.image" class="field-error" role="alert">{{ localError || form.errors.image }}</p>
        </div>
        <ConfirmationModal :show="confirmReset" title="Reset to default?" :message="`The custom ${item.label.toLowerCase()} image will be deleted and the default will be used.`" confirm-label="Reset" danger :processing="resetting" @close="confirmReset = false" @confirm="reset" />
    </div>
</template>
