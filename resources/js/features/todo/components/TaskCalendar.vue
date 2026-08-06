<script setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import axios from 'axios';
import { ChevronLeft, ChevronRight, LoaderCircle, RefreshCw } from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({ workspaceId: { type: [Number, String], required: true }, todos: { type: Array, default: () => [] } });
const emit = defineEmits(['open']);
const cursor = ref(new Date());
const events = ref([]);
const loading = ref(false);
const error = ref('');
const hoveredEventId = ref(null);

const monthOnlyLabel = computed(() => new Intl.DateTimeFormat('id-ID', { month: 'long' }).format(cursor.value));

const monthOptions = computed(() => {
    return Array.from({ length: 12 }, (_, i) => {
        const d = new Date(2000, i, 1);
        return {
            value: i.toString(),
            label: new Intl.DateTimeFormat('id-ID', { month: 'long' }).format(d)
        };
    });
});

const updateMonth = (monthStr) => {
    const newMonth = parseInt(monthStr, 10);
    cursor.value = new Date(cursor.value.getFullYear(), newMonth, 1);
};

const yearOptions = computed(() => {
    const current = new Date().getFullYear();
    return Array.from({ length: 21 }, (_, i) => current - 10 + i); // +/- 10 years
});

const updateYear = (yearStr) => {
    const newYear = parseInt(yearStr, 10);
    cursor.value = new Date(newYear, cursor.value.getMonth(), 1);
};

const todayKey = computed(() => {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
});

