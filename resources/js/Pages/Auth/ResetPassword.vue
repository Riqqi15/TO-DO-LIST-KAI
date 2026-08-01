<script setup>
import FieldError from '@/components/shared/FieldError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, LockKeyhole } from '@lucide/vue';

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
    <AuthLayout title="Buat password baru" description="Gunakan minimal delapan karakter yang tidak mudah ditebak.">
        <form class="space-y-4" @submit.prevent="submit">
            <div class="space-y-2"><Label for="email">Email</Label><Input id="email" v-model="form.email" type="email" required autocomplete="username" class="h-11" :aria-invalid="Boolean(form.errors.email)" /><FieldError :message="form.errors.email" /></div>
            <div class="space-y-2"><Label for="password">Password baru</Label><Input id="password" v-model="form.password" type="password" required autocomplete="new-password" class="h-11" :aria-invalid="Boolean(form.errors.password)" /><FieldError :message="form.errors.password" /></div>
            <div class="space-y-2"><Label for="password-confirmation">Konfirmasi password</Label><Input id="password-confirmation" v-model="form.password_confirmation" type="password" required autocomplete="new-password" class="h-11" /></div>
            <Button class="mt-2 h-11 w-full font-bold" :disabled="form.processing"><LoaderCircle v-if="form.processing" class="size-4 animate-spin" /><LockKeyhole v-else class="size-4" />Simpan password</Button>
        </form>
    </AuthLayout>
</template>
