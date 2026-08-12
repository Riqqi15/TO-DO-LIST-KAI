<script setup>
import StatusAlert from '@/components/shared/StatusAlert.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Button } from '@/components/ui/button';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle, LogOut, MailCheck } from '@lucide/vue';

defineProps({ status: { type: String, default: '' } });
const resend = useForm({});
</script>

<template>
    <Head title="Verifikasi email" />
    <AuthLayout title="Periksa email Anda" description="Klik tautan verifikasi yang kami kirim. Untuk demo lokal, buka inbox Mailpit.">
        <div class="mb-6 grid size-14 place-items-center rounded-2xl bg-secondary text-primary"><MailCheck class="size-7" /></div>
        <StatusAlert :status="status" />
        <Button class="h-11 w-full font-bold" :disabled="resend.processing" @click="resend.post('/email/verification-notification')"><LoaderCircle v-if="resend.processing" class="size-4 animate-spin" />Kirim ulang email</Button>
        <Button variant="ghost" class="mt-3 w-full text-muted-foreground" as-child><Link href="/logout" method="post" as="button"><LogOut class="size-4" />Keluar dari akun</Link></Button>
    </AuthLayout>
</template>
