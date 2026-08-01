<script setup>
import FieldError from '@/components/shared/FieldError.vue';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Separator } from '@/components/ui/separator';
import { router, useForm } from '@inertiajs/vue3';
import { Check, Clipboard, FolderCog, KeyRound, LoaderCircle, Pencil, Plus, ShieldCheck, Trash2, UserPlus, Users } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps({
    workspace: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    user: { type: Object, default: null },
    invite: { type: Object, default: null },
});
const customCategories = computed(() => props.categories.filter((item) => !item.is_system));
const systemCategories = computed(() => props.categories.filter((item) => item.is_system));
const isTeam = computed(() => props.workspace.type === 'team');
const isOwner = computed(() => isTeam.value && Number(props.workspace.owner_id) === Number(props.user?.id));

const categoryForm = useForm({ name: '' });
const teamForm = useForm({ name: '' });
const joinForm = useForm({ code: '' });
const capacityForm = useForm({ member_limit: props.workspace.member_limit ?? 5 });
const deleteTeamForm = useForm({ confirmation: '' });
const editNames = ref({});
const categoryToDelete = ref(null);
const categoryDeleteOpen = ref(false);
const leaveOpen = ref(false);
const deleteTeamOpen = ref(false);

watch(() => props.workspace, (workspace) => { capacityForm.member_limit = workspace.member_limit ?? 5; deleteTeamForm.confirmation = ''; });
const createCategory = () => categoryForm.post(`/workspaces/${props.workspace.id}/categories`, { preserveScroll: true, onSuccess: () => categoryForm.reset() });
const updateCategory = (category) => router.patch(`/categories/${category.id}`, { name: editNames.value[category.id] ?? category.name }, { preserveScroll: true });
const askDeleteCategory = (category) => { categoryToDelete.value = category; categoryDeleteOpen.value = true; };
const deleteCategory = () => router.delete(`/categories/${categoryToDelete.value.id}`, { preserveScroll: true, onSuccess: () => { categoryDeleteOpen.value = false; } });
const createTeam = () => teamForm.post('/teams', { preserveScroll: true, onSuccess: () => teamForm.reset() });
const joinTeam = () => joinForm.post('/teams/join', { preserveScroll: true, onSuccess: () => joinForm.reset() });
const generateInvite = () => router.post(`/workspaces/${props.workspace.id}/invite`, {}, { preserveScroll: true });
const copyInvite = async () => { if (!props.invite?.code) return; await navigator.clipboard.writeText(props.invite.code); toast.success('Kode tim disalin.'); };
const updateCapacity = () => capacityForm.patch(`/workspaces/${props.workspace.id}/capacity`, { preserveScroll: true });
const leaveTeam = () => router.delete(`/workspaces/${props.workspace.id}/leave`, { onSuccess: () => { leaveOpen.value = false; } });
const deleteTeam = () => deleteTeamForm.delete(`/workspaces/${props.workspace.id}`, { onSuccess: () => { deleteTeamOpen.value = false; } });
</script>