const weeks = computed(() => {
    const year = cursor.value.getFullYear();
    const month = cursor.value.getMonth();
    const first = new Date(year, month, 1);
    const startOffset = (first.getDay() + 6) % 7;
    const start = new Date(year, month, 1 - startOffset);
    
    const dayArray = Array.from({ length: 42 }, (_, index) => {
        const date = new Date(start);
        date.setDate(start.getDate() + index);
        const key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
        return { date, key, current: date.getMonth() === month, slots: [] };
    });

    const weeks = [];
    for (let i = 0; i < 6; i++) {
        const weekDays = dayArray.slice(i * 7, (i + 1) * 7);
        const weekStartKey = weekDays[0].key;
        const weekEndKey = weekDays[6].key;

        const weekEvents = events.value.filter(e => {
            const startKey = e.start_date || e.deadline_wib?.slice(0, 10);
            const endKey = e.end_date || e.deadline_wib?.slice(0, 10);
            return startKey <= weekEndKey && endKey >= weekStartKey;
        }).sort((a, b) => {
            const startA = a.start_date || a.deadline_wib?.slice(0, 10);
            const startB = b.start_date || b.deadline_wib?.slice(0, 10);
            if (startA !== startB) return startA.localeCompare(startB);
            const endA = a.end_date || a.deadline_wib?.slice(0, 10);
            const endB = b.end_date || b.deadline_wib?.slice(0, 10);
            return endB.localeCompare(endA); 
        });

        const slots = []; 

        weekEvents.forEach(event => {
            const startKey = event.start_date || event.deadline_wib?.slice(0, 10);
            const endKey = event.end_date || event.deadline_wib?.slice(0, 10);
            
            let slotIndex = slots.findIndex(slotEnd => slotEnd < startKey);
            if (slotIndex === -1) {
                slotIndex = slots.length;
                slots.push('');
            }
            slots[slotIndex] = endKey;

            weekDays.forEach(day => {
                if (day.key >= startKey && day.key <= endKey) {
                    while (day.slots.length < slotIndex) {
                        day.slots.push(null);
                    }
                    day.slots[slotIndex] = {
                        ...event,
                        isStart: day.key === startKey,
                        isEnd: day.key === endKey,
                        isFirstDayOfEvent: day.key === startKey,
                    };
                }
            });
        });
        
        const maxSlots = weekDays.reduce((max, day) => Math.max(max, day.slots.length), 0);
        weekDays.forEach(day => {
            while (day.slots.length < maxSlots) {
                day.slots.push(null);
            }
        });

        weeks.push(weekDays);
    }
    return weeks;
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
watch(() => props.todos, loadEvents, { deep: true });
onMounted(loadEvents);
</script>

<template>
    <Card class="overflow-hidden border border-slate-200/80 shadow-none bg-white rounded-xl">
        <!-- Calendar Header -->
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <div class="flex items-center gap-1.5 -ml-1">
                    <Select :modelValue="cursor.getMonth().toString()" @update:modelValue="updateMonth">
                        <SelectTrigger class="h-7 w-fit text-base font-extrabold text-slate-900 border-none shadow-none bg-transparent hover:bg-slate-100 p-1 focus:ring-0 capitalize tracking-tight">
                            <SelectValue :placeholder="monthOnlyLabel" />
                        </SelectTrigger>
                        <SelectContent class="max-h-[300px]">
                            <SelectItem v-for="m in monthOptions" :key="m.value" :value="m.value" class="capitalize">{{ m.label }}</SelectItem>
                        </SelectContent>
                    </Select>
                    
                    <Select :modelValue="cursor.getFullYear().toString()" @update:modelValue="updateYear">
                        <SelectTrigger class="h-7 w-[80px] text-base font-extrabold text-slate-900 border-none shadow-none bg-transparent hover:bg-slate-100 p-1 focus:ring-0">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent class="max-h-[300px]">
                            <SelectItem v-for="y in yearOptions" :key="y" :value="y.toString()">{{ y }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <p class="text-xs text-slate-400 mt-0.5">Progress tracking dengan rentang tanggal. Deadline WIB.</p>
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
                <!-- Days Header Row -->
                <div class="grid grid-cols-7 border-b border-slate-100 bg-white text-center text-[11px] font-extrabold uppercase tracking-wider text-slate-400">
                    <div v-for="day in ['SEN','SEL','RAB','KAM','JUM','SAB','MIN']" :key="day" class="py-3">
                        {{ day }}
                    </div>
                </div>

                <!-- Grid 7 columns x 6 rows -->
                <div class="grid grid-cols-7" v-for="(week, weekIdx) in weeks" :key="weekIdx">
                    <div
                        v-for="day in week"
                        :key="day.key"
                        class="min-h-28 border-b border-r border-slate-100 p-0 last:border-r-0 flex flex-col justify-start"
                        :class="day.current ? 'bg-white' : 'bg-slate-50/30'"
                    >
                        <!-- Date Number -->
                        <div class="flex items-center justify-between p-2.5 pb-1">
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

                        <!-- Task Events -->
                        <div class="flex-1 mt-0.5 space-y-[2px] pb-2">
                            <template v-for="(slot, idx) in day.slots.slice(0, 4)" :key="idx">
                                <div v-if="slot" class="relative h-[22px] flex" :class="{
                                    'pl-1': slot.isStart,
                                    'pr-1': slot.isEnd,
                                    '-mr-px z-10': !slot.isEnd
                                }">
                                    <button
                                        type="button"
                                        class="flex-1 h-full truncate px-2 text-[10.5px] font-semibold transition-colors flex items-center"
                                        :class="[
                                            slot.isStart ? 'rounded-l-md' : 'rounded-l-none',
                                            slot.isEnd ? 'rounded-r-md' : 'rounded-r-none',
                                            slot.status === 'belum_dikerjakan' ? 'border border-slate-200/60 text-slate-700' :
                                            slot.status === 'sedang_dikerjakan' ? 'text-blue-700' :
                                            'text-emerald-700',
                                            hoveredEventId === slot.id ? (
                                                slot.status === 'belum_dikerjakan' ? 'bg-slate-300/70' :
                                                slot.status === 'sedang_dikerjakan' ? 'bg-blue-300/60' :
                                                'bg-emerald-300/60'
                                            ) : (
                                                slot.status === 'belum_dikerjakan' ? 'bg-slate-100' :
                                                slot.status === 'sedang_dikerjakan' ? 'bg-blue-100/90' :
                                                'bg-emerald-100/90'
                                            ),
                                            (!slot.isStart || !slot.isEnd) && slot.status === 'belum_dikerjakan' ? 'border-x-0' : '',
                                            !slot.isStart && slot.status === 'belum_dikerjakan' ? 'border-l-0' : '',
                                            !slot.isEnd && slot.status === 'belum_dikerjakan' ? 'border-r-0' : ''
                                        ]"
                                        @mouseenter="hoveredEventId = slot.id"
                                        @mouseleave="hoveredEventId = null"
                                        @click="openEvent(slot)"
                                    >
                                        <span v-if="slot.isFirstDayOfEvent || day.date.getDay() === 1" class="truncate leading-none">
                                            {{ slot.title }}
                                        </span>
                                    </button>
                                </div>
                                <div v-else class="h-[22px]"></div>
                            </template>
                            
                            <div v-if="day.slots.length > 4" class="px-2 pt-1 text-[9px] font-medium text-slate-500">
                                +{{ day.slots.length - 4 }} lainnya
                            </div>
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
