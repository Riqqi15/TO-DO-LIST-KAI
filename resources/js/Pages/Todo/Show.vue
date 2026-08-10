<script setup>
import DateTimeInput24h from '@/components/shared/DateTimeInput24h.vue';
import FieldError from '@/components/shared/FieldError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import { TODO_STATUSES } from '@/features/todo/constants/todo-options';
import { deadlineMeta, formatDateTime, reminderKindLabel, reminderStatusLabel, statusDateInput, toWibDateTimeInput } from '@/features/todo/utils/todo-formatters';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Bell, CalendarClock, LoaderCircle, Pencil, Trash2, UserRound } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

const page = usePage();
const props = computed(() => page.props);

const todo = computed(() => props.value.todo);
const workspaces = computed(() => props.value.workspaces ?? []);
const activeWorkspace = computed(() => props.value.activeWorkspace ?? null);
const categories = computed(() => props.value.categories ?? []);

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
    if (!todo.value || !statusAt.value) return false;
    return status.value !== todo.value.status || 
           statusAt.value !== statusDateInput(todo.value, status.value) ||
           (status.value === 'selesai' && resultNotes.value !== (todo.value.result_notes || ''));
});

const detailsChanged = computed(() => {
    if (!todo.value) return false;
    return editableTitle.value !== todo.value.title || editableDescription.value !== (todo.value.description || '');
});

const canSave = computed(() => detailsChanged.value || statusChanged.value);

const defaultDateForStatus = (nextStatus) => {
    if (nextStatus === 'belum_dikerjakan') return statusDateInput(todo.value, nextStatus);
    if (nextStatus === todo.value?.status) return statusDateInput(todo.value, nextStatus) || toWibDateTimeInput();
    return toWibDateTimeInput();
};

const selectStatus = (nextStatus) => {
    status.value = nextStatus;
    statusAt.value = defaultDateForStatus(nextStatus);
    statusErrors.value = {};
};

watch(() => todo.value, (newTodo) => {
    if (!newTodo) return;
    status.value = newTodo.status;
    statusAt.value = defaultDateForStatus(status.value);
    statusErrors.value = {};
    titleErrors.value = {};
    editableTitle.value = newTodo.title;
    editableDescription.value = newTodo.description || '';
    resultNotes.value = newTodo.result_notes || '';
}, { immediate: true });

