<script setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { formatDateTime } from '@/features/todo/utils/todo-formatters';
import { notifyAxiosError } from '@/lib/request-errors';
import axios from 'axios';
import { ChevronLeft, ChevronRight, LoaderCircle, RefreshCw, Zap, MessageCircle, AlertCircle, ChevronDown } from '@lucide/vue';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { computed, onMounted, ref, watch } from 'vue';
import { useSessionStorage } from '@vueuse/core';

const props = defineProps({ workspaceId: { type: [Number, String], required: true }, todos: { type: Array, default: () => [] } });
const emit = defineEmits(['open']);

// Convert stored string back to Date for cursor
const storedCursor = useSessionStorage('todo_calendar_cursor', new Date().toISOString());
const cursor = ref(new Date(storedCursor.value));

watch(cursor, (newVal) => {
    storedCursor.value = newVal.toISOString();
});

const events = ref([]);
const loading = ref(false);
const error = ref('');
const hoveredEventId = ref(null);
const statusFilter = useSessionStorage('todo_calendar_status', 'all');
const categoryFilter = useSessionStorage('todo_calendar_category', 'all');
const isQuickJumpOpen = ref(false);
const previousCursor = ref(null);

const uniqueCategories = computed(() => {
    const cats = new Set();
    events.value.forEach(e => {
        if (e.category) cats.add(e.category);
    });
    return Array.from(cats).sort();
});

const resetView = () => {
    if (previousCursor.value) {
        cursor.value = new Date(previousCursor.value);
        previousCursor.value = null;
    } else {
        cursor.value = new Date();
    }
    statusFilter.value = 'all';
    categoryFilter.value = 'all';
};

const tasksByStatus = computed(() => {
    return {
        belum_dikerjakan: props.todos.filter(t => t.status === 'belum_dikerjakan').sort((a,b) => (a.deadline_wib || '9999').localeCompare(b.deadline_wib || '9999')),
        sedang_dikerjakan: props.todos.filter(t => t.status === 'sedang_dikerjakan').sort((a,b) => (a.deadline_wib || '9999').localeCompare(b.deadline_wib || '9999')),
        selesai: props.todos.filter(t => t.status === 'selesai').sort((a,b) => (a.deadline_wib || '9999').localeCompare(b.deadline_wib || '9999'))
    };
});

const jumpToSpecificTask = (task) => {
    const dateStr = task.start_date || task.deadline_wib?.slice(0, 10);
    if (dateStr) {
        if (!previousCursor.value) {
            previousCursor.value = new Date(cursor.value);
        }
        const dateObj = new Date(dateStr);
        cursor.value = new Date(dateObj.getFullYear(), dateObj.getMonth(), 1);
        statusFilter.value = task.status;
        isQuickJumpOpen.value = false;
        
        // Directly open the task instead of just scrolling the calendar to save the user a click
        emit('open', task);
    }
};

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
            const dateMatch = startKey <= weekEndKey && endKey >= weekStartKey;
            const statusMatch = statusFilter.value === 'all' || e.status === statusFilter.value;
            const categoryMatch = categoryFilter.value === 'all' || e.category === categoryFilter.value || (!e.category && categoryFilter.value === 'none');
            return dateMatch && statusMatch && categoryMatch;
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
                    const parts = event.deadline_wib.slice(0, 10).split('-');
                    const deadlineTime = new Date(parts[0], parts[1] - 1, parts[2]).getTime();
                    const todayTime = new Date(new Date().setHours(0,0,0,0)).getTime();
                    
                    day.slots[slotIndex] = {
                        ...event,
                        isStart: day.key === startKey,
                        isEnd: day.key === endKey,
                        isFirstDayOfEvent: day.key === startKey,
                        hasNoteToday: event.notes?.some(n => n.date === day.key),
                        todayNotes: event.notes?.filter(n => n.date === day.key) || [],
                        isOverdue: event.status !== 'selesai' && todayTime > deadlineTime,
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
    } catch (exception) {
        error.value = notifyAxiosError(exception, 'Kalender tidak dapat dimuat.');
    } finally {
        loading.value = false;
    }
};

const moveMonth = (amount) => { cursor.value = new Date(cursor.value.getFullYear(), cursor.value.getMonth() + amount, 1); };
const openEvent = (event) => { const todo = props.todos.find((item) => item.id === event.id); if (todo) emit('open', todo); };

