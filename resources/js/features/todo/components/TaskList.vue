<script setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { TODO_STATUSES } from '@/features/todo/constants/todo-options';
import { deadlineMeta, formatDateTime, statusTone } from '@/features/todo/utils/todo-formatters';
import { Bell, ChevronRight } from '@lucide/vue';

defineProps({ todos: { type: Array, default: () => [] } });
const emit = defineEmits(['open', 'status']);
</script>

<template>
    <Card class="overflow-hidden border-border/90 shadow-none">
        <div class="hidden grid-cols-[minmax(0,2fr)_1fr_1fr_1fr_2.5rem] gap-4 border-b bg-slate-50/80 px-5 py-3 text-[11px] font-bold uppercase tracking-[0.12em] text-muted-foreground md:grid">
            <span>Task</span><span>Status</span><span>Deadline</span><span>Reminder</span><span />
        </div>
        <div
            v-for="todo in todos"
            :key="todo.id"
            class="grid w-full gap-3 border-b px-4 py-4 text-left transition last:border-b-0 hover:bg-slate-50 md:grid-cols-[minmax(0,2fr)_1fr_1fr_1fr_2.5rem] md:items-center md:gap-4 md:px-5"
            role="button"
            tabindex="0"
            @click="emit('open', todo)"
            @keydown.enter="emit('open', todo)"
            @keydown.space.prevent="emit('open', todo)"
        >
            <div class="min-w-0">
                <div class="flex items-center gap-2"><span class="size-2 shrink-0 rounded-full" :style="{ background: deadlineMeta(todo).color }" /><p class="truncate text-sm font-extrabold">{{ todo.title }}</p></div>
                <p class="mt-1 truncate pl-4 text-xs text-muted-foreground">{{ todo.category?.name ?? 'Tanpa kategori' }} · {{ todo.creator?.name ?? 'Pengguna' }}</p>
            </div>
            <div @click.stop><NativeSelect class="h-8 w-full text-xs font-bold" :class="statusTone(todo.status)" :model-value="todo.status" @change="emit('status', todo, $event.target.value)"><NativeSelectOption v-for="status in TODO_STATUSES" :key="status.value" :value="status.value">{{ status.label }}</NativeSelectOption></NativeSelect></div>
            <p class="font-mono text-xs" :class="deadlineMeta(todo).tone">{{ formatDateTime(todo.deadline_at) }}</p>
            <div class="flex items-center gap-2 text-xs text-muted-foreground"><Bell class="size-3.5" /><span>{{ todo.reminders?.length ?? 0 }} jadwal</span></div>
            <Button variant="ghost" size="icon-sm" class="hidden md:inline-flex" tabindex="-1"><ChevronRight class="size-4" /></Button>
        </div>
        <div v-if="todos.length === 0" class="px-6 py-16 text-center text-sm text-muted-foreground">Tidak ada task yang cocok dengan filter.</div>
    </Card>
</template>
