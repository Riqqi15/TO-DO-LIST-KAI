<script setup>
import DateTimeInput24h from '@/components/shared/DateTimeInput24h.vue';
import FieldError from '@/components/shared/FieldError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import { TODO_STATUSES } from '@/features/todo/constants/todo-options';
import { deadlineMeta, formatDateTime, reminderKindLabel, reminderStatusLabel, statusDateInput, toWibDateTimeInput } from '@/features/todo/utils/todo-formatters';
import { router, useForm } from '@inertiajs/vue3';
import { Bell, CalendarClock, LoaderCircle, Pencil, Trash2, UserRound } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    todo: { type: Object, default: null },
    initialStatus: { type: String, default: null },
    hideEdit: { type: Boolean, default: false }
});
const emit = defineEmits(['update:open', 'edit', 'delete', 'status-saved']);
const status = ref('');
const statusAt = ref('');
const editableTitle = ref('');
const editableDescription = ref('');
const titleErrors = ref({});
const resultNotes = ref('');
const reminderForm = useForm({ scheduled_at: '' });
const noteForm = useForm({ body: '' });
const statusErrors = ref({});
const saveProcessing = ref(false);
const statusDateLabel = computed(() => ({
    belum_dikerjakan: 'Deadline baru',
    sedang_dikerjakan: 'Tanggal mulai',
    selesai: 'Tanggal selesai',
}[status.value] ?? 'Tanggal status'));
const statusDateHelp = computed(() => ({
    belum_dikerjakan: 'Deadline baru minimal lima menit dari sekarang.',
    sedang_dikerjakan: 'Waktu ketika task mulai dikerjakan.',
    selesai: 'Waktu ketika task dinyatakan selesai.',
}[status.value] ?? 'Pilih tanggal dan waktu status.'));
const statusChanged = computed(() => {
    if (!props.todo || !statusAt.value) return false;
    return status.value !== props.todo.status || 
           statusAt.value !== statusDateInput(props.todo, status.value) ||
           (status.value === 'selesai' && resultNotes.value !== (props.todo.result_notes || ''));
});
const detailsChanged = computed(() => {
    if (!props.todo) return false;
    return editableTitle.value !== props.todo.title || editableDescription.value !== (props.todo.description || '');
});
const canSave = computed(() => detailsChanged.value || statusChanged.value);

const defaultDateForStatus = (nextStatus) => {
    if (nextStatus === 'belum_dikerjakan') return statusDateInput(props.todo, nextStatus);
    if (nextStatus === props.todo?.status) return statusDateInput(props.todo, nextStatus) || toWibDateTimeInput();
    return toWibDateTimeInput();
};
const selectStatus = (nextStatus) => {
    status.value = nextStatus;
    statusAt.value = defaultDateForStatus(nextStatus);
    statusErrors.value = {};
};

watch(() => [props.todo?.id, props.initialStatus, props.open], ([todoId, initialStatus, open]) => {
    if (!todoId || !open || !props.todo) return;
    status.value = initialStatus ?? props.todo.status;
    statusAt.value = defaultDateForStatus(status.value);
    statusErrors.value = {};
    titleErrors.value = {};
    editableTitle.value = props.todo.title;
    editableDescription.value = props.todo.description || '';
    resultNotes.value = props.todo.result_notes || '';
}, { immediate: true });

const saveAll = () => {
    if (!props.todo) return;
    saveProcessing.value = true;
    titleErrors.value = {};
    
    const shouldUpdateStatus = statusChanged.value;
    
    if (detailsChanged.value) {
        router.put(`/todos/${props.todo.id}`, {
            title: editableTitle.value,
            description: editableDescription.value,
            category_id: props.todo.category_id,
            deadline_at: props.todo.deadline_at,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                if (shouldUpdateStatus) {
                    changeStatus();
                } else {
                    emit('update:open', false);
                    saveProcessing.value = false;
                }
            },
            onError: (errors) => { 
                titleErrors.value = errors; 
                saveProcessing.value = false;
            },
        });
    } else if (shouldUpdateStatus) {
        changeStatus();
    }
};

const changeStatus = () => {
    router.patch(`/todos/${props.todo.id}/status`, { 
        status: status.value, 
        status_at: statusAt.value,
        result_notes: status.value === 'selesai' ? resultNotes.value : null 
    }, {
        preserveScroll: true,
        onSuccess: () => {
            emit('status-saved');
            emit('update:open', false);
        },
        onError: (errors) => { statusErrors.value = errors; },
        onFinish: () => { saveProcessing.value = false; },
    });
};

