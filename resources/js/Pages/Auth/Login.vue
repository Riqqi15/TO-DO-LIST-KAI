<script setup>
import FieldError from '@/components/shared/FieldError.vue';
import StatusAlert from '@/components/shared/StatusAlert.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight, LoaderCircle } from '@lucide/vue';

defineProps({ status: { type: String, default: '' } });

const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post('/login', { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="Masuk" />
    <AuthLayout title="Masuk ke workspace" description="Kelola task pribadi dan tim dari satu dashboard.">
        <StatusAlert :status="status" />
        <form class="space-y-5" @submit.prevent="submit">
            <div class="space-y-2">
                <Label for="email">Email</Label>
                <Input id="email" v-model="form.email" type="email" autocomplete="username" placeholder="nama@contoh.com" required autofocus :aria-invalid="Boolean(form.errors.email)" class="h-11" />
                <FieldError :message="form.errors.email" />
            </div>
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <Label for="password">Password</Label>
                    <Link href="/forgot-password" class="text-xs font-bold text-primary hover:underline">Lupa password?</Link>
                </div>
                <Input id="password" v-model="form.password" type="password" autocomplete="current-password" required :aria-invalid="Boolean(form.errors.password)" class="h-11" />
                <FieldError :message="form.errors.password" />
            </div>
            <div class="flex items-center gap-2.5">
                <Checkbox id="remember" v-model="form.remember" />
                <Label for="remember" class="font-medium text-muted-foreground">Tetap masuk di perangkat ini</Label>
            </div>
            <Button class="h-11 w-full font-bold" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                Masuk
                <ArrowRight v-if="!form.processing" class="size-4" />
            </Button>
        </form>
        <p class="mt-7 text-center text-sm text-muted-foreground">Belum punya akun? <Link href="/register" class="font-bold text-primary hover:underline">Daftar sekarang</Link></p>
    </AuthLayout>
</template>
