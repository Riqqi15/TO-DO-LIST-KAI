<script setup>
import FieldError from '@/components/shared/FieldError.vue';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import { defaultDeadline } from '@/features/todo/utils/todo-formatters';
import { router, useForm } from '@inertiajs/vue3';
import { ArrowRight, CheckCircle2, LoaderCircle, MoreHorizontal, Pencil, Plus, Sparkles, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';

const props = defineProps({ notes: { type: Array, default: () => [] }, categories: { type: Array, default: () => [] }, workspaceId: { type: [Number, String], required: true } });
const editorOpen = ref(false);
const convertOpen = ref(false);
const deleteOpen = ref(false);
const selected = ref(null);
const form = useForm({ content: '', color: 'yellow' });
const convertForm = useForm({ category_id: '', title: '', description: '', deadline_at: defaultDeadline(), manual_reminders: [] });
const editing = computed(() => Boolean(selected.value));
const colors = [
    { value: 'yellow', label: 'Kuning', class: 'bg-[#fff8d9] border-[#eadb9d]' },
    { value: 'blue', label: 'Biru', class: 'bg-[#eaf2ff] border-[#bfd4f8]' },
    { value: 'green', label: 'Hijau', class: 'bg-[#e9f7ee] border-[#b9dfc7]' },
    { value: 'pink', label: 'Merah muda', class: 'bg-[#fff0f4] border-[#efc5d0]' },
    { value: 'purple', label: 'Ungu', class: 'bg-[#f2edff] border-[#d6c8f5]' },
];
const noteClass = (color) => colors.find((item) => item.value === color)?.class ?? colors[0].class;

const openCreate = () => { selected.value = null; form.reset(); form.color = 'yellow'; editorOpen.value = true; };
const openEdit = (note) => { selected.value = note; form.content = note.content; form.color = note.color; form.clearErrors(); editorOpen.value = true; };
const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => { editorOpen.value = false; form.reset(); } };
    if (selected.value) form.patch(`/sticky-notes/${selected.value.id}`, options);
    else form.post(`/workspaces/${props.workspaceId}/sticky-notes`, options);
};
const openConvert = (note) => {
    selected.value = note;
    convertForm.category_id = props.categories[0]?.id ?? '';
    convertForm.title = note.content.slice(0, 80);
    convertForm.description = note.content;
    convertForm.deadline_at = defaultDeadline(8);
    convertForm.manual_reminders = [];
    convertForm.clearErrors();
    convertOpen.value = true;
};
const convert = () => convertForm.post(`/sticky-notes/${selected.value.id}/convert`, { preserveScroll: true, onSuccess: () => { convertOpen.value = false; } });
const confirmDelete = (note) => { selected.value = note; deleteOpen.value = true; };
const remove = () => router.delete(`/sticky-notes/${selected.value.id}`, { preserveScroll: true, onSuccess: () => { deleteOpen.value = false; } });
</script>