const saveAll = () => {
    if (!todo.value) return;
    saveProcessing.value = true;
    titleErrors.value = {};
    
    const shouldUpdateStatus = statusChanged.value;
    
    if (detailsChanged.value) {
        router.put(`/todos/${todo.value.id}`, {
            title: editableTitle.value,
            description: editableDescription.value,
            category_id: todo.value.category_id,
            deadline_at: todo.value.deadline_at,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                if (shouldUpdateStatus) {
                    changeStatus();
                } else {
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
    router.patch(`/todos/${todo.value.id}/status`, { 
        status: status.value, 
        status_at: statusAt.value,
        result_notes: status.value === 'selesai' ? resultNotes.value : null 
    }, {
        preserveScroll: true,
        onError: (errors) => { statusErrors.value = errors; },
        onFinish: () => { saveProcessing.value = false; },
    });
};

const deleteTodo = () => {
    if (confirm(`Hapus task "${todo.value.title}"? Tindakan ini tidak dapat dibatalkan.`)) {
        router.delete(`/todos/${todo.value.id}`);
    }
};

const addReminder = () => reminderForm.post(`/todos/${todo.value.id}/reminders`, { preserveScroll: true, onSuccess: () => reminderForm.reset() });
const deleteReminder = (reminder) => router.delete(`/reminders/${reminder.id}`, { preserveScroll: true });

const addNote = () => noteForm.post(`/todos/${todo.value.id}/notes`, { preserveScroll: true, onSuccess: () => noteForm.reset() });
const deleteNote = (note) => router.delete(`/notes/${note.id}`, { preserveScroll: true });

const goBack = () => {
    window.history.back();
};
</script>

<template>
    <Head :title="`Task: ${todo?.title ?? 'Detail'}`" />
    
    <AppLayout :workspaces="workspaces" :active-workspace="activeWorkspace" :categories="categories">
        <div class="container mx-auto p-4 max-w-4xl pt-6">
            <Button variant="ghost" class="-ml-2 mb-4 text-muted-foreground" @click="goBack">
                <ArrowLeft class="mr-2 size-4" />
                Kembali
            </Button>
            
            <div v-if="todo" class="bg-card text-card-foreground border rounded-2xl shadow-sm p-6 sm:p-8 relative">
                <!-- Header Actions -->
                <div class="absolute right-6 top-6 flex gap-2">
                    <Button variant="ghost" class="text-destructive hover:text-destructive" @click="deleteTodo" aria-label="Hapus task">
                        <Trash2 class="size-4" />
                    </Button>
                    <Button :disabled="saveProcessing || !canSave" @click="saveAll">
                        <LoaderCircle v-if="saveProcessing" class="mr-2 size-4 animate-spin" />
                        Simpan Perubahan
                    </Button>
                </div>
                
                <!-- Header badges -->
                <div class="mb-4 flex flex-wrap items-center gap-2 pr-40">
                    <Badge variant="secondary">{{ todo.category?.name ?? 'Tanpa kategori' }}</Badge>
                    <Badge variant="outline" :style="{ borderColor: deadlineMeta(todo).color, color: deadlineMeta(todo).color }">{{ deadlineMeta(todo).label }}</Badge>
                </div>
                
                <!-- Main Content & Title -->
                <div class="space-y-4 mb-8">
                    <div>
                        <Label for="edit-title" class="sr-only">Judul task</Label>
                        <Input id="edit-title" v-model="editableTitle" class="text-3xl font-extrabold leading-tight h-auto py-2 px-3 -ml-3 border-transparent hover:border-input focus-visible:border-input bg-transparent" placeholder="Judul task" />
                        <FieldError :message="titleErrors.title" />
                    </div>
                    <div>
                        <Label for="edit-desc" class="sr-only">Deskripsi task</Label>
                        <Textarea id="edit-desc" v-model="editableDescription" class="flex min-h-[120px] w-full rounded-md border-transparent hover:border-input focus-visible:border-input bg-transparent px-3 py-3 text-base shadow-none placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 resize-y leading-relaxed -ml-3" placeholder="Tambahkan deskripsi lengkap untuk task ini..." />
                        <FieldError :message="titleErrors.description" />
                    </div>
                </div>

                <!-- Meta grids -->
                <div class="grid gap-4 sm:grid-cols-2 mb-8">
                    <div class="flex items-center gap-4 rounded-xl border p-4 bg-muted/30">
                        <CalendarClock class="size-5 text-primary" />
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">Deadline</p>
                            <p class="mt-0.5 font-mono text-sm font-medium">{{ formatDateTime(todo.deadline_at) }} WIB</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 rounded-xl border p-4 bg-muted/30">
                        <UserRound class="size-5 text-primary" />
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">Dibuat oleh</p>
                            <p class="mt-0.5 text-sm font-bold">{{ todo.creator?.name ?? 'Pengguna' }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-8">
                    <!-- Left Column: Status & Notes -->
                    <div class="space-y-8">
                        <section>
                            <h3 class="text-lg font-extrabold mb-4 flex items-center gap-2">Status & Hasil</h3>
                            <div class="space-y-4 rounded-xl border p-5 bg-card">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <Label class="text-xs font-semibold mb-1.5 block">Status</Label>
                                        <NativeSelect :model-value="status" class="h-10 w-full" @change="selectStatus($event.target.value)">
                                            <NativeSelectOption v-for="option in TODO_STATUSES" :key="option.value" :value="option.value">{{ option.label }}</NativeSelectOption>
                                        </NativeSelect>
                                    </div>
                                    <div>
                                        <Label for="status-at" class="text-xs font-semibold mb-1.5 block">{{ statusDateLabel }}</Label>
                                        <DateTimeInput24h id="status-at" v-model="statusAt" class="h-10 font-mono text-xs" :title="statusDateLabel" :aria-invalid="Boolean(statusErrors.status_at)" />
                                    </div>
                                </div>
                                
                                <div v-if="status === 'selesai'">
                                    <Label for="result-notes" class="text-xs font-semibold mb-1.5 block">Hasil kegiatan (opsional)</Label>
                                    <Textarea id="result-notes" v-model="resultNotes" class="mt-1 flex min-h-[80px] w-full resize-y text-sm bg-transparent" placeholder="Tuliskan keterangan atau hasil dari kegiatan ini..." />
                                    <FieldError :message="statusErrors.result_notes" />
                                </div>

                                <p class="text-xs text-muted-foreground"><span class="font-semibold text-foreground">{{ statusDateLabel }}:</span> {{ statusDateHelp }}</p>
                                <FieldError :message="statusErrors.status" />
                                <FieldError :message="statusErrors.status_at" />
                            </div>
                        </section>

                        <section v-if="status === 'sedang_dikerjakan' || (status === 'selesai' && todo.notes?.length)">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-extrabold">Catatan Harian</h3>
                                    <p class="text-sm text-muted-foreground">Catatan progres selama pengerjaan.</p>
                                </div>
                                <Badge variant="secondary" class="text-sm">{{ todo.notes?.length ?? 0 }}</Badge>
                            </div>
                            
                            <div class="space-y-3">
                                <form v-if="status === 'sedang_dikerjakan'" class="flex gap-2 items-start mb-4" @submit.prevent="addNote">
                                    <div class="flex-1">
                                        <Label for="new-note" class="sr-only">Catatan baru</Label>
                                        <Textarea id="new-note" v-model="noteForm.body" required class="min-h-[60px] text-sm resize-y bg-transparent" placeholder="Tulis catatan progres hari ini..." :aria-invalid="Boolean(noteForm.errors.body)" />
                                        <FieldError :message="noteForm.errors.body" />
                                    </div>
                                    <Button type="submit" variant="secondary" :disabled="noteForm.processing">
                                        <LoaderCircle v-if="noteForm.processing" class="mr-2 size-4 animate-spin" />
                                        Tambah
                                    </Button>
                                </form>

                                <div v-for="note in todo.notes ?? []" :key="note.id" class="rounded-xl border p-4 text-sm bg-card">
                                    <div class="flex items-center justify-between gap-3 mb-2 pb-2 border-b border-border/50">
                                        <p class="text-sm font-bold">{{ note.creator?.name ?? 'Pengguna' }}</p>
                                        <div class="flex items-center gap-3">
                                            <p class="font-mono text-xs text-muted-foreground">{{ formatDateTime(note.created_at) }} WIB</p>
                                            <Button variant="ghost" size="icon-sm" class="text-destructive h-6 w-6 -mr-1" aria-label="Hapus catatan" @click="deleteNote(note)">
                                                <Trash2 class="size-3.5" />
                                            </Button>
                                        </div>
                                    </div>
                                    <p class="whitespace-pre-wrap text-sm leading-relaxed">{{ note.body }}</p>
                                </div>
                                <p v-if="!todo.notes?.length" class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground bg-muted/10">Belum ada catatan.</p>
                            </div>
                        </section>
                    </div>

                    <!-- Right Column: Reminders -->
                    <div>
                        <section>
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-lg font-extrabold flex items-center gap-2"><Bell class="size-5" /> Reminder</h3>
                                    <p class="text-sm text-muted-foreground">Jadwal otomatis dan manual.</p>
                                </div>
                                <Badge variant="secondary" class="text-sm">{{ todo.reminders?.length ?? 0 }}</Badge>
                            </div>
                            
                            <div class="space-y-3">
                                <form class="flex gap-2 items-start mb-4 bg-muted/30 p-4 rounded-xl border" @submit.prevent="addReminder">
                                    <div class="flex-1">
                                        <Label for="detail-reminder" class="text-xs font-semibold block mb-1.5">Tambah Reminder Manual</Label>
                                        <DateTimeInput24h id="detail-reminder" v-model="reminderForm.scheduled_at" required class="h-10 font-mono text-sm" :aria-invalid="Boolean(reminderForm.errors.scheduled_at)" />
                                        <FieldError :message="reminderForm.errors.scheduled_at" />
                                    </div>
                                    <Button type="submit" variant="secondary" :disabled="reminderForm.processing" class="mt-5">
                                        <LoaderCircle v-if="reminderForm.processing" class="mr-2 size-4 animate-spin" />
                                        Set
                                    </Button>
                                </form>

                                <div v-for="reminder in todo.reminders ?? []" :key="reminder.id" class="flex items-center justify-between gap-3 rounded-xl border px-4 py-3 bg-card">
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold flex items-center gap-1.5">
                                            <span class="inline-block w-2 h-2 rounded-full" :class="reminder.status === 'pending' ? 'bg-amber-400' : 'bg-emerald-500'"></span>
                                            {{ reminderKindLabel(reminder.kind) }}
                                        </p>
                                        <p class="mt-1 font-mono text-xs text-muted-foreground">{{ formatDateTime(reminder.scheduled_at) }} WIB</p>
                                    </div>
                                    <Button v-if="reminder.kind === 'manual'" variant="ghost" size="icon-sm" class="text-destructive h-8 w-8" aria-label="Hapus reminder" @click="deleteReminder(reminder)">
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                                <p v-if="!todo.reminders?.length" class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground bg-muted/10">Belum ada reminder.</p>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
