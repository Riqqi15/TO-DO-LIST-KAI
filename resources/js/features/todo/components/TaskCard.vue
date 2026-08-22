<script setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { TODO_STATUSES } from '@/features/todo/constants/todo-options';
import { deadlineMeta, formatShortDate, statusDateMeta, statusTone } from '@/features/todo/utils/todo-formatters';
import { getCategoryColor } from '@/lib/utils';
import { Bell, CalendarClock, MoreHorizontal, Pencil, Trash2 } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps({ todo: { type: Object, required: true } });
const emit = defineEmits(['open', 'edit', 'delete', 'status']);
const deadline = computed(() => deadlineMeta(props.todo));
const statusDate = computed(() => statusDateMeta(props.todo));

const durationText = computed(() => {
    const formatDate = (val) => {
        if (!val) return '?';
        const d = new Date(val);
        return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(d);
    };
    const start = formatDate(props.todo.start_date);
    const end = formatDate(props.todo.deadline_wib?.slice(0, 10) || props.todo.deadline_at);
    return `${start} - ${end}`;
});
</script>

<template>
    <Card
        class="deadline-rail group cursor-pointer border-slate-200/60 bg-white/95 backdrop-blur-sm shadow-sm transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg hover:shadow-primary/5 hover:border-primary/30"
        :style="{ '--deadline-color': deadline.color }"
        @click="emit('open', todo)"
    >
        <CardContent class="p-4 pl-5">
            <div class="flex items-start justify-between gap-3">
                <Badge variant="secondary" :class="['max-w-[75%] truncate font-semibold', getCategoryColor(todo.category?.name)]">{{ todo.category?.name ?? 'Tanpa kategori' }}</Badge>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="ghost" size="icon-sm" class="-mr-1 -mt-1 opacity-70 hover:opacity-100" aria-label="Aksi task" @click.stop>
                            <MoreHorizontal class="size-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" @click.stop>
                        <DropdownMenuItem @click="emit('edit', todo)"><Pencil class="size-4" />Edit task</DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem class="text-destructive focus:text-destructive" @click="emit('delete', todo)"><Trash2 class="size-4" />Hapus task</DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            <h3 class="mt-3 line-clamp-2 break-words [overflow-wrap:anywhere] text-[15px] font-extrabold leading-5 tracking-[-0.015em]">{{ todo.title }}</h3>
            <p v-if="todo.description && todo.description !== todo.category?.name" class="mt-2 line-clamp-2 break-words [overflow-wrap:anywhere] text-xs leading-5 text-muted-foreground">{{ todo.description }}</p>

            <div class="mt-4 space-y-1.5">
                <div v-if="todo.status === 'selesai'" class="flex items-center gap-2 text-xs text-emerald-700">
                    <CalendarClock class="size-3.5 shrink-0" />
                    <span class="font-semibold">Selesai</span>
                    <span class="text-current/45">·</span>
                    <span class="font-mono font-medium truncate">{{ todo.completed_at ? formatShortDate(todo.completed_at) : 'Belum tercatat' }}</span>
                </div>
                
                <div v-if="todo.status === 'sedang_dikerjakan'" class="flex items-center gap-2 text-xs text-blue-700">
                    <CalendarClock class="size-3.5 shrink-0" />
                    <span class="font-semibold">Mulai</span>
                    <span class="text-current/45">·</span>
                    <span class="font-mono font-medium truncate">{{ todo.started_at ? formatShortDate(todo.started_at) : 'Belum tercatat' }}</span>
                </div>

                <div v-if="todo.status !== 'selesai'" class="flex items-center gap-2 text-xs" :class="deadline.tone">
                    <CalendarClock class="size-3.5 shrink-0" />
                    <span class="font-semibold">Durasi</span>
                    <span class="text-current/45">·</span>
                    <span class="font-mono font-medium truncate">{{ durationText }}</span>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between gap-3 border-t border-border/70 pt-3" @click.stop>
                <NativeSelect
                    class="h-8 min-w-0 border-0 pl-3 pr-7 text-xs font-bold shadow-none focus-visible:ring-0 rounded-md"
                    :class="statusTone(todo.status)"
                    :model-value="todo.status"
                    aria-label="Ubah status task"
                    @click.stop
                    @change="emit('status', todo, $event.target.value)"
                >
                    <NativeSelectOption v-for="status in TODO_STATUSES" :key="status.value" :value="status.value">{{ status.label }}</NativeSelectOption>
                </NativeSelect>
                <div class="flex items-center gap-1 text-muted-foreground" :title="`${todo.reminders?.length ?? 0} reminder`">
                    <Bell class="size-3.5" />
                    <span class="font-mono text-[11px]">{{ todo.reminders?.length ?? 0 }}</span>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
