<script setup>
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({ status: { type: String, default: '' } });
const resend = useForm({});
</script>

<template>
    <Head title="Verifikasi email" />
    <AuthLayout title="Verifikasi email" description="Buka Mailpit, lalu klik tautan verifikasi yang kami kirim.">
        <p v-if="status" class="mb-4 text-sm text-green-700">{{ status }}</p>
        <button class="w-full rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white" @click="resend.post('/email/verification-notification')">Kirim ulang email</button>
        <Link href="/logout" method="post" as="button" class="mt-4 w-full text-sm text-slate-600">Keluar</Link>
    </AuthLayout>
</template>
