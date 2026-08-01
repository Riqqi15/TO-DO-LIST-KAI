<script setup>
import FieldError from '@/components/shared/FieldError.vue';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import StickyNoteCard from '@/features/todo/components/StickyNoteCard.vue';
import { router, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Pin, Plus, StickyNote } from '@lucide/vue';
import Sortable from 'sortablejs';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps({
    notes: { type: Array, default: () => [] },
    workspaceId: { type: [Number, String], required: true },
});
const editorOpen = ref(false);
const deleteOpen = ref(false);
const selected = ref(null);
const pinBusyId = ref(null);
const reorderProcessing = ref(false);
const pinnedGrid = ref(null);
const localNotes = ref([]);
const form = useForm({ content: '', color: 'yellow' });
const editing = computed(() => Boolean(selected.value));
const pinnedNotes = computed(() => localNotes.value
    .filter((note) => note.pinned_at)
    .sort((a, b) => (a.pin_order ?? 0) - (b.pin_order ?? 0)));
const ordinaryNotes = computed(() => localNotes.value
    .filter((note) => !note.pinned_at)
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at)));
const colors = [
    { value: 'yellow', label: 'Kuning', class: 'bg-[#fff8d9] border-[#eadb9d]' },
    { value: 'blue', label: 'Biru', class: 'bg-[#eaf2ff] border-[#bfd4f8]' },
    { value: 'green', label: 'Hijau', class: 'bg-[#e9f7ee] border-[#b9dfc7]' },
    { value: 'pink', label: 'Merah muda', class: 'bg-[#fff0f4] border-[#efc5d0]' },
    { value: 'purple', label: 'Ungu', class: 'bg-[#f2edff] border-[#d6c8f5]' },
];
const noteClass = (color) => colors.find((item) => item.value === color)?.class ?? colors[0].class;

let sortable = null;
const destroySortable = () => {
    sortable?.destroy();
    sortable = null;
};
const initializeSortable = () => {
    destroySortable();
    if (!pinnedGrid.value || pinnedNotes.value.length < 2) return;
    sortable = Sortable.create(pinnedGrid.value, {
        animation: 180,
        direction: 'horizontal',
        handle: '.pin-drag-handle',
        forceFallback: true,
        fallbackOnBody: true,
        fallbackTolerance: 4,
        swapThreshold: 0.65,
        ghostClass: 'opacity-40',
        chosenClass: 'z-10',
        onEnd: () => {
            const orderedIds = [...pinnedGrid.value.querySelectorAll('[data-note-id]')].map((element) => Number(element.dataset.noteId));
            const previous = localNotes.value.map((note) => ({ ...note }));
            const currentIds = pinnedNotes.value.map((note) => Number(note.id));
            if (orderedIds.every((id, index) => id === currentIds[index])) return;

            const byId = new Map(localNotes.value.map((note) => [Number(note.id), { ...note }]));
            orderedIds.forEach((id, index) => { byId.get(id).pin_order = index; });
            localNotes.value = [...orderedIds.map((id) => byId.get(id)), ...ordinaryNotes.value];
            reorderProcessing.value = true;
            router.patch(`/workspaces/${props.workspaceId}/sticky-notes/reorder`, { note_ids: orderedIds }, {
                preserveScroll: true,
                onError: () => {
                    localNotes.value = previous;
                    toast.error('Urutan pin tidak tersimpan. Posisi sebelumnya dipulihkan.');
                },
                onFinish: () => {
                    reorderProcessing.value = false;
                    nextTick(initializeSortable);
                },
            });
        },
    });
    pinnedGrid.value.dataset.sortableActive = 'true';
};

watch(() => props.notes, (notes) => {
    localNotes.value = notes.map((note) => ({ ...note }));
}, { deep: true, immediate: true });
watch([pinnedGrid, () => pinnedNotes.value.length], () => nextTick(initializeSortable), { flush: 'post' });
onMounted(() => nextTick(initializeSortable));
onBeforeUnmount(destroySortable);

const openCreate = () => {
    selected.value = null;
    form.reset();
    form.color = 'yellow';
    editorOpen.value = true;
};
const openEdit = (note) => {
    selected.value = note;
    form.content = note.content;
    form.color = note.color;
    form.clearErrors();
    editorOpen.value = true;
};
const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => { editorOpen.value = false; form.reset(); } };
    if (selected.value) form.patch(`/sticky-notes/${selected.value.id}`, options);
    else form.post(`/workspaces/${props.workspaceId}/sticky-notes`, options);
};
const togglePin = (note) => {
    pinBusyId.value = note.id;
    router.patch(`/sticky-notes/${note.id}/pin`, {}, {
        preserveScroll: true,
        onError: () => toast.error('Status pin tidak dapat diubah.'),
        onFinish: () => { pinBusyId.value = null; },
    });
};
const confirmDelete = (note) => { selected.value = note; deleteOpen.value = true; };
const remove = () => router.delete(`/sticky-notes/${selected.value.id}`, { preserveScroll: true, onSuccess: () => { deleteOpen.value = false; } });
</script>

