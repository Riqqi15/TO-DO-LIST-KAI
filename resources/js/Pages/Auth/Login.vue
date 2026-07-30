<script setup>
import FieldError from '@/components/shared/FieldError.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({ status: { type: String, default: '' } });

const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post('/login', { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="Masuk" />
    <AuthLayout title="Masuk" description="Gunakan akun yang sudah terdaftar.">
        <p v-if="status" class="mb-4 text-sm text-green-700">{{ status }}</p>
        <form class="space-y-4" @submit.prevent="submit">
            <label class="block text-sm font-medium">
                Email
                <input v-model="form.email" type="email" autocomplete="username" required autofocus class="mt-1 w-full rounded-lg border px-3 py-2" />
                <FieldError :message="form.errors.email" />
            </label>
            <label class="block text-sm font-medium">
                Password
                <input v-model="form.password" type="password" autocomplete="current-password" required class="mt-1 w-full rounded-lg border px-3 py-2" />
                <FieldError :message="form.errors.password" />
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input v-model="form.remember" type="checkbox" />
                Ingat saya
            </label>
            <button class="w-full rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white disabled:opacity-50" :disabled="form.processing">Masuk</button>
        </form>
        <div class="mt-4 flex justify-between text-sm">
            <Link href="/forgot-password" class="text-blue-700">Lupa password?</Link>
            <Link href="/register" class="text-blue-700">Daftar</Link>
        </div>
    </AuthLayout>
</template>
