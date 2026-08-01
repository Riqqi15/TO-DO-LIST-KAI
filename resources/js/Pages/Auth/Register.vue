<script setup>
import FieldError from '@/components/shared/FieldError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight, LoaderCircle } from '@lucide/vue';

const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });
const submit = () => form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <Head title="Daftar" />
    <AuthLayout title="Buat akun baru" description="Verifikasi email Anda untuk mengaktifkan workspace personal.">
        <form class="space-y-4" @submit.prevent="submit">
            <div class="space-y-2"><Label for="name">Nama lengkap</Label><Input id="name" v-model="form.name" autocomplete="name" placeholder="Nama Anda" required autofocus class="h-11" :aria-invalid="Boolean(form.errors.name)" /><FieldError :message="form.errors.name" /></div>
            <div class="space-y-2"><Label for="email">Email</Label><Input id="email" v-model="form.email" type="email" autocomplete="username" placeholder="nama@contoh.com" required class="h-11" :aria-invalid="Boolean(form.errors.email)" /><FieldError :message="form.errors.email" /></div>
            <div class="space-y-2"><Label for="password">Password</Label><Input id="password" v-model="form.password" type="password" autocomplete="new-password" required class="h-11" :aria-invalid="Boolean(form.errors.password)" /><FieldError :message="form.errors.password" /></div>
            <div class="space-y-2"><Label for="password-confirmation">Konfirmasi password</Label><Input id="password-confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" required class="h-11" /></div>
            <Button class="mt-2 h-11 w-full font-bold" :disabled="form.processing"><LoaderCircle v-if="form.processing" class="size-4 animate-spin" />Buat akun<ArrowRight v-if="!form.processing" class="size-4" /></Button>
        </form>
        <p class="mt-7 text-center text-sm text-muted-foreground">Sudah punya akun? <Link href="/login" class="font-bold text-primary hover:underline">Masuk</Link></p>
    </AuthLayout>
</template>
