<script setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import axios from 'axios';
import { ChevronLeft, ChevronRight, LoaderCircle, RefreshCw } from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({ workspaceId: { type: [Number, String], required: true }, todos: { type: Array, default: () => [] } });
const emit = defineEmits(['open']);
const cursor = ref(new Date());
const events = ref([]);
const loading = ref(false);
const error = ref('');

const monthLabel = computed(() => new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(cursor.value));
const days = computed(() => {
    const year = cursor.value.getFullYear();
    const month = cursor.value.getMonth();
    const first = new Date(year, month, 1);
    const startOffset = (first.getDay() + 6) % 7;
    const start = new Date(year, month, 1 - startOffset);
    return Array.from({ length: 42 }, (_, index) => {
        const date = new Date(start);
        date.setDate(start.getDate() + index);
        const key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
        return { date, key, current: date.getMonth() === month, events: events.value.filter((event) => event.deadline_wib?.slice(0, 10) === key) };
    });
});

const loadEvents = async () => {
    loading.value = true;
    error.value = '';
    const year = cursor.value.getFullYear();
    const month = cursor.value.getMonth();
    const from = new Date(year, month, 1).toISOString().slice(0, 10);
    const to = new Date(year, month + 1, 0, 23, 59, 59).toISOString();
    try {
        const response = await axios.get(`/workspaces/${props.workspaceId}/calendar`, { params: { from, to } });
        events.value = response.data.events ?? [];
    } catch {
        error.value = 'Kalender tidak dapat dimuat.';
    } finally {
        loading.value = false;
    }
};

const moveMonth = (amount) => { cursor.value = new Date(cursor.value.getFullYear(), cursor.value.getMonth() + amount, 1); };
const openEvent = (event) => { const todo = props.todos.find((item) => item.id === event.id); if (todo) emit('open', todo); };
watch(() => props.workspaceId, loadEvents);
watch(cursor, loadEvents);
onMounted(loadEvents);
</script>

<template>
    <Card class="overflow-hidden border-border/90 shadow-none">
        <div class="flex flex-col gap-3 border-b px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div><h2 class="text-base font-extrabold capitalize">{{ monthLabel }}</h2><p class="text-xs text-muted-foreground">Deadline ditampilkan dalam WIB.</p></div>
            <div class="flex items-center gap-2"><Button variant="outline" size="icon-sm" aria-label="Bulan sebelumnya" @click="moveMonth(-1)"><ChevronLeft class="size-4" /></Button><Button variant="outline" size="sm" @click="cursor = new Date()">Hari ini</Button><Button variant="outline" size="icon-sm" aria-label="Bulan berikutnya" @click="moveMonth(1)"><ChevronRight class="size-4" /></Button></div>
        </div>
        <div v-if="error" class="flex items-center justify-between gap-4 border-b bg-red-50 px-5 py-3 text-sm text-red-700"><span>{{ error }}</span><Button variant="outline" size="sm" @click="loadEvents"><RefreshCw class="size-4" />Coba lagi</Button></div>
        <div class="relative overflow-x-auto">
            <div class="min-w-[48rem]">
                <div class="grid grid-cols-7 border-b bg-slate-50/70 text-center text-[11px] font-bold uppercase tracking-[0.12em] text-muted-foreground"><div v-for="day in ['Sen','Sel','Rab','Kam','Jum','Sab','Min']" :key="day" class="py-2.5">{{ day }}</div></div>
                <div class="grid grid-cols-7">
                    <div v-for="day in days" :key="day.key" class="min-h-28 border-b border-r p-2 last:border-r-0" :class="day.current ? 'bg-white' : 'bg-slate-50/60'">
                        <span class="grid size-6 place-items-center rounded-full text-xs font-semibold" :class="day.key === new Date().toLocaleDateString('en-CA') ? 'bg-primary text-primary-foreground' : day.current ? '' : 'text-muted-foreground/50'">{{ day.date.getDate() }}</span>
                        <div class="mt-1.5 space-y-1">
                            <button v-for="event in day.events.slice(0, 3)" :key="event.id" type="button" class="block w-full truncate rounded-md border-l-2 border-primary bg-secondary px-2 py-1 text-left text-[11px] font-semibold text-secondary-foreground hover:bg-accent" @click="openEvent(event)">{{ event.deadline_wib.slice(11) }} · {{ event.title }}</button>
                            <Badge v-if="day.events.length > 3" variant="outline" class="text-[9px]">+{{ day.events.length - 3 }} lainnya</Badge>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="loading" class="absolute inset-0 grid place-items-center bg-white/65 backdrop-blur-[1px]"><LoaderCircle class="size-7 animate-spin text-primary" /></div>
        </div>
    </Card>
</template>
