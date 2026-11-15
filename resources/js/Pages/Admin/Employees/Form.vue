<script setup>
import { useForm } from '@inertiajs/vue3';
import Btn from '@/Components/Btn.vue';
import FormCheckbox from '@/Components/FormCheckbox.vue';
import FormInput from '@/Components/FormInput.vue';
import FormTextarea from '@/Components/FormTextarea.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Seo from '@/Components/Seo.vue';

const props = defineProps({ employee: Object, services: Array });
const e = props.employee;
const form = useForm({
    name: e?.name ?? '', email: e?.email ?? '', phone: e?.phone ?? '', password: '', is_active: e?.is_active ?? true,
    job_title: e?.job_title ?? '', bio: e?.bio ?? '', skills: e?.skills ?? [], working_days: e?.working_days ?? [1, 2, 3, 4, 5],
    work_start: e?.work_start ?? '08:00', work_end: e?.work_end ?? '17:00', emergency_contact: e?.emergency_contact ?? '', is_available: e?.is_available ?? true,
});
const days = [[1, 'Mon'], [2, 'Tue'], [3, 'Wed'], [4, 'Thu'], [5, 'Fri'], [6, 'Sat'], [7, 'Sun']];
const toggle = (list, v) => { const i = list.indexOf(v); i === -1 ? list.push(v) : list.splice(i, 1); };
const submit = () => (e ? form.put(`/admin/employees/${e.id}`) : form.post('/admin/employees'));
</script>

<template>
    <Seo :title="employee ? 'Edit employee' : 'Add employee'" noindex />
    <PageHeader :title="employee ? 'Edit employee' : 'Add employee'" />
    <form class="card card-pad max-w-3xl space-y-5" @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-2">
            <FormInput v-model="form.name" label="Full name" required :error="form.errors.name" />
            <FormInput v-model="form.job_title" label="Job title" :error="form.errors.job_title" />
            <FormInput v-model="form.email" label="Email" type="email" required :error="form.errors.email" />
            <FormInput v-model="form.phone" label="Phone" type="tel" :error="form.errors.phone" />
            <FormInput v-model="form.password" :label="employee ? 'New password (leave blank to keep)' : 'Temporary password'" type="password" autocomplete="new-password" :required="!employee" hint="Min 10 characters, mixed case and a number. They must change it at first sign-in." :error="form.errors.password" />
            <FormInput v-model="form.emergency_contact" label="Emergency contact" :error="form.errors.emergency_contact" />
        </div>
        <FormTextarea v-model="form.bio" label="Bio" rows="3" :error="form.errors.bio" />

        <fieldset>
            <legend class="label">Working days</legend>
            <div class="flex flex-wrap gap-2">
                <label v-for="[v, l] in days" :key="v" :class="['cursor-pointer rounded-lg border px-3 py-1.5 text-sm', form.working_days.includes(v) ? 'border-brand bg-brand text-white' : 'border-gray-200']">
                    <input type="checkbox" class="sr-only" :checked="form.working_days.includes(v)" @change="toggle(form.working_days, v)">{{ l }}
                </label>
            </div>
            <p v-if="form.errors.working_days" class="field-error">{{ form.errors.working_days }}</p>
        </fieldset>
        <div class="grid gap-4 sm:grid-cols-2">
            <FormInput v-model="form.work_start" label="Start time" type="time" required :error="form.errors.work_start" />
            <FormInput v-model="form.work_end" label="End time" type="time" required :error="form.errors.work_end" />
        </div>

        <fieldset>
            <legend class="label">Services this cleaner can perform <span class="font-normal text-gray-500">(none selected = all)</span></legend>
            <div class="grid gap-2 sm:grid-cols-2">
                <label v-for="s in services" :key="s.id" class="flex items-center gap-2 text-sm"><input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand" :checked="form.skills.includes(s.id)" @change="toggle(form.skills, s.id)">{{ s.name }}</label>
            </div>
        </fieldset>

        <div class="space-y-2"><FormCheckbox v-model="form.is_available" label="Available for new assignments" /><FormCheckbox v-model="form.is_active" label="Account active (can sign in)" /></div>
        <div class="flex gap-3"><Btn type="submit" :loading="form.processing">Save employee</Btn><Btn :href="employee ? `/admin/employees/${employee.id}` : '/admin/employees'" variant="outline">Cancel</Btn></div>
    </form>
</template>