<template>
    <div class="space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="text-xl font-extrabold tracking-[-0.025em]">Sticky Notes</h2><p class="mt-1 text-sm text-muted-foreground">Tangkap ide cepat, lalu ubah menjadi task saat sudah siap.</p></div><Button @click="openCreate"><Plus class="size-4" />Buat note</Button></div>
        <div v-if="notes.length" class="grid items-start gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
            <Card v-for="note in notes" :key="note.id" class="group relative min-h-52 rotate-[var(--note-rotate)] border p-5 shadow-sm transition hover:-translate-y-1 hover:rotate-0 hover:shadow-lg" :class="noteClass(note.color)" :style="{ '--note-rotate': note.id % 2 === 0 ? '-0.35deg' : '0.35deg' }">
                <div class="flex items-start justify-between gap-3"><Badge v-if="note.converted_at" variant="outline" class="border-emerald-300 bg-white/55 text-emerald-700"><CheckCircle2 class="size-3" />Sudah jadi task</Badge><span v-else class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Catatan</span><Button variant="ghost" size="icon-sm" class="-mr-2 -mt-2 bg-white/25" aria-label="Edit note" @click="openEdit(note)"><MoreHorizontal class="size-4" /></Button></div>
                <p class="mt-5 whitespace-pre-line text-sm font-semibold leading-6 text-slate-800">{{ note.content }}</p>
                <div class="absolute inset-x-5 bottom-4 flex items-center justify-between gap-3 border-t border-slate-900/10 pt-3"><span class="truncate text-[10px] font-medium text-slate-500">{{ note.creator?.name ?? 'Pengguna' }}</span><div class="flex gap-1"><Button variant="ghost" size="icon-xs" title="Jadikan task" @click="openConvert(note)"><Sparkles class="size-3.5" /></Button><Button variant="ghost" size="icon-xs" title="Edit" @click="openEdit(note)"><Pencil class="size-3.5" /></Button><Button variant="ghost" size="icon-xs" class="text-destructive" title="Hapus" @click="confirmDelete(note)"><Trash2 class="size-3.5" /></Button></div></div>
            </Card>
        </div>
        <Card v-else class="grid min-h-72 place-items-center border-dashed bg-white/50 p-8 text-center shadow-none"><div><div class="mx-auto grid size-12 place-items-center rounded-2xl bg-secondary text-primary"><Sparkles class="size-5" /></div><h3 class="mt-4 font-extrabold">Belum ada catatan</h3><p class="mt-1 text-sm text-muted-foreground">Simpan ide singkat sebelum terlewat.</p><Button class="mt-5" variant="outline" @click="openCreate"><Plus class="size-4" />Buat note pertama</Button></div></Card>

        <Dialog v-model:open="editorOpen"><DialogContent><DialogHeader><DialogTitle>{{ editing ? 'Edit sticky note' : 'Buat sticky note' }}</DialogTitle><DialogDescription>Catatan dapat dilihat dan diedit oleh anggota workspace.</DialogDescription></DialogHeader><form id="note-form" class="space-y-4" @submit.prevent="submit"><div class="space-y-2"><Label for="note-content">Isi catatan</Label><Textarea id="note-content" v-model="form.content" class="min-h-36" maxlength="5000" required :aria-invalid="Boolean(form.errors.content)" /><FieldError :message="form.errors.content" /></div><div class="space-y-2"><Label for="note-color">Warna</Label><NativeSelect id="note-color" v-model="form.color" class="h-10 w-full"><NativeSelectOption v-for="color in colors" :key="color.value" :value="color.value">{{ color.label }}</NativeSelectOption></NativeSelect></div></form><DialogFooter><Button variant="outline" @click="editorOpen = false">Batal</Button><Button form="note-form" type="submit" :disabled="form.processing"><LoaderCircle v-if="form.processing" class="size-4 animate-spin" />Simpan note</Button></DialogFooter></DialogContent></Dialog>

        <Dialog v-model:open="convertOpen"><DialogContent><DialogHeader><DialogTitle>Jadikan task</DialogTitle><DialogDescription>Note tetap tersimpan setelah task berhasil dibuat.</DialogDescription></DialogHeader><form id="convert-form" class="space-y-4" @submit.prevent="convert"><div class="space-y-2"><Label for="convert-title">Judul task</Label><Input id="convert-title" v-model="convertForm.title" required maxlength="180" :aria-invalid="Boolean(convertForm.errors.title)" /><FieldError :message="convertForm.errors.title" /></div><div class="space-y-2"><Label for="convert-category">Kategori</Label><NativeSelect id="convert-category" v-model="convertForm.category_id" class="h-10 w-full"><NativeSelectOption v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</NativeSelectOption></NativeSelect><FieldError :message="convertForm.errors.category_id" /></div><div class="space-y-2"><Label for="convert-deadline">Deadline (WIB)</Label><Input id="convert-deadline" v-model="convertForm.deadline_at" type="datetime-local" required class="font-mono text-xs" :aria-invalid="Boolean(convertForm.errors.deadline_at)" /><FieldError :message="convertForm.errors.deadline_at" /></div><FieldError :message="convertForm.errors.manual_reminders" /></form><DialogFooter><Button variant="outline" @click="convertOpen = false">Batal</Button><Button form="convert-form" type="submit" :disabled="convertForm.processing"><LoaderCircle v-if="convertForm.processing" class="size-4 animate-spin" />Buat task<ArrowRight class="size-4" /></Button></DialogFooter></DialogContent></Dialog>

        <AlertDialog v-model:open="deleteOpen"><AlertDialogContent><AlertDialogHeader><AlertDialogTitle>Hapus sticky note?</AlertDialogTitle><AlertDialogDescription>Catatan akan dihapus permanen. Task hasil konversi, jika ada, tetap tersimpan.</AlertDialogDescription></AlertDialogHeader><AlertDialogFooter><AlertDialogCancel>Batal</AlertDialogCancel><AlertDialogAction class="bg-destructive text-white hover:bg-destructive/90" @click="remove">Hapus permanen</AlertDialogAction></AlertDialogFooter></AlertDialogContent></AlertDialog>
    </div>
</template>
