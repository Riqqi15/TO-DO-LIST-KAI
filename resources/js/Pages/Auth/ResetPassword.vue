<script setup>
import FieldError from '@/components/shared/FieldError.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
});

const form = useForm({
    email: props.email,
    token: props.token,
    password: '',
    password_confirmation: '',
});

const submit = () => form.post('/reset-password', {
    onFinish: () => form.reset('password', 'password_confirmation'),
});
</script>

<template>
    <Head title="Reset password" />
    <AuthLayout title="Reset password" description="Gunakan password baru minimal delapan karakter.">
        <form class="space-y-4" @submit.prevent="submit">
            <label class="block text-sm font-medium">Email<input v-model="form.email" type="email" required autocomplete="username" class="mt-1 w-full rounded-lg border px-3 py-2" /><FieldError :message="form.errors.email" /></label>
            <label class="block text-sm font-medium">Password baru<input v-model="form.password" type="password" required autocomplete="new-password" class="mt-1 w-full rounded-lg border px-3 py-2" /><FieldError :message="form.errors.password" /></label>
            <label class="block text-sm font-medium">Konfirmasi password<input v-model="form.password_confirmation" type="password" required autocomplete="new-password" class="mt-1 w-full rounded-lg border px-3 py-2" /></label>
            <button class="w-full rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white disabled:opacity-50" :disabled="form.processing">Simpan password</button>
        </form>
    </AuthLayout>
</template>
