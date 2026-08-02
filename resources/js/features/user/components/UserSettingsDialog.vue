<script setup>
import FieldError from '@/components/shared/FieldError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useForm } from '@inertiajs/vue3';
import { KeyRound, LoaderCircle, User } from '@lucide/vue';
import { ref, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    user: { type: Object, default: () => null },
});

const emit = defineEmits(['update:open']);

const activeTab = ref('profile');

const profileForm = useForm({
    name: '',
    email: '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

watch(() => props.open, (isOpen) => {
    if (isOpen && props.user) {
        profileForm.name = props.user.name ?? '';
        profileForm.email = props.user.email ?? '';
        profileForm.clearErrors();
        passwordForm.reset();
        passwordForm.clearErrors();
    }
}, { immediate: true });

const submitProfile = () => {
    profileForm.put('/profile', {
        preserveScroll: true,
        onSuccess: () => {
            profileForm.clearErrors();
        },
    });
};

const submitPassword = () => {
    passwordForm.put('/profile/password', {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            passwordForm.clearErrors();
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Pengaturan Akun</DialogTitle>
                <DialogDescription>
                    Kelola nama profil, alamat email, dan kata sandi akun Anda.
                </DialogDescription>
            </DialogHeader>

            <Tabs v-model="activeTab" class="mt-2 w-full">
                <TabsList class="grid w-full grid-cols-2">
                    <TabsTrigger value="profile" class="gap-2">
                        <User class="size-4" />
                        <span>Profil</span>
                    </TabsTrigger>
                    <TabsTrigger value="password" class="gap-2">
                        <KeyRound class="size-4" />
                        <span>Kata Sandi</span>
                    </TabsTrigger>
                </TabsList>

                <!-- Profile Tab -->
                <TabsContent value="profile" class="mt-4 space-y-4">
                    <form id="profile-form" class="space-y-4" @submit.prevent="submitProfile">
                        <div class="space-y-2">
                            <Label for="user-name">Nama lengkap / Username</Label>
                            <Input
                                id="user-name"
                                v-model="profileForm.name"
                                type="text"
                                required
                                maxlength="255"
                                placeholder="Masukkan nama..."
                                :aria-invalid="Boolean(profileForm.errors.name)"
                            />
                            <FieldError :message="profileForm.errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="user-email">Alamat Email</Label>
                            <Input
                                id="user-email"
                                v-model="profileForm.email"
                                type="email"
                                required
                                maxlength="255"
                                placeholder="email@domain.com"
                                :aria-invalid="Boolean(profileForm.errors.email)"
                            />
                            <FieldError :message="profileForm.errors.email" />
                        </div>

                        <div class="flex justify-end pt-2">
                            <Button type="submit" :disabled="profileForm.processing">
                                <LoaderCircle v-if="profileForm.processing" class="size-4 animate-spin" />
                                Simpan Profil
                            </Button>
                        </div>
                    </form>
                </TabsContent>

                <!-- Password Tab -->
                <TabsContent value="password" class="mt-4 space-y-4">
                    <form id="password-form" class="space-y-4" @submit.prevent="submitPassword">
                        <div class="space-y-2">
                            <Label for="current-password">Kata Sandi Saat Ini</Label>
                            <Input
                                id="current-password"
                                v-model="passwordForm.current_password"
                                type="password"
                                required
                                placeholder="••••••••"
                                :aria-invalid="Boolean(passwordForm.errors.current_password)"
                            />
                            <FieldError :message="passwordForm.errors.current_password" />
                        </div>

                        <div class="space-y-2">
                            <Label for="new-password">Kata Sandi Baru</Label>
                            <Input
                                id="new-password"
                                v-model="passwordForm.password"
                                type="password"
                                required
                                placeholder="Minimal 8 karakter"
                                :aria-invalid="Boolean(passwordForm.errors.password)"
                            />
                            <FieldError :message="passwordForm.errors.password" />
                        </div>

                        <div class="space-y-2">
                            <Label for="confirm-password">Konfirmasi Kata Sandi Baru</Label>
                            <Input
                                id="confirm-password"
                                v-model="passwordForm.password_confirmation"
                                type="password"
                                required
                                placeholder="Ketik ulang kata sandi baru"
                                :aria-invalid="Boolean(passwordForm.errors.password_confirmation)"
                            />
                            <FieldError :message="passwordForm.errors.password_confirmation" />
                        </div>

                        <div class="flex justify-end pt-2">
                            <Button type="submit" :disabled="passwordForm.processing">
                                <LoaderCircle v-if="passwordForm.processing" class="size-4 animate-spin" />
                                Perbarui Kata Sandi
                            </Button>
                        </div>
                    </form>
                </TabsContent>
            </Tabs>
        </DialogContent>
    </Dialog>
</template>