const addReminder = () => reminderForm.post(`/todos/${props.todo.id}/reminders`, { preserveScroll: true, onSuccess: () => reminderForm.reset() });
const deleteReminder = (reminder) => router.delete(`/reminders/${reminder.id}`, { preserveScroll: true });

const addNote = () => noteForm.post(`/todos/${props.todo.id}/notes`, { preserveScroll: true, onSuccess: () => noteForm.reset() });
const deleteNote = (note) => router.delete(`/notes/${note.id}`, { preserveScroll: true });
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent v-if="todo" class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <div class="mb-2 flex flex-wrap items-center gap-2"><Badge variant="secondary">{{ todo.category?.name ?? 'Tanpa kategori' }}</Badge><Badge variant="outline" :style="{ borderColor: deadlineMeta(todo).color, color: deadlineMeta(todo).color }">{{ deadlineMeta(todo).label }}</Badge></div>
                <div class="space-y-3 mt-1">
                    <div>
                        <Label for="edit-title" class="sr-only">Judul task</Label>
                        <Input id="edit-title" v-model="editableTitle" class="text-xl font-extrabold leading-7 h-auto py-1 px-2 -ml-2 border-transparent hover:border-input focus-visible:border-input bg-transparent" placeholder="Judul task" />
                        <FieldError :message="titleErrors.title" />
                    </div>
                    <div>
                        <Label for="edit-desc" class="sr-only">Deskripsi task</Label>
                        <Textarea id="edit-desc" v-model="editableDescription" class="flex min-h-[80px] w-full rounded-md border-transparent hover:border-input focus-visible:border-input bg-transparent px-2 py-2 text-sm shadow-none placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 resize-y leading-6 -ml-2" placeholder="Tambahkan deskripsi..." />
                        <FieldError :message="titleErrors.description" />
                    </div>
                </div>
            </DialogHeader>

            <div class="grid gap-3 sm:grid-cols-2 mt-2">
                <div class="flex items-center gap-3 rounded-xl border p-3"><CalendarClock class="size-4 text-primary" /><div><p class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Deadline</p><p class="mt-0.5 font-mono text-xs font-medium">{{ formatDateTime(todo.deadline_at) }} WIB</p></div></div>
                <div class="flex items-center gap-3 rounded-xl border p-3"><UserRound class="size-4 text-primary" /><div><p class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Dibuat oleh</p><p class="mt-0.5 text-xs font-bold">{{ todo.creator?.name ?? 'Pengguna' }}</p></div></div>
            </div>

            <section class="rounded-2xl border p-4">
                <h3 class="text-sm font-extrabold">Ubah status</h3>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <NativeSelect :model-value="status" class="h-10 w-full" @change="selectStatus($event.target.value)"><NativeSelectOption v-for="option in TODO_STATUSES" :key="option.value" :value="option.value">{{ option.label }}</NativeSelectOption></NativeSelect>
                    <div><Label for="status-at" class="sr-only">{{ statusDateLabel }}</Label><DateTimeInput24h id="status-at" v-model="statusAt" class="h-10 font-mono text-xs" :title="statusDateLabel" :aria-invalid="Boolean(statusErrors.status_at)" /></div>
                </div>
                
                <div v-if="status === 'selesai'" class="mt-3">
                    <Label for="result-notes" class="text-xs font-semibold">Hasil kegiatan (opsional)</Label>
                    <Textarea id="result-notes" v-model="resultNotes" class="mt-1 flex min-h-[60px] w-full resize-y text-sm bg-transparent" placeholder="Tuliskan keterangan atau hasil dari kegiatan ini..." />
                    <FieldError :message="statusErrors.result_notes" />
                </div>

                <p class="mt-2 text-xs text-muted-foreground"><span class="font-semibold text-foreground">{{ statusDateLabel }}:</span> {{ statusDateHelp }}</p>
                <FieldError :message="statusErrors.status" />
                <FieldError :message="statusErrors.status_at" />
            </section>

            <section v-if="status === 'sedang_dikerjakan' || (status === 'selesai' && todo.notes?.length)" class="rounded-2xl border p-4">
                <div class="flex items-center justify-between"><div><h3 class="text-sm font-extrabold">Catatan Harian</h3><p class="text-xs text-muted-foreground">Catatan progres selama pengerjaan.</p></div><Badge variant="outline">{{ todo.notes?.length ?? 0 }}</Badge></div>
                <div class="mt-3 space-y-2">
                    <div v-for="note in todo.notes ?? []" :key="note.id" class="rounded-xl border p-3 text-sm">
                        <div class="flex items-center justify-between gap-3 mb-1">
                            <p class="text-xs font-bold">{{ note.creator?.name ?? 'Pengguna' }}</p>
                            <div class="flex items-center gap-2">
                                <p class="font-mono text-[10px] text-muted-foreground">{{ formatDateTime(note.created_at) }} WIB</p>
                                <Button variant="ghost" size="icon-sm" class="text-destructive h-5 w-5" aria-label="Hapus catatan" @click="deleteNote(note)"><Trash2 class="size-3" /></Button>
                            </div>
                        </div>
                        <p class="whitespace-pre-wrap text-sm">{{ note.body }}</p>
                    </div>
                    <p v-if="!todo.notes?.length" class="rounded-xl border border-dashed p-5 text-center text-xs text-muted-foreground">Belum ada catatan.</p>
                </div>
                <form v-if="status === 'sedang_dikerjakan'" class="mt-3 flex gap-2 items-start" @submit.prevent="addNote"><div class="flex-1"><Label for="new-note" class="sr-only">Catatan baru</Label><Textarea id="new-note" v-model="noteForm.body" required class="min-h-[60px] text-xs resize-y bg-transparent" placeholder="Tulis catatan progres hari ini..." :aria-invalid="Boolean(noteForm.errors.body)" /><FieldError :message="noteForm.errors.body" /></div><Button type="submit" variant="outline" :disabled="noteForm.processing"><LoaderCircle v-if="noteForm.processing" class="size-4 animate-spin" />Tambah</Button></form>
            </section>

            <section>
                <div class="flex items-center justify-between"><div><h3 class="text-sm font-extrabold">Reminder</h3><p class="text-xs text-muted-foreground">Jadwal otomatis dan manual untuk task ini.</p></div><Badge variant="outline"><Bell class="size-3" />{{ todo.reminders?.length ?? 0 }}</Badge></div>
                <div class="mt-3 space-y-2">
                    <div v-for="reminder in todo.reminders ?? []" :key="reminder.id" class="flex items-center justify-between gap-3 rounded-xl border px-3 py-2.5"><div class="min-w-0"><p class="text-xs font-bold">{{ reminderKindLabel(reminder.kind) }} · {{ reminderStatusLabel(reminder.status) }}</p><p class="mt-0.5 font-mono text-[10px] text-muted-foreground">{{ formatDateTime(reminder.scheduled_at) }} WIB</p></div><Button v-if="reminder.kind === 'manual'" variant="ghost" size="icon-sm" class="text-destructive" aria-label="Hapus reminder" @click="deleteReminder(reminder)"><Trash2 class="size-3.5" /></Button></div>
                    <p v-if="!todo.reminders?.length" class="rounded-xl border border-dashed p-5 text-center text-xs text-muted-foreground">Belum ada reminder.</p>
                </div>
                <form class="mt-3 flex gap-2" @submit.prevent="addReminder"><div class="flex-1"><Label for="detail-reminder" class="sr-only">Reminder manual baru</Label><DateTimeInput24h id="detail-reminder" v-model="reminderForm.scheduled_at" required class="h-10 font-mono text-xs" :aria-invalid="Boolean(reminderForm.errors.scheduled_at)" /><FieldError :message="reminderForm.errors.scheduled_at" /></div><Button type="submit" variant="outline" :disabled="reminderForm.processing"><LoaderCircle v-if="reminderForm.processing" class="size-4 animate-spin" />Tambah</Button></form>
            </section>

            <Separator />
            <DialogFooter class="sm:justify-between">
                <Button variant="ghost" class="text-destructive hover:text-destructive" @click="emit('delete', todo)"><Trash2 class="size-4" />Hapus</Button>
                <div class="flex gap-2 justify-end w-full sm:w-auto mt-2 sm:mt-0">
                    <Button variant="ghost" @click="emit('update:open', false)">Batal</Button>
                    <Button :disabled="saveProcessing || !canSave" @click="saveAll"><LoaderCircle v-if="saveProcessing" class="size-4 animate-spin" />Simpan</Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
