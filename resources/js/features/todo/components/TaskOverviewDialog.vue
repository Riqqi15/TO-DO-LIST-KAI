<script setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { TODO_STATUSES } from '@/features/todo/constants/todo-options';
import { deadlineMeta, formatDateTime, statusDateMeta } from '@/features/todo/utils/todo-formatters';
import { CalendarClock, CalendarPlus, CheckCircle2, Circle, CircleDot, Clock, Pencil, UserRound } from '@lucide/vue';
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
                <DialogDescription class="text-sm leading-6 whitespace-pre-wrap">{{ todo.description || 'Task ini tidak memiliki deskripsi.' }}</DialogDescription>
            </DialogHeader>

            <div class="mt-2 grid gap-3">
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

                <div v-if="todo.status !== 'belum_dikerjakan'" class="flex items-center gap-3 rounded-xl border p-3">
                    <Clock class="size-4 text-primary" />
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Tanggal {{ statusDateMeta(todo).label }}</p>
                        <p class="mt-0.5 font-mono text-xs font-medium">{{ formatDateTime(statusDateMeta(todo).value) }} WIB</p>
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
            </div>

            <DialogFooter class="mt-4 sm:justify-end">
                <Button @click="emit('edit', todo)">
                    <Pencil class="size-4" />
                    Edit task
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
