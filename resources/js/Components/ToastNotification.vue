<script setup>
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import Icon from '@/Components/Icon.vue';
import { useToastStore } from '@/Stores/toast';

const toast = useToastStore();
const page = usePage();

// Server flash messages (redirect()->with('success'|'error', ...)) become toasts.
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    },
    { immediate: true },
);
</script>

<template>
    <div class="pointer-events-none fixed inset-x-0 top-4 z-[60] flex flex-col items-center gap-2 px-4" aria-live="polite">
        <TransitionGroup enter-active-class="transition duration-200" enter-from-class="-translate-y-2 opacity-0" leave-active-class="transition duration-150" leave-to-class="opacity-0">
            <div
                v-for="t in toast.items" :key="t.id" role="status"
                :class="['pointer-events-auto flex w-full max-w-md items-start gap-3 rounded-xl px-4 py-3 text-sm shadow-lg', t.type === 'error' ? 'bg-red-600 text-white' : 'bg-ink text-white']"
            >
                <Icon :name="t.type === 'error' ? 'warning' : 'check'" class="mt-0.5 h-5 w-5" />
                <p class="flex-1">{{ t.message }}</p>
                <button type="button" class="opacity-70 hover:opacity-100" aria-label="Dismiss" @click="toast.dismiss(t.id)"><Icon name="x" class="h-4 w-4" /></button>
            </div>
        </TransitionGroup>
    </div>
</template>