const getEventStyle = (slot) => {
    // 1. Task Selesai (Hijau)
    if (slot.status === 'selesai') {
        return { backgroundColor: '#10b981', color: 'white', border: 'none' };
    }
    
    const parseDateStr = (dateStr) => {
        if (!dateStr) return new Date().getTime();
        const parts = dateStr.split('-');
        return new Date(parts[0], parts[1] - 1, parts[2]).getTime();
    };
    
    // Hitung jarak H-x berdasarkan deadline_wib (bukan end_date visual kalender)
    const end = parseDateStr(slot.deadline_wib?.slice(0, 10) || slot.end_date);
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const currentDay = today.getTime();
    
    // 2. Hari H Deadline ATAU Overdue (Merah)
    if (currentDay >= end) {
        return { backgroundColor: '#ef4444', color: 'white', border: 'none' }; // red-500
    }
    
    // 3. Peringatan Deadline (H-3, H-2, H-1) - berlaku untuk semua status yang belum selesai
    const msPerDay = 1000 * 60 * 60 * 24;
    const daysRemaining = Math.round((end - currentDay) / msPerDay);
    
    if (daysRemaining === 3 || daysRemaining === 2) {
        return { backgroundColor: '#eab308', color: 'white', border: 'none' }; // yellow-500 (Warning: H-3 atau H-2)
    } else if (daysRemaining === 1) {
        return { backgroundColor: '#f97316', color: 'white', border: 'none' }; // orange-500 (Urgent: H-1 deadline)
    }
    
    // 4. Default / Belum H-3 (Abu-abu)
    // Tetap abu-abu meskipun sedang dikerjakan atau belum dikerjakan
    return { backgroundColor: '#94a3b8', color: 'white', border: 'none' }; // gray-400
};

watch(() => props.workspaceId, loadEvents);
watch(cursor, loadEvents);
watch(() => props.todos, loadEvents, { deep: true });
onMounted(loadEvents);
</script>

