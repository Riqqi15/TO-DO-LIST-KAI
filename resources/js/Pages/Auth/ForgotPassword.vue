<script setup>
import FieldError from '@/components/shared/FieldError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, LoaderCircle, Mail } from '@lucide/vue';

defineProps({ status: { type: String, default: '' } });
const form = useForm({ email: '' });
const submit = () => form.post('/forgot-password');
</script>

<template>
    <Head title="Lupa password" />
    <AuthLayout title="Atur ulang password" description="Masukkan email akun. Kami akan mengirim tautan untuk membuat password baru.">
        <div v-if="status" class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm font-medium text-emerald-800">{{ status }}</div>
        <form class="space-y-5" @submit.prevent="submit">
            <div class="space-y-2"><Label for="email">Email</Label><Input id="email" v-model="form.email" type="email" autocomplete="username" placeholder="nama@contoh.com" required autofocus class="h-11" :aria-invalid="Boolean(form.errors.email)" /><FieldError :message="form.errors.email" /></div>
            <Button class="h-11 w-full font-bold" :disabled="form.processing"><LoaderCircle v-if="form.processing" class="size-4 animate-spin" /><Mail v-else class="size-4" />Kirim tautan reset</Button>
        </form>
        <Button variant="ghost" class="mt-5 w-full text-muted-foreground" as-child><Link href="/login"><ArrowLeft class="size-4" />Kembali ke halaman masuk</Link></Button>
    </AuthLayout>
</template>
