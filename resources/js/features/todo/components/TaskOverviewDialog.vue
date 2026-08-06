<script setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { TODO_STATUSES } from '@/features/todo/constants/todo-options';
import { deadlineMeta, formatDateTime, statusDateMeta } from '@/features/todo/utils/todo-formatters';
import { CalendarClock, CalendarPlus, CheckCircle2, Circle, CircleDot, Clock, Pencil, UserRound, Timer } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    todo: { type: Object, default: null }
});
const emit = defineEmits(['update:open', 'edit']);

const statusLabel = computed(() => {
    if (!props.todo) return '-';
    return TODO_STATUSES.find(s => s.value === props.todo.status)?.label ?? props.todo.status;
});

const statusIcon = computed(() => {
    if (props.todo?.status === 'selesai') return CheckCircle2;
    if (props.todo?.status === 'sedang_dikerjakan') return CircleDot;
    return Circle;
});

const statusColor = computed(() => {
    if (props.todo?.status === 'selesai') return 'text-emerald-600';
    if (props.todo?.status === 'sedang_dikerjakan') return 'text-blue-600';
    return 'text-slate-400';
});

const durationWorked = computed(() => {
    if (!props.todo?.started_at) return null;
    const start = new Date(props.todo.started_at);
    const end = props.todo.status === 'selesai' && props.todo.completed_at 
        ? new Date(props.todo.completed_at) 
        : new Date();
    
    let diffMs = end.getTime() - start.getTime();
    if (diffMs < 0) return 'Kurang dari 1 menit';
    
    const days = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    diffMs -= days * (1000 * 60 * 60 * 24);
    
    const hours = Math.floor(diffMs / (1000 * 60 * 60));
    diffMs -= hours * (1000 * 60 * 60);
    
    const minutes = Math.floor(diffMs / (1000 * 60));
    
    const parts = [];
    if (days > 0) parts.push(`${days} hari`);
    if (hours > 0) parts.push(`${hours} jam`);
    if (minutes > 0) parts.push(`${minutes} menit`);
    
    if (parts.length === 0) return 'Kurang dari 1 menit';
    return parts.join(' ');
});
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent v-if="todo" class="sm:max-w-md">
            <DialogHeader>
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    <Badge variant="secondary">{{ todo.category?.name ?? 'Tanpa kategori' }}</Badge>
                    <Badge variant="outline" :style="{ borderColor: deadlineMeta(todo).color, color: deadlineMeta(todo).color }">{{ deadlineMeta(todo).label }}</Badge>
                </div>
                <DialogTitle class="text-xl font-extrabold leading-7">{{ todo.title }}</DialogTitle>
                <DialogDescription class="sr-only">Detail dan informasi task</DialogDescription>
            </DialogHeader>

            <div class="mt-2 grid gap-3">
                <div v-if="todo.description" class="rounded-xl border p-4 bg-slate-50/50">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground mb-2">Deskripsi Task</p>
                    <p class="text-sm leading-relaxed whitespace-pre-wrap text-slate-800">{{ todo.description }}</p>
                </div>

                <div class="flex items-center gap-3 rounded-xl border p-3">
                    <CalendarClock class="size-4 text-primary" />
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Deadline</p>
                        <p class="mt-0.5 font-mono text-xs font-medium">{{ formatDateTime(todo.deadline_at) }} WIB</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-xl border p-3">
                    <component :is="statusIcon" class="size-4" :class="statusColor" />
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Status</p>
                        <p class="mt-0.5 text-xs font-medium">{{ statusLabel }}</p>
                    </div>
                </div>

                <div v-if="todo.started_at" class="flex items-center gap-3 rounded-xl border p-3">
                    <Clock class="size-4 text-primary" />
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Mulai Dikerjakan</p>
                        <p class="mt-0.5 font-mono text-xs font-medium">{{ formatDateTime(todo.started_at) }} WIB</p>
                    </div>
                </div>

                <div v-if="todo.status === 'selesai' && todo.completed_at" class="flex items-center gap-3 rounded-xl border p-3">
                    <CheckCircle2 class="size-4 text-primary" />
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Selesai Pada</p>
                        <p class="mt-0.5 font-mono text-xs font-medium">{{ formatDateTime(todo.completed_at) }} WIB</p>
                    </div>
                </div>

                <div v-if="todo.started_at" class="flex items-center gap-3 rounded-xl border p-3">
                    <Timer class="size-4 text-primary" />
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Durasi Pengerjaan</p>
                        <p class="mt-0.5 text-xs font-medium">{{ durationWorked }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-xl border p-3">
                    <UserRound class="size-4 text-primary" />
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Dibuat oleh</p>
                        <p class="mt-0.5 text-xs font-medium">{{ todo.creator?.name ?? 'Pengguna' }}</p>
                    </div>
                </div>

                <div v-if="todo.created_at" class="flex items-center gap-3 rounded-xl border p-3">
                    <CalendarPlus class="size-4 text-primary" />
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Dibuat pada</p>
                        <p class="mt-0.5 font-mono text-xs font-medium">{{ formatDateTime(todo.created_at) }} WIB</p>
                    </div>
                </div>
                <div v-if="todo.status === 'selesai' && todo.result_notes" class="mt-4 rounded-xl bg-slate-50/50 p-4 border">
                    <p class="text-xs font-bold uppercase tracking-wider text-muted-foreground mb-2">Hasil Kegiatan</p>
                    <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ todo.result_notes }}</p>
                </div>
            </div>

            <DialogFooter class="sm:justify-between">
                <Button @click="emit('edit', todo)">
                    <Pencil class="size-4" />
                    Edit task
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