<template>
    <TooltipProvider :delay-duration="300">
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
                <Select v-model="categoryFilter">
                    <SelectTrigger class="h-8 w-[160px] text-xs font-semibold text-slate-700 border-slate-200/80 shadow-none bg-white hover:bg-slate-50 focus:ring-0">
                        <SelectValue placeholder="Semua Kategori" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua Kategori</SelectItem>
                        <SelectItem value="none">Tanpa Kategori</SelectItem>
                        <SelectItem v-for="cat in uniqueCategories" :key="cat" :value="cat">{{ cat }}</SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="statusFilter">
                    <SelectTrigger class="h-8 w-[150px] text-xs font-semibold text-slate-700 border-slate-200/80 shadow-none bg-white hover:bg-slate-50 focus:ring-0">
                        <SelectValue placeholder="Semua Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua Status</SelectItem>
                        <SelectItem value="belum_dikerjakan">Belum Dikerjakan</SelectItem>
                        <SelectItem value="sedang_dikerjakan">Sedang Dikerjakan</SelectItem>
                        <SelectItem value="selesai">Selesai</SelectItem>
                    </SelectContent>
                </Select>
                
                <Dialog v-model:open="isQuickJumpOpen">
                    <DialogTrigger asChild>
                        <Button variant="outline" size="sm" class="h-8 rounded-lg px-3 text-xs font-semibold border-slate-200/80 shadow-none hover:bg-slate-50 text-indigo-700 hover:text-indigo-800 gap-1.5">
                            <Zap class="size-3.5" />
                            Cari Task Cepat
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="max-w-xl p-0 overflow-hidden border-slate-200/80">
                        <DialogHeader class="px-5 pt-5 pb-3 border-b border-slate-100">
                            <DialogTitle class="text-base font-bold text-slate-800">Cari & Lompat ke Task</DialogTitle>
                        </DialogHeader>
                        
                        <Tabs defaultValue="belum_dikerjakan" class="w-full">
                            <div class="px-5 pt-2">
                                <TabsList class="grid w-full grid-cols-3 bg-slate-100/80 p-1">
                                    <TabsTrigger value="belum_dikerjakan" class="text-xs data-[state=active]:bg-white data-[state=active]:shadow-sm">Belum Dikerjakan</TabsTrigger>
                                    <TabsTrigger value="sedang_dikerjakan" class="text-xs data-[state=active]:bg-white data-[state=active]:shadow-sm">Sedang Dikerjakan</TabsTrigger>
                                    <TabsTrigger value="selesai" class="text-xs data-[state=active]:bg-white data-[state=active]:shadow-sm">Selesai</TabsTrigger>
                                </TabsList>
                            </div>
                            
                            <div class="h-[300px] overflow-y-auto p-2">
                                <TabsContent value="belum_dikerjakan" class="m-0 focus-visible:outline-none">
                                    <div v-if="tasksByStatus.belum_dikerjakan.length === 0" class="flex h-full items-center justify-center text-sm text-slate-400 py-10">Tidak ada task</div>
                                    <div v-else class="space-y-1">
                                        <button v-for="t in tasksByStatus.belum_dikerjakan" :key="t.id" @click="jumpToSpecificTask(t)" class="w-full flex flex-col text-left px-4 py-2.5 rounded-lg hover:bg-slate-100 hover:shadow-sm border border-transparent hover:border-slate-200 transition-all">
                                            <div class="mb-1.5 flex items-center">
                                                <span class="bg-indigo-50 text-indigo-700 border border-indigo-100 text-[10px] px-2 py-0.5 rounded-md uppercase font-bold tracking-wider">{{ t.category?.name || 'TANPA KATEGORI' }}</span>
                                            </div>
                                            <span class="text-sm font-semibold text-slate-700">{{ t.title }}</span>
                                            <span class="text-xs text-slate-400 mt-0.5">{{ t.deadline_wib ? 'Deadline: ' + new Date(t.deadline_wib).toLocaleDateString('id-ID', {weekday: 'long', day: 'numeric', month: 'short', year: 'numeric'}) : 'Tanpa Tenggat' }}</span>
                                        </button>
                                    </div>
                                </TabsContent>
                                <TabsContent value="sedang_dikerjakan" class="m-0 focus-visible:outline-none">
                                    <div v-if="tasksByStatus.sedang_dikerjakan.length === 0" class="flex h-full items-center justify-center text-sm text-slate-400 py-10">Tidak ada task</div>
                                    <div v-else class="space-y-1">
                                        <button v-for="t in tasksByStatus.sedang_dikerjakan" :key="t.id" @click="jumpToSpecificTask(t)" class="w-full flex flex-col text-left px-4 py-2.5 rounded-lg hover:bg-slate-100 hover:shadow-sm border border-transparent hover:border-slate-200 transition-all">
                                            <div class="mb-1.5 flex items-center">
                                                <span class="bg-indigo-50 text-indigo-700 border border-indigo-100 text-[10px] px-2 py-0.5 rounded-md uppercase font-bold tracking-wider">{{ t.category?.name || 'TANPA KATEGORI' }}</span>
                                            </div>
                                            <span class="text-sm font-semibold text-blue-700">{{ t.title }}</span>
                                            <span class="text-xs text-slate-400 mt-0.5">{{ t.started_at ? 'Mulai dikerjakan: ' + new Date(t.started_at).toLocaleDateString('id-ID', {weekday: 'long', day: 'numeric', month: 'short', year: 'numeric'}) : 'Belum dimulai' }}</span>
                                        </button>
                                    </div>
                                </TabsContent>
                                <TabsContent value="selesai" class="m-0 focus-visible:outline-none">
                                    <div v-if="tasksByStatus.selesai.length === 0" class="flex h-full items-center justify-center text-sm text-slate-400 py-10">Tidak ada task</div>
                                    <div v-else class="space-y-1">
                                        <button v-for="t in tasksByStatus.selesai" :key="t.id" @click="jumpToSpecificTask(t)" class="w-full flex flex-col text-left px-4 py-2.5 rounded-lg hover:bg-slate-100 hover:shadow-sm border border-transparent hover:border-slate-200 transition-all">
                                            <div class="mb-1.5 flex items-center">
                                                <span class="bg-indigo-50 text-indigo-700 border border-indigo-100 text-[10px] px-2 py-0.5 rounded-md uppercase font-bold tracking-wider">{{ t.category?.name || 'TANPA KATEGORI' }}</span>
                                            </div>
                                            <span class="text-sm font-semibold text-emerald-700">{{ t.title }}</span>
                                            <span class="text-xs text-slate-400 mt-0.5">{{ t.completed_at ? 'Selesai pada: ' + new Date(t.completed_at).toLocaleDateString('id-ID', {weekday: 'long', day: 'numeric', month: 'short', year: 'numeric'}) : 'Selesai' }}</span>
                                        </button>
                                    </div>
                                </TabsContent>
                            </div>
                        </Tabs>
                    </DialogContent>
                </Dialog>

                <Button v-if="previousCursor || statusFilter !== 'all' || categoryFilter !== 'all'" variant="ghost" size="sm" class="h-8 rounded-lg px-3 text-xs font-semibold text-slate-500 hover:text-slate-700 hover:bg-slate-100" @click="resetView">
                    Kembali
                </Button>

                <div class="w-px h-5 bg-slate-200/80 mx-1"></div>
                <Button variant="outline" size="icon-sm" class="size-8 rounded-lg border-slate-200/80 shadow-none hover:bg-slate-50" aria-label="Bulan sebelumnya" @click="moveMonth(-1)">
                    <ChevronLeft class="size-4 text-slate-600" />
                </Button>
                <Button variant="outline" size="sm" class="h-8 rounded-lg px-3.5 text-xs font-semibold border-slate-200/80 shadow-none hover:bg-slate-50 text-slate-700" @click="resetView">
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
                                <div v-if="slot" class="relative h-[22px] flex" :class="[
                                    slot.isStart ? 'pl-1' : '',
                                    slot.isEnd ? 'pr-1' : '',
                                    !slot.isEnd ? '-mr-px' : '',
                                    (slot.isFirstDayOfEvent || day.date.getDay() === 1) ? 'z-20' : (!slot.isEnd ? 'z-10' : '')
                                ]">
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <button
                                                type="button"
                                                class="flex-1 h-full px-2 text-[10.5px] font-semibold transition-all flex items-center relative whitespace-nowrap"
                                                :class="[
                                                    slot.isStart ? 'rounded-l-md' : 'rounded-l-none',
                                                    slot.isEnd ? 'rounded-r-md' : 'rounded-r-none',
                                                    hoveredEventId === slot.id ? 'brightness-90 shadow-sm opacity-100 z-20' : ''
                                                ]"
                                                :style="getEventStyle(slot)"
                                                @mouseenter="hoveredEventId = slot.id"
                                                @mouseleave="hoveredEventId = null"
                                                @click="openEvent(slot)"
                                            >
                                                <span v-if="slot.isFirstDayOfEvent || day.date.getDay() === 1" class="leading-none pointer-events-none pr-3 flex items-center gap-1.5 whitespace-nowrap overflow-visible">
                                                    <span class="text-white text-[11px] uppercase font-extrabold tracking-widest">{{ slot.category || 'TANPA KATEGORI' }}</span>
                                                    <span v-if="slot.isOverdue" class="bg-black/25 text-white text-[8.5px] px-1.5 py-0.5 rounded-sm uppercase font-extrabold tracking-widest shrink-0 shadow-sm leading-none">TERLAMBAT</span>
                                                </span>
                                                <div v-if="slot.hasNoteToday" class="absolute right-1 top-1/2 -translate-y-1/2 flex items-center gap-0.5">
                                                    <MessageCircle class="size-3.5 opacity-90 fill-white/20 drop-shadow-sm" />
                                                </div>
                                            </button>
                                        </TooltipTrigger>
                                        <TooltipContent hide-arrow side="top" class="max-w-xs space-y-2 p-3 bg-white border border-slate-200 shadow-md z-[60]">
                                            <p class="font-bold text-sm text-slate-900">{{ slot.title }}</p>
                                            <div class="flex flex-wrap gap-1.5">
                                                <Badge variant="secondary" class="text-[10px]">{{ slot.category || 'Tanpa Kategori' }}</Badge>
                                                <Badge variant="outline" class="text-[10px] capitalize">{{ slot.status ? slot.status.replace('_', ' ') : 'Belum Dikerjakan' }}</Badge>
                                                <Badge v-if="slot.isOverdue" class="text-[10px] font-bold uppercase tracking-wider bg-red-500 hover:bg-red-600 text-white border-transparent shadow-sm">TERLAMBAT</Badge>
                                            </div>
                                            <p v-if="slot.description" class="text-xs text-slate-500 line-clamp-3 leading-relaxed">{{ slot.description }}</p>
                                            <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-[10px] mt-2 pt-2 border-t border-slate-100">
                                                <div>
                                                    <span class="font-semibold text-slate-400 block uppercase tracking-wider mb-0.5">Mulai</span>
                                                    <span class="font-mono text-slate-700 font-medium">{{ slot.start_date ? new Date(slot.start_date).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'}) : '-' }}</span>
                                                </div>
                                                <div>
                                                    <span class="font-semibold text-slate-400 block uppercase tracking-wider mb-0.5">Deadline</span>
                                                    <span class="font-mono text-slate-700 font-medium">{{ slot.deadline_wib ? formatDateTime(slot.deadline_wib) : '-' }}</span>
                                                </div>
                                            </div>
                                            
                                            <div v-if="slot.todayNotes && slot.todayNotes.length > 0" class="mt-3 pt-3 border-t border-slate-100 space-y-2">
                                                <div class="flex items-center gap-1.5 text-[10px] font-bold text-primary">
                                                    <MessageCircle class="size-3.5" />
                                                    <span class="uppercase tracking-wider">Catatan Hari Ini</span>
                                                </div>
                                                <div v-for="note in slot.todayNotes" :key="note.id" class="bg-blue-50/50 p-2 rounded-lg text-xs text-slate-700 border border-blue-100/50">
                                                    <p class="font-bold text-[9px] text-blue-500/80 mb-0.5 uppercase tracking-wider">{{ note.creator || 'Pengguna' }}</p>
                                                    <p class="leading-relaxed line-clamp-4 break-words [overflow-wrap:anywhere]">{{ note.body }}</p>
                                                </div>
                                            </div>
                                        </TooltipContent>
                                    </Tooltip>
                                </div>
                                <div v-else class="h-[22px]"></div>
                            </template>
                            
                            <div v-if="day.slots.slice(4).some(Boolean)" class="px-2 pt-1">
                                <Popover>
                                    <PopoverTrigger asChild>
                                        <button type="button" class="text-[10px] font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded px-1.5 py-0.5 transition-colors w-full text-left flex items-center justify-between group">
                                            <span>+{{ day.slots.slice(4).filter(Boolean).length }} lainnya</span>
                                            <ChevronDown class="size-3 opacity-50 group-hover:opacity-100" />
                                        </button>
                                    </PopoverTrigger>
                                    <PopoverContent class="w-64 p-2 shadow-xl z-[70] max-h-[300px] overflow-y-auto">
                                        <div class="text-xs font-bold text-slate-700 mb-2 px-1 pb-1 border-b border-slate-100">
                                            Agenda {{ day.date.toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'}) }}
                                        </div>
                                        <div class="space-y-1">
                                            <template v-for="(slot, idx) in day.slots" :key="'popover-' + idx">
                                                <button v-if="slot" @click="openEvent(slot)" type="button" class="w-full text-left px-2 py-1.5 rounded-md hover:bg-slate-100 transition-colors flex items-center justify-between group">
                                                    <div class="flex items-center gap-2 overflow-hidden">
                                                        <div class="size-2 rounded-full shrink-0" :style="{ backgroundColor: getEventStyle(slot).backgroundColor }"></div>
                                                        <span class="text-[11px] font-medium text-slate-800 truncate flex items-center gap-1.5">
                                                            <span v-if="slot.category" class="bg-slate-100 text-slate-500 border border-slate-200 text-[8px] px-1.5 py-0.5 rounded uppercase font-bold tracking-wider shrink-0 leading-none">{{ slot.category }}</span>
                                                            <span class="truncate">{{ slot.title }}</span>
                                                        </span>
                                                    </div>
                                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                                                        <span v-if="slot.isOverdue" class="text-[8px] font-bold text-red-500 uppercase tracking-widest bg-red-50 px-1 py-0.5 rounded">TERLAMBAT</span>
                                                        <AlertCircle v-if="slot.isOverdue" class="size-3 text-red-500" />
                                                        <MessageCircle v-if="slot.hasNoteToday" class="size-3 text-slate-400" />
                                                    </div>
                                                </button>
                                            </template>
                                        </div>
                                    </PopoverContent>
                                </Popover>
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
    </TooltipProvider>
</template>
