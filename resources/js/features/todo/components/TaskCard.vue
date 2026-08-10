<script setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { TODO_STATUSES } from '@/features/todo/constants/todo-options';
import { deadlineMeta, formatShortDate, statusDateMeta, statusTone } from '@/features/todo/utils/todo-formatters';
import { Bell, CalendarClock, MoreHorizontal, Pencil, Trash2 } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps({ todo: { type: Object, required: true } });
const emit = defineEmits(['open', 'edit', 'delete', 'status']);
const deadline = computed(() => deadlineMeta(props.todo));
const statusDate = computed(() => statusDateMeta(props.todo));
</script>

<template>
    <Card
        class="deadline-rail group cursor-pointer border-border/90 shadow-none transition duration-200 hover:-translate-y-0.5 hover:border-primary/25 hover:shadow-md hover:shadow-slate-200/50"
        :style="{ '--deadline-color': deadline.color }"
        @click="emit('open', todo)"
    >
        <CardContent class="p-4 pl-5">
            <div class="flex items-start justify-between gap-3">
                <Badge variant="secondary" class="max-w-[75%] truncate font-semibold">{{ todo.category?.name ?? 'Tanpa kategori' }}</Badge>
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
            <p v-if="todo.description" class="mt-2 line-clamp-2 break-words [overflow-wrap:anywhere] text-xs leading-5 text-muted-foreground">{{ todo.description }}</p>

            <div class="mt-4 flex items-center gap-2 text-xs" :class="statusDate.tone">
                <CalendarClock class="size-3.5" />
                <span class="font-semibold">{{ statusDate.label }}</span>
                <span class="text-current/45">·</span>
                <span class="font-mono font-medium">{{ statusDate.value ? formatShortDate(statusDate.value) : 'Belum tercatat' }}</span>
            </div>

            <div class="mt-4 flex items-center justify-between gap-3 border-t border-border/70 pt-3" @click.stop>
                <NativeSelect
                    class="h-8 min-w-0 border-0 pl-3 pr-7 text-xs font-bold shadow-none focus-visible:ring-0 rounded-md"
                    :class="statusTone(todo.status)"
                    :model-value="todo.status"
                    aria-label="Ubah status task"
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
