<script setup>
import DateTimeInput24h from '@/components/shared/DateTimeInput24h.vue';
import FieldError from '@/components/shared/FieldError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import { defaultDeadline, toDateTimeInput } from '@/features/todo/utils/todo-formatters';
import { notifyRequestError } from '@/lib/request-errors';
import { useForm } from '@inertiajs/vue3';
import { BellPlus, LoaderCircle, Plus, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    todo: { type: Object, default: null },
    workspaceId: { type: [Number, String], default: null },
    categories: { type: Array, default: () => [] },
});
const emit = defineEmits(['update:open', 'saved']);
const reminderDraft = ref('');
const form = useForm({ category_id: '', title: '', description: '', deadline_at: defaultDeadline(), manual_reminders: [] });
const editing = computed(() => Boolean(props.todo));
const requiresManualReminder = computed(() => {
    const deadline = new Date(form.deadline_at);
    const withinThreeDays = deadline.getTime() - Date.now() <= 72 * 3_600_000;
    const existing = props.todo?.reminders?.some((item) => item.kind === 'manual' && item.status === 'scheduled' && new Date(item.scheduled_at) > new Date());
    return withinThreeDays && !existing;
});

const resetForm = () => {
    form.clearErrors();
    form.category_id = props.todo?.category_id ?? props.categories[0]?.id ?? '';
    form.title = props.todo?.title ?? '';
    form.description = props.todo?.description ?? '';
    form.deadline_at = props.todo ? toDateTimeInput(props.todo) : defaultDeadline();
    form.manual_reminders = [];
    reminderDraft.value = '';
};
watch(() => [props.open, props.todo], () => { if (props.open) resetForm(); }, { deep: true });
watch(() => props.categories, () => { if (!form.category_id) form.category_id = props.categories[0]?.id ?? ''; }, { deep: true });

const addReminder = () => {
    if (!reminderDraft.value || form.manual_reminders.includes(reminderDraft.value)) return;
    form.manual_reminders.push(reminderDraft.value);
    reminderDraft.value = '';
};
const submit = () => {
    const inlineFields = ['title', 'description', 'category_id', 'deadline_at'];
    const options = {
        preserveScroll: true,
        onSuccess: () => { emit('update:open', false); emit('saved'); },
        onError: (errors) => {
            if (!Object.keys(errors).some((field) => inlineFields.includes(field))) {
                notifyRequestError(errors, 'Task tidak dapat disimpan.');
            }
        },
    };
    if (editing.value) form.put(`/todos/${props.todo.id}`, options);
    else form.post(`/workspaces/${props.workspaceId}/todos`, options);
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[85vh] gap-0 overflow-hidden p-0 sm:max-w-[760px] grid-rows-[auto_minmax(0,1fr)_auto]">
            <DialogHeader class="border-b px-6 py-5 pr-14 text-left">
                <DialogTitle class="text-xl font-extrabold">{{ editing ? 'Edit task' : 'Buat task baru' }}</DialogTitle>
                <DialogDescription>{{ editing ? 'Perbarui detail task dan sinkronkan reminder.' : 'Tentukan hasil yang ingin dicapai dan kapan harus selesai.' }}</DialogDescription>
            </DialogHeader>

            <form id="task-form" class="min-h-0 space-y-5 overflow-y-auto px-6 py-5" @submit.prevent="submit">
                <div class="space-y-2"><Label for="task-title">Judul task</Label><Input id="task-title" v-model="form.title" placeholder="Contoh: Siapkan laporan mingguan" maxlength="180" required autofocus class="h-11" :aria-invalid="Boolean(form.errors.title)" /><FieldError :message="form.errors.title" /></div>
                <div class="space-y-2"><Label for="task-description">Deskripsi <span class="font-normal text-muted-foreground">(opsional)</span></Label><Textarea id="task-description" v-model="form.description" placeholder="Tambahkan konteks, hasil akhir, atau catatan penting." class="min-h-28 resize-y" :aria-invalid="Boolean(form.errors.description)" /><FieldError :message="form.errors.description" /></div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="space-y-2"><Label for="task-category">Kategori</Label><NativeSelect id="task-category" v-model="form.category_id" class="h-11 w-full" required :aria-invalid="Boolean(form.errors.category_id)"><NativeSelectOption v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</NativeSelectOption></NativeSelect><FieldError :message="form.errors.category_id" /></div>
                    <div class="space-y-2"><Label for="task-deadline">Deadline (WIB)</Label><DateTimeInput24h id="task-deadline" v-model="form.deadline_at" required class="h-11 font-mono text-xs" :aria-invalid="Boolean(form.errors.deadline_at)" /><FieldError :message="form.errors.deadline_at" /></div>
                </div>

                <div class="rounded-2xl border bg-slate-50/70 p-4">
                    <div class="flex items-start gap-3"><div class="grid size-9 shrink-0 place-items-center rounded-xl bg-secondary text-primary"><BellPlus class="size-4.5" /></div><div><h3 class="text-sm font-extrabold">Reminder manual</h3><p class="mt-0.5 text-xs leading-5 text-muted-foreground">Reminder otomatis H-7 dan H-3 dibuat backend jika waktunya masih tersedia.</p></div></div>
                    <div class="mt-4 flex gap-2"><DateTimeInput24h v-model="reminderDraft" class="h-10 flex-1 font-mono text-xs" aria-label="Waktu reminder manual" /><Button type="button" variant="outline" size="icon" aria-label="Tambah reminder" @click="addReminder"><Plus class="size-4" /></Button></div>
                    <div v-if="form.manual_reminders.length" class="mt-3 flex flex-wrap gap-2"><Badge v-for="(reminder, index) in form.manual_reminders" :key="reminder" variant="outline" class="gap-2 bg-white py-1.5 font-mono text-[10px]">{{ reminder.replace('T', ' ') }}<button type="button" aria-label="Hapus reminder" @click="form.manual_reminders.splice(index, 1)"><X class="size-3" /></button></Badge></div>
                    <p v-if="requiresManualReminder" class="mt-3 text-xs font-semibold text-amber-700">Deadline kurang dari tiga hari. Tambahkan minimal satu reminder manual jika belum ada jadwal mendatang.</p>
                    <FieldError :message="form.errors.manual_reminders" />
                </div>
            </form>

            <DialogFooter class="border-t bg-background px-6 py-4">
                <Button type="button" variant="outline" @click="emit('update:open', false)">Batal</Button>
                <Button form="task-form" type="submit" :disabled="form.processing"><LoaderCircle v-if="form.processing" class="size-4 animate-spin" />{{ editing ? 'Simpan perubahan' : 'Buat task' }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
