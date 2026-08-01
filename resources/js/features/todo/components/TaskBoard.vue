<script setup>
import { Badge } from '@/components/ui/badge';
import { TODO_STATUSES } from '@/features/todo/constants/todo-options';
import TaskCard from '@/features/todo/components/TaskCard.vue';
import { computed } from 'vue';

const props = defineProps({ todos: { type: Array, default: () => [] } });
const emit = defineEmits(['open', 'edit', 'delete', 'status']);
const grouped = computed(() => Object.fromEntries(TODO_STATUSES.map((status) => [status.value, props.todos.filter((todo) => todo.status === status.value)])));
</script>

<template>
    <div class="grid min-w-[58rem] grid-cols-3 gap-4 xl:gap-5">
        <section v-for="status in TODO_STATUSES" :key="status.value" class="rounded-2xl bg-slate-100/75 p-3.5">
            <div class="mb-3 flex items-center justify-between px-1">
                <div class="flex items-center gap-2.5">
                    <span class="size-2 rounded-full" :class="status.value === 'selesai' ? 'bg-emerald-500' : status.value === 'sedang_dikerjakan' ? 'bg-blue-500' : 'bg-slate-400'" />
                    <h2 class="text-sm font-extrabold">{{ status.label }}</h2>
                </div>
                <Badge variant="outline" class="bg-white font-mono text-[10px]">{{ grouped[status.value].length }}</Badge>
            </div>
            <div class="space-y-3">
                <TaskCard
                    v-for="todo in grouped[status.value]"
                    :key="todo.id"
                    :todo="todo"
                    @open="emit('open', $event)"
                    @edit="emit('edit', $event)"
                    @delete="emit('delete', $event)"
                    @status="(todo, nextStatus) => emit('status', todo, nextStatus)"
                />
                <div v-if="grouped[status.value].length === 0" class="rounded-xl border border-dashed border-slate-300 bg-white/55 px-4 py-9 text-center text-xs text-muted-foreground">
                    Belum ada task di status ini.
                </div>
            </div>
        </section>
    </div>
</template>
