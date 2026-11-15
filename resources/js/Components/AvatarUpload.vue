<script setup>
import { router, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';
import Avatar from '@/Components/Avatar.vue';
import Btn from '@/Components/Btn.vue';

// Profile photo uploader. Works for every role (POST /profile/avatar). user: { name, avatar_url }
const props = defineProps({ user: { type: Object, required: true } });

const form = useForm({ avatar: null });
const input = ref(null);
const preview = ref(null);
const localError = ref('');
const shown = computed(() => ({ name: props.user.name, avatar_url: preview.value || props.user.avatar_url }));

function revoke() { if (preview.value) { URL.revokeObjectURL(preview.value); preview.value = null; } }
function cancel() { revoke(); form.reset(); form.clearErrors(); localError.value = ''; if (input.value) input.value.value = ''; }

function pick(e) {
    const file = e.target.files?.[0];
    cancel();
    if (!file) return;
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) { localError.value = 'Please choose a JPG, PNG or WebP image.'; e.target.value = ''; return; }
    if (file.size > 2 * 1024 * 1024) { localError.value = 'The photo is too large (maximum 2 MB).'; e.target.value = ''; return; }
    form.avatar = file;
    preview.value = URL.createObjectURL(file);
}
const upload = () => form.post('/profile/avatar', { forceFormData: true, preserveScroll: true, onSuccess: cancel });
const remove = () => router.delete('/profile/avatar', { preserveScroll: true });
onBeforeUnmount(revoke);
</script>

<template>
    <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-center">
        <Avatar :user="shown" size="xl" />
        <div class="space-y-2">
            <p class="font-medium text-ink">Profile photo</p>
            <p class="text-xs text-gray-500">JPG, PNG or WebP, up to 2 MB. Shown in the header next to your name.</p>
            <div class="flex flex-wrap items-center gap-2">
                <label class="btn btn-outline btn-sm cursor-pointer">
                    Choose photo
                    <input ref="input" type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" @change="pick">
                </label>
                <Btn v-if="form.avatar" size="sm" :loading="form.processing" @click="upload">Save photo</Btn>
                <Btn v-if="form.avatar" size="sm" variant="ghost" @click="cancel">Cancel</Btn>
                <Btn v-else-if="user.avatar_url" size="sm" variant="ghost" @click="remove">Remove</Btn>
            </div>
            <p v-if="localError || form.errors.avatar" class="field-error" role="alert">{{ localError || form.errors.avatar }}</p>
        </div>
    </div>
</template>