<template>
    <div class="space-y-7">
        <div class="flex items-center justify-between gap-4">
            <div><h2 class="text-xl font-extrabold tracking-[-0.025em]">Sticky Notes</h2><p class="mt-1 text-sm text-muted-foreground">Simpan catatan penting dan pin prioritas agar selalu mudah ditemukan.</p></div>
            <Button @click="openCreate"><Plus class="size-4" />Buat note</Button>
        </div>

        <template v-if="localNotes.length">
            <section v-if="pinnedNotes.length" class="space-y-3">
                <div class="flex items-center justify-between border-b border-primary/10 pb-2">
                    <div class="flex items-center gap-2"><Pin class="size-4 text-primary" /><h3 class="text-sm font-extrabold">Disematkan</h3><span class="font-mono text-[10px] text-muted-foreground">{{ pinnedNotes.length }}</span></div>
                    <p class="text-xs text-muted-foreground">Geser dari pegangan untuk mengatur urutan</p>
                </div>
                <div ref="pinnedGrid" class="grid items-start gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" :class="{ 'pointer-events-none opacity-75': reorderProcessing }">
                    <StickyNoteCard v-for="note in pinnedNotes" :key="note.id" :note="note" :color-class="noteClass(note.color)" draggable :pin-busy="pinBusyId === note.id" @pin="togglePin" @edit="openEdit" @delete="confirmDelete" />
                </div>
            </section>

            <section v-if="ordinaryNotes.length" class="space-y-3">
                <div v-if="pinnedNotes.length" class="flex items-center gap-2"><StickyNote class="size-4 text-muted-foreground" /><h3 class="text-sm font-extrabold">Catatan lainnya</h3><span class="font-mono text-[10px] text-muted-foreground">{{ ordinaryNotes.length }}</span></div>
                <div class="grid items-start gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                    <StickyNoteCard v-for="note in ordinaryNotes" :key="note.id" :note="note" :color-class="noteClass(note.color)" :pin-busy="pinBusyId === note.id" @pin="togglePin" @edit="openEdit" @delete="confirmDelete" />
                </div>
            </section>
        </template>

        <Card v-else class="grid min-h-72 place-items-center border-dashed bg-white/50 p-8 text-center shadow-none"><div><div class="mx-auto grid size-12 place-items-center rounded-2xl bg-secondary text-primary"><Pin class="size-5" /></div><h3 class="mt-4 font-extrabold">Belum ada catatan</h3><p class="mt-1 text-sm text-muted-foreground">Simpan ide singkat lalu pin yang paling penting.</p><Button class="mt-5" variant="outline" @click="openCreate"><Plus class="size-4" />Buat note pertama</Button></div></Card>

        <Dialog v-model:open="editorOpen"><DialogContent><DialogHeader><DialogTitle>{{ editing ? 'Edit sticky note' : 'Buat sticky note' }}</DialogTitle><DialogDescription>Catatan dapat dilihat, diedit, dan dipin oleh anggota workspace.</DialogDescription></DialogHeader><form id="note-form" class="space-y-4" @submit.prevent="submit"><div class="space-y-2"><Label for="note-content">Isi catatan</Label><Textarea id="note-content" v-model="form.content" class="min-h-36" maxlength="5000" required :aria-invalid="Boolean(form.errors.content)" /><FieldError :message="form.errors.content" /></div><div class="space-y-2"><Label for="note-color">Warna</Label><NativeSelect id="note-color" v-model="form.color" class="h-10 w-full"><NativeSelectOption v-for="color in colors" :key="color.value" :value="color.value">{{ color.label }}</NativeSelectOption></NativeSelect></div></form><DialogFooter><Button variant="outline" @click="editorOpen = false">Batal</Button><Button form="note-form" type="submit" :disabled="form.processing"><LoaderCircle v-if="form.processing" class="size-4 animate-spin" />Simpan note</Button></DialogFooter></DialogContent></Dialog>

        <AlertDialog v-model:open="deleteOpen"><AlertDialogContent><AlertDialogHeader><AlertDialogTitle>Hapus sticky note?</AlertDialogTitle><AlertDialogDescription>Catatan akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.</AlertDialogDescription></AlertDialogHeader><AlertDialogFooter><AlertDialogCancel>Batal</AlertDialogCancel><AlertDialogAction class="bg-destructive text-white hover:bg-destructive/90" @click="remove">Hapus permanen</AlertDialogAction></AlertDialogFooter></AlertDialogContent></AlertDialog>
    </div>
</template>