<template>
    <div class="mx-auto max-w-5xl space-y-6">
        <div><h2 class="text-xl font-extrabold tracking-[-0.025em]">Pengaturan workspace</h2><p class="mt-1 text-sm text-muted-foreground">Kelola kategori, tim, dan akses untuk {{ workspace.name }}.</p></div>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card class="shadow-none">
                <CardHeader><div class="flex items-start justify-between gap-4"><div><CardTitle class="flex items-center gap-2 text-base"><FolderCog class="size-4 text-primary" />Kategori</CardTitle><CardDescription class="mt-1">Kategori sistem tersedia di semua workspace.</CardDescription></div><Badge variant="secondary">{{ categories.length }} kategori</Badge></div></CardHeader>
                <CardContent>
                    <form class="flex gap-2" @submit.prevent="createCategory"><div class="flex-1"><Label for="category-name" class="sr-only">Nama kategori baru</Label><Input id="category-name" v-model="categoryForm.name" placeholder="Nama kategori baru" maxlength="80" required :aria-invalid="Boolean(categoryForm.errors.name)" /><FieldError :message="categoryForm.errors.name" /></div><Button type="submit" size="icon" :disabled="categoryForm.processing" aria-label="Tambah kategori"><LoaderCircle v-if="categoryForm.processing" class="size-4 animate-spin" /><Plus v-else class="size-4" /></Button></form>
                    <div class="mt-5 space-y-2"><div v-for="category in systemCategories" :key="category.id" class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2.5"><span class="text-sm font-semibold">{{ category.name }}</span><Badge variant="outline" class="text-[9px]">Sistem</Badge></div><div v-for="category in customCategories" :key="category.id" class="flex items-center gap-2 rounded-xl border px-2 py-2"><Input v-model="editNames[category.id]" :placeholder="category.name" class="h-8 flex-1 border-0 px-2 shadow-none focus-visible:ring-0" /><Button variant="ghost" size="icon-sm" aria-label="Simpan nama kategori" @click="updateCategory(category)"><Check class="size-3.5" /></Button><Button variant="ghost" size="icon-sm" class="text-destructive" aria-label="Hapus kategori" @click="askDeleteCategory(category)"><Trash2 class="size-3.5" /></Button></div><p v-if="customCategories.length === 0" class="py-3 text-center text-xs text-muted-foreground">Belum ada kategori custom.</p></div>
                </CardContent>
            </Card>

            <Card class="shadow-none">
                <CardHeader><CardTitle class="flex items-center gap-2 text-base"><Users class="size-4 text-primary" />Workspace tim</CardTitle><CardDescription>Buat ruang kerja baru atau bergabung dengan kode.</CardDescription></CardHeader>
                <CardContent class="space-y-5">
                    <form class="space-y-2" @submit.prevent="createTeam"><Label for="team-name">Buat tim baru</Label><div class="flex gap-2"><Input id="team-name" v-model="teamForm.name" placeholder="Contoh: Product Team" maxlength="100" required :aria-invalid="Boolean(teamForm.errors.name)" /><Button type="submit" :disabled="teamForm.processing"><LoaderCircle v-if="teamForm.processing" class="size-4 animate-spin" /><Plus v-else class="size-4" />Buat</Button></div><FieldError :message="teamForm.errors.name" /></form>
                    <Separator />
                    <form class="space-y-2" @submit.prevent="joinTeam"><Label for="join-code">Gabung dengan kode</Label><div class="flex gap-2"><Input id="join-code" v-model="joinForm.code" class="font-mono uppercase tracking-[0.15em]" placeholder="XXXXXXXX" maxlength="8" required :aria-invalid="Boolean(joinForm.errors.code)" /><Button type="submit" variant="outline" :disabled="joinForm.processing"><LoaderCircle v-if="joinForm.processing" class="size-4 animate-spin" /><UserPlus v-else class="size-4" />Gabung</Button></div><FieldError :message="joinForm.errors.code" /></form>
                </CardContent>
            </Card>
        </div>

        <Card v-if="isTeam" class="shadow-none">
            <CardHeader><div class="flex items-start justify-between gap-4"><div><CardTitle class="flex items-center gap-2 text-base"><ShieldCheck class="size-4 text-primary" />Kelola {{ workspace.name }}</CardTitle><CardDescription>{{ isOwner ? 'Anda adalah owner workspace ini.' : 'Anda adalah anggota workspace ini.' }}</CardDescription></div><Badge :variant="isOwner ? 'default' : 'secondary'">{{ isOwner ? 'Owner' : 'Anggota' }}</Badge></div></CardHeader>
            <CardContent>
                <div v-if="isOwner" class="grid gap-5 md:grid-cols-2">
                    <div class="rounded-2xl border p-4"><div class="flex items-start gap-3"><div class="grid size-9 place-items-center rounded-xl bg-secondary text-primary"><KeyRound class="size-4" /></div><div><h3 class="text-sm font-extrabold">Kode undangan</h3><p class="mt-0.5 text-xs text-muted-foreground">Kode berlaku lima menit dan dapat dipakai beberapa anggota.</p></div></div><div v-if="invite?.code" class="mt-4 flex items-center gap-2 rounded-xl bg-slate-50 p-3"><code class="flex-1 text-center font-mono text-lg font-bold tracking-[0.2em]">{{ invite.code }}</code><Button variant="outline" size="icon-sm" aria-label="Salin kode" @click="copyInvite"><Clipboard class="size-4" /></Button></div><Button class="mt-4 w-full" variant="outline" @click="generateInvite">Buat kode baru</Button></div>
                    <div class="rounded-2xl border p-4"><div class="flex items-start gap-3"><div class="grid size-9 place-items-center rounded-xl bg-secondary text-primary"><Users class="size-4" /></div><div><h3 class="text-sm font-extrabold">Kapasitas anggota</h3><p class="mt-0.5 text-xs text-muted-foreground">Owner ikut dihitung dalam kapasitas.</p></div></div><form class="mt-4 flex gap-2" @submit.prevent="updateCapacity"><NativeSelect v-model="capacityForm.member_limit" class="h-10 flex-1"><NativeSelectOption :value="5">5 anggota</NativeSelectOption><NativeSelectOption :value="10">10 anggota</NativeSelectOption></NativeSelect><Button type="submit" :disabled="capacityForm.processing">Simpan</Button></form><FieldError :message="capacityForm.errors.member_limit" /></div>
                </div>
                <div v-else class="flex flex-col gap-4 rounded-2xl border p-4 sm:flex-row sm:items-center sm:justify-between"><div><h3 class="text-sm font-extrabold">Keluar dari tim</h3><p class="mt-1 text-xs text-muted-foreground">Data yang pernah Anda buat tetap berada di workspace.</p></div><Button variant="outline" @click="leaveOpen = true">Keluar dari tim</Button></div>

                <div v-if="isOwner" class="mt-5 rounded-2xl border border-red-200 bg-red-50/45 p-4"><div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><h3 class="text-sm font-extrabold text-red-800">Hapus tim permanen</h3><p class="mt-1 text-xs text-red-700/80">Seluruh data operasional tim akan dihapus. Activity log tetap menjadi arsip audit.</p></div><Button variant="destructive" @click="deleteTeamOpen = true"><Trash2 class="size-4" />Hapus tim</Button></div></div>
            </CardContent>
        </Card>

        <AlertDialog v-model:open="categoryDeleteOpen"><AlertDialogContent><AlertDialogHeader><AlertDialogTitle>Hapus kategori {{ categoryToDelete?.name }}?</AlertDialogTitle><AlertDialogDescription>Kategori yang masih dipakai task tidak dapat dihapus. Backend akan memeriksanya.</AlertDialogDescription></AlertDialogHeader><AlertDialogFooter><AlertDialogCancel>Batal</AlertDialogCancel><AlertDialogAction class="bg-destructive text-white hover:bg-destructive/90" @click="deleteCategory">Hapus kategori</AlertDialogAction></AlertDialogFooter></AlertDialogContent></AlertDialog>
        <AlertDialog v-model:open="leaveOpen"><AlertDialogContent><AlertDialogHeader><AlertDialogTitle>Keluar dari {{ workspace.name }}?</AlertDialogTitle><AlertDialogDescription>Anda akan kehilangan akses ke task dan catatan tim ini. Owner harus memindahkan ownership sebelum keluar.</AlertDialogDescription></AlertDialogHeader><AlertDialogFooter><AlertDialogCancel>Batal</AlertDialogCancel><AlertDialogAction @click="leaveTeam">Keluar dari tim</AlertDialogAction></AlertDialogFooter></AlertDialogContent></AlertDialog>
        <AlertDialog v-model:open="deleteTeamOpen"><AlertDialogContent><AlertDialogHeader><AlertDialogTitle>Hapus tim secara permanen?</AlertDialogTitle><AlertDialogDescription>Ketik persis <strong>konfirmasi hapus tim {{ workspace.name }}</strong> untuk melanjutkan.</AlertDialogDescription></AlertDialogHeader><div class="space-y-2"><Label for="delete-team-confirmation">Konfirmasi</Label><Input id="delete-team-confirmation" v-model="deleteTeamForm.confirmation" :placeholder="`konfirmasi hapus tim ${workspace.name}`" :aria-invalid="Boolean(deleteTeamForm.errors.confirmation)" /><FieldError :message="deleteTeamForm.errors.confirmation" /></div><AlertDialogFooter><AlertDialogCancel>Batal</AlertDialogCancel><Button variant="destructive" :disabled="deleteTeamForm.processing" @click="deleteTeam"><LoaderCircle v-if="deleteTeamForm.processing" class="size-4 animate-spin" />Hapus permanen</Button></AlertDialogFooter></AlertDialogContent></AlertDialog>
    </div>
</template>
