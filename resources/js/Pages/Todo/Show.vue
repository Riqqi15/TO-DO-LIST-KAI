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
import { deadlineMeta, formatDateTime, formatDuration, reminderKindLabel, reminderStatusLabel, statusDateInput, toWibDateTimeInput } from '@/features/todo/utils/todo-formatters';
import { getCategoryColor } from '@/lib/utils';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { useSessionStorage } from '@vueuse/core';
import { ArrowLeft, Bell, CalendarClock, CheckCircle2, Hourglass, LoaderCircle, Pencil, Play, Trash2, UserRound } from '@lucide/vue';
import { notifyRequestError } from '@/lib/request-errors';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

const page = usePage();
const props = computed(() => page.props);

const todo = computed(() => props.value.todo);
const workspaces = computed(() => props.value.workspaces ?? []);
const activeWorkspace = computed(() => props.value.activeWorkspace ?? null);
const categories = computed(() => props.value.categories ?? []);

const isG63 = computed(() => todo.value?.category?.name === 'G63');

const status = ref('');
const statusAt = ref('');
const editableTitle = ref('');
const editableDescription = ref('');
const editableStartDate = ref('');
const editableDeadline = ref('');
const titleErrors = ref({});
const resultNotes = ref('');
const reminderForm = useForm({ scheduled_at: '' });
const noteForm = useForm({ body: '' });
const statusErrors = ref({});
const saveProcessing = ref(false);

const now = ref(new Date());
let timeInterval;

onMounted(() => {
    timeInterval = setInterval(() => {
        now.value = new Date();
    }, 60000);
});

onUnmounted(() => {
    if (timeInterval) clearInterval(timeInterval);
});

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
    if (!todo.value) return false;
    return status.value !== todo.value.status || 
           (status.value === 'selesai' && resultNotes.value !== (todo.value.result_notes || ''));
});

const detailsChanged = computed(() => {
    if (!todo.value) return false;
    return editableTitle.value !== todo.value.title || 
           editableDescription.value !== (todo.value.description || '') ||
           editableStartDate.value !== (todo.value.start_date || '') ||
           editableDeadline.value !== (todo.value.deadline_wib?.replace(' ', 'T') || '');
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
    editableStartDate.value = newTodo.start_date || '';
    editableDeadline.value = newTodo.deadline_wib?.replace(' ', 'T') || toWibDateTimeInput(newTodo.deadline_at);
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
            start_date: editableStartDate.value,
            category_id: todo.value.category_id,
            deadline_at: editableDeadline.value,
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
                if (!errors.title && !errors.description) notifyRequestError(errors, 'Perubahan task tidak dapat disimpan.');
            },
        });
    } else if (shouldUpdateStatus) {
        changeStatus();
    }
};

const changeStatus = () => {
    const finalStatusAt = status.value === 'belum_dikerjakan' ? editableDeadline.value : statusAt.value;
    router.patch(`/todos/${todo.value.id}/status`, { 
        status: status.value, 
        status_at: finalStatusAt,
        result_notes: status.value === 'selesai' ? resultNotes.value : null 
    }, {
        preserveScroll: true,
        onError: (errors) => {
            statusErrors.value = errors;
            if (!errors.status && !errors.status_at && !errors.result_notes) notifyRequestError(errors, 'Status task tidak dapat diperbarui.');
        },
        onFinish: () => { saveProcessing.value = false; },
    });
};

const deleteTodo = () => {
    if (confirm(`Hapus task "${todo.value.title}"? Tindakan ini tidak dapat dibatalkan.`)) {
        router.delete(`/todos/${todo.value.id}`, {
            onError: (errors) => notifyRequestError(errors, 'Task tidak dapat dihapus.'),
        });
    }
};

const addReminder = () => reminderForm.post(`/todos/${todo.value.id}/reminders`, {
    preserveScroll: true,
    onSuccess: () => reminderForm.reset(),
    onError: (errors) => {
        if (!errors.scheduled_at) notifyRequestError(errors, 'Reminder tidak dapat dibuat.');
    },
});
const deleteReminder = (reminder) => router.delete(`/reminders/${reminder.id}`, {
    preserveScroll: true,
    onError: (errors) => notifyRequestError(errors, 'Reminder tidak dapat dihapus.'),
});

