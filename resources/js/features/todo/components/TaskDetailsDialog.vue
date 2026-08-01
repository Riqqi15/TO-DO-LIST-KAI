<script setup>
import FieldError from '@/components/shared/FieldError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Separator } from '@/components/ui/separator';
import { TODO_STATUSES } from '@/features/todo/constants/todo-options';
import { deadlineMeta, formatDateTime, reminderKindLabel, reminderStatusLabel } from '@/features/todo/utils/todo-formatters';
import { router, useForm } from '@inertiajs/vue3';
import { Bell, CalendarClock, LoaderCircle, Pencil, Trash2, UserRound } from '@lucide/vue';
import { ref, watch } from 'vue';

const props = defineProps({ open: { type: Boolean, default: false }, todo: { type: Object, default: null } });
const emit = defineEmits(['update:open', 'edit', 'delete']);
const status = ref('');
const reopenReminder = ref('');
const reminderForm = useForm({ scheduled_at: '' });
const statusErrors = ref({});
const statusProcessing = ref(false);
watch(() => props.todo, (todo) => { status.value = todo?.status ?? ''; reopenReminder.value = ''; statusErrors.value = {}; });

const changeStatus = () => {
    if (!props.todo || status.value === props.todo.status) return;
    statusProcessing.value = true;
    router.patch(`/todos/${props.todo.id}/status`, { status: status.value, manual_reminder_at: reopenReminder.value || null }, {
        preserveScroll: true,
        onError: (errors) => { statusErrors.value = errors; },
        onFinish: () => { statusProcessing.value = false; },
    });
};
const addReminder = () => reminderForm.post(`/todos/${props.todo.id}/reminders`, { preserveScroll: true, onSuccess: () => reminderForm.reset() });
const deleteReminder = (reminder) => router.delete(`/reminders/${reminder.id}`, { preserveScroll: true });
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent v-if="todo" class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <div class="mb-2 flex flex-wrap items-center gap-2"><Badge variant="secondary">{{ todo.category?.name ?? 'Tanpa kategori' }}</Badge><Badge variant="outline" :style="{ borderColor: deadlineMeta(todo).color, color: deadlineMeta(todo).color }">{{ deadlineMeta(todo).label }}</Badge></div>
                <DialogTitle class="text-xl font-extrabold leading-7">{{ todo.title }}</DialogTitle>
                <DialogDescription class="text-sm leading-6">{{ todo.description || 'Task ini tidak memiliki deskripsi.' }}</DialogDescription>
            </DialogHeader>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="flex items-center gap-3 rounded-xl border p-3"><CalendarClock class="size-4 text-primary" /><div><p class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Deadline</p><p class="mt-0.5 font-mono text-xs font-medium">{{ formatDateTime(todo.deadline_at) }} WIB</p></div></div>
                <div class="flex items-center gap-3 rounded-xl border p-3"><UserRound class="size-4 text-primary" /><div><p class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Dibuat oleh</p><p class="mt-0.5 text-xs font-bold">{{ todo.creator?.name ?? 'Pengguna' }}</p></div></div>
            </div>

            <section class="rounded-2xl border p-4">
                <h3 class="text-sm font-extrabold">Ubah status</h3>
                <div class="mt-3 grid gap-3 sm:grid-cols-[1fr_1fr_auto]">
                    <NativeSelect v-model="status" class="h-10 w-full"><NativeSelectOption v-for="option in TODO_STATUSES" :key="option.value" :value="option.value">{{ option.label }}</NativeSelectOption></NativeSelect>
                    <Input v-model="reopenReminder" type="datetime-local" class="h-10 font-mono text-xs" title="Reminder saat membuka kembali task selesai" />
                    <Button :disabled="statusProcessing || status === todo.status" @click="changeStatus"><LoaderCircle v-if="statusProcessing" class="size-4 animate-spin" />Simpan</Button>
                </div>
                <p class="mt-2 text-xs text-muted-foreground">Isi reminder hanya saat membuka kembali task selesai yang tidak memiliki jadwal mendatang.</p>
                <FieldError :message="statusErrors.status" />
            </section>

            <section>
                <div class="flex items-center justify-between"><div><h3 class="text-sm font-extrabold">Reminder</h3><p class="text-xs text-muted-foreground">Jadwal otomatis dan manual untuk task ini.</p></div><Badge variant="outline"><Bell class="size-3" />{{ todo.reminders?.length ?? 0 }}</Badge></div>
                <div class="mt-3 space-y-2">
                    <div v-for="reminder in todo.reminders ?? []" :key="reminder.id" class="flex items-center justify-between gap-3 rounded-xl border px-3 py-2.5"><div class="min-w-0"><p class="text-xs font-bold">{{ reminderKindLabel(reminder.kind) }} · {{ reminderStatusLabel(reminder.status) }}</p><p class="mt-0.5 font-mono text-[10px] text-muted-foreground">{{ formatDateTime(reminder.scheduled_at) }} WIB</p></div><Button v-if="reminder.kind === 'manual'" variant="ghost" size="icon-sm" class="text-destructive" aria-label="Hapus reminder" @click="deleteReminder(reminder)"><Trash2 class="size-3.5" /></Button></div>
                    <p v-if="!todo.reminders?.length" class="rounded-xl border border-dashed p-5 text-center text-xs text-muted-foreground">Belum ada reminder.</p>
                </div>
                <form class="mt-3 flex gap-2" @submit.prevent="addReminder"><div class="flex-1"><Label for="detail-reminder" class="sr-only">Reminder manual baru</Label><Input id="detail-reminder" v-model="reminderForm.scheduled_at" type="datetime-local" required class="h-10 font-mono text-xs" :aria-invalid="Boolean(reminderForm.errors.scheduled_at)" /><FieldError :message="reminderForm.errors.scheduled_at" /></div><Button type="submit" variant="outline" :disabled="reminderForm.processing"><LoaderCircle v-if="reminderForm.processing" class="size-4 animate-spin" />Tambah</Button></form>
            </section>

            <Separator />
            <DialogFooter class="sm:justify-between"><Button variant="ghost" class="text-destructive hover:text-destructive" @click="emit('delete', todo)"><Trash2 class="size-4" />Hapus</Button><Button @click="emit('edit', todo)"><Pencil class="size-4" />Edit task</Button></DialogFooter>
        </DialogContent>
    </Dialog>
</template>
