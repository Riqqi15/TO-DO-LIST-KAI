<script setup>
import FieldError from '@/components/shared/FieldError.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });
const submit = () => form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <Head title="Daftar" />
    <AuthLayout title="Buat akun" description="Verifikasi email diperlukan sebelum memakai aplikasi.">
        <form class="space-y-4" @submit.prevent="submit">
            <label class="block text-sm font-medium">Nama<input v-model="form.name" required autofocus class="mt-1 w-full rounded-lg border px-3 py-2" /><FieldError :message="form.errors.name" /></label>
            <label class="block text-sm font-medium">Email<input v-model="form.email" type="email" required class="mt-1 w-full rounded-lg border px-3 py-2" /><FieldError :message="form.errors.email" /></label>
            <label class="block text-sm font-medium">Password<input v-model="form.password" type="password" required class="mt-1 w-full rounded-lg border px-3 py-2" /><FieldError :message="form.errors.password" /></label>
            <label class="block text-sm font-medium">Konfirmasi password<input v-model="form.password_confirmation" type="password" required class="mt-1 w-full rounded-lg border px-3 py-2" /></label>
            <button class="w-full rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white disabled:opacity-50" :disabled="form.processing">Daftar</button>
        </form>
        <Link href="/login" class="mt-4 block text-center text-sm text-blue-700">Sudah punya akun?</Link>
    </AuthLayout>
</template>
