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

const todayKey = computed(() => {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
});

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
    <Card class="overflow-hidden border border-slate-200/80 shadow-none bg-white rounded-xl">
        <!-- Calendar Header -->
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 class="text-base font-extrabold text-slate-900 capitalize tracking-tight">{{ monthLabel }}</h2>
                <p class="text-xs text-slate-400 mt-0.5">Deadline ditampilkan dalam WIB.</p>
            </div>
            <div class="flex items-center gap-2">
                <Button variant="outline" size="icon-sm" class="size-8 rounded-lg border-slate-200/80 shadow-none hover:bg-slate-50" aria-label="Bulan sebelumnya" @click="moveMonth(-1)">
                    <ChevronLeft class="size-4 text-slate-600" />
                </Button>
                <Button variant="outline" size="sm" class="h-8 rounded-lg px-3.5 text-xs font-semibold border-slate-200/80 shadow-none hover:bg-slate-50 text-slate-700" @click="cursor = new Date()">
                    Hari ini
                </Button>
                <Button variant="outline" size="icon-sm" class="size-8 rounded-lg border-slate-200/80 shadow-none hover:bg-slate-50" aria-label="Bulan berikutnya" @click="moveMonth(1)">
                    <ChevronRight class="size-4 text-slate-600" />
                </Button>
            </div>
        </div>

        <!-- Error Alert -->
        <div v-if="error" class="flex items-center justify-between gap-4 border-b bg-red-50 px-5 py-3 text-sm text-red-700">
            <span>{{ error }}</span>
            <Button variant="outline" size="sm" @click="loadEvents"><RefreshCw class="size-4" />Coba lagi</Button>
        </div>

        <!-- Calendar Body -->
        <div class="relative overflow-x-auto">
            <div class="min-w-[48rem]">
                <!-- Days Header Row: SEN SEL RAB KAM JUM SAB MIN -->
                <div class="grid grid-cols-7 border-b border-slate-100 bg-white text-center text-[11px] font-extrabold uppercase tracking-wider text-slate-400">
                    <div v-for="day in ['SEN','SEL','RAB','KAM','JUM','SAB','MIN']" :key="day" class="py-3">
                        {{ day }}
                    </div>
                </div>

                <!-- Grid 7 columns x 6 rows -->
                <div class="grid grid-cols-7">
                    <div
                        v-for="day in days"
                        :key="day.key"
                        class="min-h-28 border-b border-r border-slate-100 p-2.5 last:border-r-0 flex flex-col justify-start"
                        :class="day.current ? 'bg-white' : 'bg-slate-50/30'"
                    >
                        <!-- Date Number -->
                        <div class="flex items-center justify-between mb-1.5">
                            <span
                                class="grid size-6 place-items-center rounded-full text-xs"
                                :class="[
                                    day.key === todayKey
                                        ? 'bg-blue-600 text-white font-bold shadow-xs'
                                        : day.current
                                            ? 'font-semibold text-slate-700'
                                            : 'font-normal text-slate-300'
                                ]"
                            >
                                {{ day.date.getDate() }}
                            </span>
                        </div>

                        <!-- Task Events (Pill style matching screenshot) -->
                        <div class="mt-0.5 space-y-1 flex-1">
                            <button
                                v-for="event in day.events.slice(0, 3)"
                                :key="event.id"
                                type="button"
                                class="w-full text-left truncate rounded-full px-3 py-1 text-[11px] font-semibold transition-colors block"
                                :class="{
                                    'bg-slate-100/90 text-slate-700 hover:bg-slate-200/90': event.status === 'belum_dikerjakan',
                                    'bg-blue-50/90 text-blue-700 hover:bg-blue-100/90': event.status === 'sedang_dikerjakan',
                                    'bg-emerald-50/90 text-emerald-700 hover:bg-emerald-100/90': event.status === 'selesai'
                                }"
                                @click="openEvent(event)"
                            >
                                {{ event.deadline_wib ? event.deadline_wib.slice(11) : '' }} · {{ event.title }}
                            </button>

                            <Badge
                                v-if="day.events.length > 3"
                                variant="outline"
                                class="text-[9px] rounded-full px-2 py-0.5 text-blue-600 border-blue-200 bg-blue-50/50"
                            >
                                +{{ day.events.length - 3 }} lainnya
                            </Badge>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading overlay -->
            <div v-if="loading" class="absolute inset-0 grid place-items-center bg-white/65 backdrop-blur-[1px]">
                <LoaderCircle class="size-7 animate-spin text-primary" />
            </div>
        </div>
    </Card>
</template>
