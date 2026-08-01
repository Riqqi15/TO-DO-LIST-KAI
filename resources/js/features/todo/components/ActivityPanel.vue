<script setup>
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { activityLabel, formatDateTime, summarizeActivity } from '@/features/todo/utils/todo-formatters';
import { Activity, Search } from '@lucide/vue';
import { computed, ref } from 'vue';

const props = defineProps({ activities: { type: Array, default: () => [] } });
const search = ref('');
const filtered = computed(() => props.activities.filter((item) => `${activityLabel(item.action)} ${item.actor?.name ?? ''} ${summarizeActivity(item)}`.toLowerCase().includes(search.value.toLowerCase())));
</script>

<template>
    <div class="mx-auto max-w-4xl space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div><h2 class="text-xl font-extrabold tracking-[-0.025em]">Activity workspace</h2><p class="mt-1 text-sm text-muted-foreground">Riwayat perubahan permanen dari workspace ini.</p></div><div class="relative w-full sm:w-72"><Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" /><Input v-model="search" placeholder="Cari aktivitas..." class="h-10 pl-9" /></div></div>
        <Card class="p-5 shadow-none sm:p-7">
            <div v-if="filtered.length" class="relative space-y-0 before:absolute before:bottom-4 before:left-[0.7rem] before:top-4 before:w-px before:bg-border">
                <article v-for="item in filtered" :key="item.id" class="relative flex gap-4 pb-7 last:pb-0"><div class="relative z-10 mt-1 grid size-6 shrink-0 place-items-center rounded-full border-4 border-white bg-primary"><span class="size-1 rounded-full bg-white" /></div><div class="min-w-0 flex-1"><div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between"><h3 class="text-sm font-extrabold">{{ activityLabel(item.action) }}</h3><time class="font-mono text-[10px] text-muted-foreground">{{ formatDateTime(item.created_at) }}</time></div><p class="mt-1 text-xs text-muted-foreground">oleh <span class="font-bold text-foreground">{{ item.actor?.name ?? 'Sistem' }}</span></p><p class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-xs leading-5 text-slate-600">{{ summarizeActivity(item) }}</p></div></article>
            </div>
            <div v-else class="grid min-h-56 place-items-center text-center"><div><div class="mx-auto grid size-11 place-items-center rounded-2xl bg-secondary text-primary"><Activity class="size-5" /></div><h3 class="mt-4 text-sm font-extrabold">{{ activities.length ? 'Aktivitas tidak ditemukan' : 'Belum ada aktivitas' }}</h3><p class="mt-1 text-xs text-muted-foreground">{{ activities.length ? 'Coba kata kunci lain.' : 'Perubahan workspace akan muncul di sini.' }}</p></div></div>
        </Card>
        <Badge variant="outline" class="font-mono text-[10px]">Maksimal 100 aktivitas terbaru</Badge>
    </div>
</template>
