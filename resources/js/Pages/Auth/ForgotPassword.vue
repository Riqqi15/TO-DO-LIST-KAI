<script setup>
import FieldError from '@/components/shared/FieldError.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({ status: { type: String, default: '' } });
const form = useForm({ email: '' });
const submit = () => form.post('/forgot-password');
</script>

<template>
    <Head title="Lupa password" />
    <AuthLayout title="Lupa password" description="Kami akan mengirim tautan reset ke Mailpit.">
        <p v-if="status" class="mb-4 text-sm text-green-700">{{ status }}</p>
        <form class="space-y-4" @submit.prevent="submit">
            <label class="block text-sm font-medium">Email<input v-model="form.email" type="email" required autofocus class="mt-1 w-full rounded-lg border px-3 py-2" /><FieldError :message="form.errors.email" /></label>
            <button class="w-full rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white disabled:opacity-50" :disabled="form.processing">Kirim tautan reset</button>
        </form>
        <Link href="/login" class="mt-4 block text-center text-sm text-blue-700">Kembali ke login</Link>
    </AuthLayout>
</template>