const addNote = () => noteForm.post(`/todos/${todo.value.id}/notes`, {
    preserveScroll: true,
    onSuccess: () => noteForm.reset(),
    onError: (errors) => {
        if (!errors.body) notifyRequestError(errors, 'Catatan tidak dapat ditambahkan.');
    },
});
const deleteNote = (note) => router.delete(`/notes/${note.id}`, {
    preserveScroll: true,
    onError: (errors) => notifyRequestError(errors, 'Catatan tidak dapat dihapus.'),
});

const goBack = () => {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit('/app');
    }
};

// Bug fix: sidebar navigation dari Detail Task page tidak berfungsi
// karena AppLayout tidak memiliki handler untuk @navigate dan @switch-workspace.
// Solusi: simpan target section ke sessionStorage lalu redirect ke /app.
const activeSection = useSessionStorage('todo_active_section', 'tasks');
const navigate = (section) => {
    activeSection.value = section;
    router.visit('/app', { preserveState: false });
};
const switchWorkspace = (id) => {
    router.get('/app', { workspace: id }, { preserveScroll: false, preserveState: false });
};
</script>

<template>
    <Head :title="`Task: ${todo?.title ?? 'Detail'}`" />

    <AppLayout
        title="Detail Task"
        :workspaces="workspaces"
        :active-workspace="activeWorkspace"
        :categories="categories"
        @navigate="navigate"
        @switch-workspace="switchWorkspace"
    >
        <template #actions>
            <Button variant="outline" size="sm" class="text-destructive hover:bg-destructive hover:text-destructive-foreground border-destructive/20" @click="deleteTodo">
                <Trash2 class="mr-1.5 size-3.5" />
                Hapus
            </Button>
            <Button size="sm" :disabled="saveProcessing || !canSave" @click="saveAll">
                <LoaderCircle v-if="saveProcessing" class="mr-1.5 size-3.5 animate-spin" />
                Simpan Perubahan
            </Button>
        </template>

        <div v-if="todo" class="space-y-6">
            <!-- Back + Badges + Title -->
            <div>
                <Button variant="ghost" size="sm" class="-ml-2 mb-3 text-muted-foreground hover:text-foreground" @click="goBack">
                    <ArrowLeft class="mr-1.5 size-4" />
                    Kembali
                </Button>

                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <Badge variant="secondary" :class="['font-semibold', getCategoryColor(todo.category).class]" :style="getCategoryColor(todo.category).style">{{ todo.category?.name ?? 'Tanpa kategori' }}</Badge>
                    <Badge variant="outline" :style="{ borderColor: deadlineMeta(todo).color, color: deadlineMeta(todo).color }">{{ deadlineMeta(todo).label }}</Badge>
                </div>

                <Input
                    id="edit-title"
                    v-model="editableTitle"
                    class="text-2xl sm:text-3xl font-extrabold leading-tight h-auto py-1.5 px-2 -ml-2 border-transparent hover:border-input focus-visible:border-input bg-transparent shadow-none"
                    placeholder="Judul task"
                />
                <FieldError :message="titleErrors.title" />
            </div>

            <!-- Metadata: Start Date + Deadline + Creator -->
            <div class="grid gap-3 sm:grid-cols-3">
                <div v-if="todo.start_date" class="flex items-center gap-3 rounded-lg border bg-card p-3">
                    <div class="rounded-md bg-primary/10 p-2 text-primary">
                        <CalendarDays class="size-4" />
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">Tanggal Mulai</p>
                        <p class="mt-0.5 font-mono text-sm font-medium">{{ formatDateTime(todo.start_date) }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-lg border bg-card p-3">
                    <div class="rounded-md bg-primary/10 p-2 text-primary">
                        <CalendarClock class="size-4" />
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">Deadline</p>
                        <p class="mt-0.5 font-mono text-sm font-medium">{{ formatDateTime(todo.deadline_at) }} WIB</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-lg border bg-card p-3">
                    <div class="rounded-md bg-primary/10 p-2 text-primary">
                        <UserRound class="size-4" />
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">Dibuat oleh</p>
                        <p class="mt-0.5 text-sm font-bold">{{ todo.creator?.name ?? 'Pengguna' }}</p>
                    </div>
                </div>
            </div>

            <!-- Metadata: Work Timeline -->
            <div v-if="todo.started_at" class="grid gap-3 sm:grid-cols-3">
                <div class="flex items-center gap-3 rounded-lg border bg-card p-3">
                    <div class="rounded-md bg-blue-50 p-2 text-blue-600">
                        <Play class="size-4" />
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">Mulai Dikerjakan</p>
                        <p class="mt-0.5 font-mono text-xs font-medium">{{ formatDateTime(todo.started_at) }} WIB</p>
                    </div>
                </div>
                <div v-if="todo.completed_at" class="flex items-center gap-3 rounded-lg border bg-card p-3">
                    <div class="rounded-md bg-emerald-50 p-2 text-emerald-600">
                        <CheckCircle2 class="size-4" />
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">Selesai Dikerjakan</p>
                        <p class="mt-0.5 font-mono text-xs font-medium">{{ formatDateTime(todo.completed_at) }} WIB</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-lg border bg-card p-3">
                    <div class="rounded-md bg-amber-50 p-2 text-amber-600">
                        <Hourglass class="size-4" />
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">Durasi Pengerjaan</p>
                        <p class="mt-0.5 font-mono text-xs font-medium">{{ formatDuration(todo.started_at, todo.completed_at || now) }}</p>
                    </div>
                </div>
            </div>

            <!-- Deskripsi -->
            <section>
                <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-muted-foreground">Deskripsi</h3>
                <Textarea
                    id="edit-desc"
                    v-model="editableDescription"
                    class="min-h-[120px] w-full resize-y rounded-lg border bg-card px-4 py-3 text-sm leading-relaxed shadow-none placeholder:text-muted-foreground"
                    placeholder="Tambahkan deskripsi lengkap untuk task ini..."
                />
                <FieldError :message="titleErrors.description" />
            </section>

            <!-- Status & Hasil + Reminder (2 kolom) -->
            <div class="grid gap-6 lg:grid-cols-5">
                <!-- Status & Hasil (3/5) -->
                <section class="lg:col-span-3">
                    <h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-muted-foreground">Status & Hasil</h3>
                    <div class="space-y-4 rounded-lg border bg-card p-4">
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <Label class="text-xs font-semibold mb-1.5 block">Status</Label>
                                <NativeSelect :model-value="status" class="h-9 w-full" @change="selectStatus($event.target.value)">
                                    <NativeSelectOption v-for="option in TODO_STATUSES" :key="option.value" :value="option.value">{{ option.label }}</NativeSelectOption>
                                </NativeSelect>
                                <FieldError :message="statusErrors.status" />
                            </div>
                            <div>
                                <Label for="edit-start-date" class="text-xs font-semibold mb-1.5 block">Rencana Mulai (opsional)</Label>
                                <Input
                                    id="edit-start-date"
                                    type="date"
                                    v-model="editableStartDate"
                                    class="h-9 w-full font-mono text-xs"
                                    :disabled="status !== 'belum_dikerjakan'"
                                />
                                <FieldError :message="titleErrors.start_date" />
                            </div>
                            <div>
                                <Label for="edit-deadline" class="text-xs font-semibold mb-1.5 block">Deadline *</Label>
                                <DateTimeInput24h 
                                    id="edit-deadline" 
                                    v-model="editableDeadline" 
                                    class="h-9 font-mono text-xs" 
                                    :aria-invalid="Boolean(titleErrors.deadline_at)" 
                                    :disabled="status !== 'belum_dikerjakan' || isG63"
                                />
                                <p v-if="isG63" class="text-[10px] text-muted-foreground mt-1">Terkunci 30 hari dari pembuatan task.</p>
                                <FieldError :message="titleErrors.deadline_at" />
                            </div>
                        </div>


                        <div v-if="status === 'selesai'">
                            <Label for="result-notes" class="text-xs font-semibold mb-1.5 block">Hasil kegiatan (opsional)</Label>
                            <Textarea id="result-notes" v-model="resultNotes" class="min-h-[80px] w-full resize-y text-sm" placeholder="Tuliskan keterangan atau hasil dari kegiatan ini..." />
                            <FieldError :message="statusErrors.result_notes" />
                        </div>
                    </div>
                </section>

                <!-- Reminder (2/5) -->
                <section class="lg:col-span-2">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                            <Bell class="size-3.5" /> Reminder
                        </h3>
                        <Badge variant="secondary" class="text-[11px] px-1.5 py-0">{{ todo.reminders?.length ?? 0 }}</Badge>
                    </div>
                    <div class="space-y-3 rounded-lg border bg-card p-4">
                        <form class="space-y-2" @submit.prevent="addReminder">
                            <Label for="detail-reminder" class="text-xs font-semibold block">Set pengingat manual</Label>
                            <DateTimeInput24h id="detail-reminder" v-model="reminderForm.scheduled_at" required class="h-9 font-mono text-xs" :aria-invalid="Boolean(reminderForm.errors.scheduled_at)" />
                            <FieldError :message="reminderForm.errors.scheduled_at" />
                            <Button type="submit" variant="secondary" size="sm" class="w-full" :disabled="reminderForm.processing">
                                <LoaderCircle v-if="reminderForm.processing" class="mr-1.5 size-3 animate-spin" />
                                Tambahkan
                            </Button>
                        </form>

                        <Separator />

                        <div class="space-y-2">
                            <div v-for="reminder in todo.reminders ?? []" :key="reminder.id" class="flex items-center justify-between gap-2 rounded-md border px-3 py-2 text-sm">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold flex items-center gap-1.5">
                                        <span class="inline-block size-1.5 rounded-full" :class="reminder.status === 'pending' ? 'bg-amber-400' : 'bg-emerald-500'"></span>
                                        {{ reminderKindLabel(reminder.kind) }}
                                    </p>
                                    <p class="mt-0.5 font-mono text-[11px] text-muted-foreground">{{ formatDateTime(reminder.scheduled_at) }} WIB</p>
                                </div>
                                <Button v-if="reminder.kind === 'manual'" variant="ghost" size="icon-sm" class="text-destructive h-7 w-7 shrink-0 hover:bg-destructive/10" aria-label="Hapus reminder" @click="deleteReminder(reminder)">
                                    <Trash2 class="size-3.5" />
                                </Button>
                            </div>
                            <p v-if="!todo.reminders?.length" class="rounded-md border border-dashed p-4 text-center text-xs text-muted-foreground">Belum ada reminder.</p>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Catatan Harian -->
            <section v-if="status === 'sedang_dikerjakan' || (status === 'selesai' && todo.notes?.length)">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Catatan Harian</h3>
                        <p class="text-[11px] text-muted-foreground mt-0.5">Catatan progres selama pengerjaan.</p>
                    </div>
                    <Badge variant="secondary" class="text-[11px] px-1.5 py-0">{{ todo.notes?.length ?? 0 }}</Badge>
                </div>

                <div class="space-y-3">
                    <form v-if="status === 'sedang_dikerjakan'" class="flex gap-3 items-start" @submit.prevent="addNote">
                        <div class="flex-1">
                            <Textarea id="new-note" v-model="noteForm.body" required class="min-h-[70px] text-sm resize-y bg-white" placeholder="Tulis catatan progres hari ini..." :aria-invalid="Boolean(noteForm.errors.body)" />
                            <FieldError :message="noteForm.errors.body" />
                        </div>
                        <Button type="submit" :disabled="noteForm.processing" class="shrink-0">
                            <LoaderCircle v-if="noteForm.processing" class="mr-1.5 size-4 animate-spin" />
                            Tambah
                        </Button>
                    </form>

                    <Separator v-if="status === 'sedang_dikerjakan' && todo.notes?.length" class="my-5" />

                    <div v-for="note in todo.notes ?? []" :key="note.id" class="rounded-lg border bg-card p-4 text-sm">
                        <div class="flex items-center justify-between gap-3 mb-2 pb-2 border-b border-border/50">
                            <div class="flex items-center gap-2">
                                <div class="flex size-5 items-center justify-center rounded-full bg-primary/10 text-primary">
                                    <UserRound class="size-2.5" />
                                </div>
                                <p class="text-xs font-bold">{{ note.creator?.name ?? 'Pengguna' }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="flex items-center gap-1.5 rounded-md bg-secondary/60 px-2 py-1 font-mono text-xs font-medium text-secondary-foreground border border-secondary/20">
                                    <CalendarClock class="size-3" />
                                    {{ formatDateTime(note.created_at) }} WIB
                                </span>
                                <Button variant="ghost" size="icon-sm" class="text-destructive h-7 w-7 hover:bg-destructive/10" aria-label="Hapus catatan" @click="deleteNote(note)">
                                    <Trash2 class="size-3.5" />
                                </Button>
                            </div>
                        </div>
                        <p class="whitespace-pre-wrap text-sm leading-relaxed break-words">{{ note.body }}</p>
                    </div>
                    <p v-if="!todo.notes?.length" class="rounded-lg border border-dashed p-6 text-center text-xs text-muted-foreground">Belum ada catatan.</p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

