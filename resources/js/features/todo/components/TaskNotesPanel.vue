<script setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Textarea } from '@/components/ui/textarea';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { TODO_STATUSES } from '@/features/todo/constants/todo-options';
import { notifyRequestError } from '@/lib/request-errors';
import { router } from '@inertiajs/vue3';
import { useTimeAgo } from '@vueuse/core';
import { ArrowLeft, CheckCircle2, Circle, CircleDot, FileText, Search, Send, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';

const props = defineProps({
    todos: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    workspaceId: { type: [Number, String], required: true },
});

const searchQuery = ref('');
const sortBy = ref('terbaru');
const selectedCategoryId = ref('');
const selectedStatus = ref('');
const selectedMonthYear = ref('');

const selectedTaskId = ref(null);
const newNote = ref('');
const isSubmitting = ref(false);

const noteSortBy = ref('terbaru');
const noteFilterDate = ref('');
const noteFilterMonth = ref('');
const noteFilterYear = ref('');

const monthYears = computed(() => {
    const dates = props.todos
        .map(t => t.created_at)
        .filter(d => Boolean(d))
        .map(d => {
            const date = new Date(d);
            const y = date.getFullYear();
            const m = (date.getMonth() + 1).toString().padStart(2, '0');
            return `${y}-${m}`;
        });
    const unique = [...new Set(dates)].sort().reverse();
    return unique.map(val => {
        const [y, m] = val.split('-');
        const monthName = new Date(y, parseInt(m) - 1, 1).toLocaleString('id-ID', { month: 'long' });
        return { value: val, label: `${monthName} ${y}` };
    });
});

const filteredTodos = computed(() => {
    let result = [...props.todos];
    
    const q = searchQuery.value.toLowerCase().trim();
    if (q) {
        result = result.filter((t) => t.title.toLowerCase().includes(q) || t.description?.toLowerCase().includes(q));
    }

    if (selectedCategoryId.value) {
        result = result.filter(t => String(t.category_id) === String(selectedCategoryId.value));
    }
    
    if (selectedStatus.value) {
        result = result.filter(t => t.status === selectedStatus.value);
    }
    
    if (selectedMonthYear.value) {
        result = result.filter(t => {
            if (!t.created_at) return false;
            const date = new Date(t.created_at);
            const y = date.getFullYear();
            const m = (date.getMonth() + 1).toString().padStart(2, '0');
            return `${y}-${m}` === selectedMonthYear.value;
        });
    }

    result.sort((a, b) => {
        if (sortBy.value === 'terbaru') {
            return new Date(b.created_at) - new Date(a.created_at);
        } else if (sortBy.value === 'terlama') {
            return new Date(a.created_at) - new Date(b.created_at);
        } else if (sortBy.value === 'deadline') {
            const dateA = a.deadline_at ? new Date(a.deadline_at) : new Date(8640000000000000);
            const dateB = b.deadline_at ? new Date(b.deadline_at) : new Date(8640000000000000);
            return dateA - dateB;
        }
        return 0;
    });

    return result;
});

const selectedTask = computed(() => props.todos.find((t) => t.id === selectedTaskId.value) ?? null);
const taskNotes = computed(() => selectedTask.value?.notes ?? []);

const filteredTaskNotes = computed(() => {
    let result = [...taskNotes.value];
    
    if (noteFilterYear.value) {
        result = result.filter(n => new Date(n.created_at).getFullYear() == noteFilterYear.value);
    }
    if (noteFilterMonth.value) {
        result = result.filter(n => (new Date(n.created_at).getMonth() + 1) == noteFilterMonth.value);
    }
    if (noteFilterDate.value) {
        result = result.filter(n => new Date(n.created_at).getDate() == noteFilterDate.value);
    }
    
    result.sort((a, b) => {
        if (noteSortBy.value === 'terbaru') {
            return new Date(b.created_at) - new Date(a.created_at);
        } else {
            return new Date(a.created_at) - new Date(b.created_at);
        }
    });
    
    return result;
});

const selectTask = (task) => {
    selectedTaskId.value = task.id;
};

const backToList = () => {
    selectedTaskId.value = null;
    newNote.value = '';
};

const formatTimeAgo = (dateStr) => {
    if (!dateStr) return '';
    return useTimeAgo(new Date(dateStr)).value;
};

const submitNote = () => {
    if (!newNote.value.trim() || !selectedTask.value) return;
    isSubmitting.value = true;
    router.post(`/todos/${selectedTask.value.id}/notes`, {
        body: newNote.value.trim(),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            newNote.value = '';
            isSubmitting.value = false;
        },
        onError: (errors) => {
            notifyRequestError(errors, 'Gagal menambahkan catatan.');
            isSubmitting.value = false;
        }
    });
};

const deleteNote = (noteId) => {
    router.delete(`/notes/${noteId}`, {
        preserveScroll: true,
        onError: (errors) => notifyRequestError(errors, 'Gagal menghapus catatan.'),
    });
};

const getStatusIcon = (status) => {
    if (status === 'selesai') return CheckCircle2;
    if (status === 'sedang_dikerjakan') return CircleDot;
    return Circle;
};

const getStatusColor = (status) => {
    if (status === 'selesai') return 'text-emerald-600';
    if (status === 'sedang_dikerjakan') return 'text-blue-600';
    return 'text-slate-400';
};

const setFilterToToday = () => {
    const today = new Date();
    noteFilterDate.value = today.getDate();
    noteFilterMonth.value = today.getMonth() + 1;
    noteFilterYear.value = today.getFullYear();
};

const currentYear = new Date().getFullYear();
const availableYears = [currentYear - 1, currentYear, currentYear + 1, currentYear + 2];
</script>

<template>
    <div class="h-[calc(100vh-6rem)] w-full">
        <!-- Page 1: Task List -->
        <Card v-if="!selectedTask" class="flex h-full flex-col overflow-hidden border-slate-200/60 bg-white/50 backdrop-blur-md shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-200/60 p-4 bg-white/80">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-1/2 z-10 size-4 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                        <Input v-model="searchQuery" placeholder="Cari task untuk melihat catatan..." class="h-10 w-full bg-slate-100/50 pl-9 border-slate-200 shadow-inner rounded-xl" />
                    </div>
                    <div class="w-full sm:w-48">
                        <NativeSelect v-model="sortBy" class="h-10 rounded-xl bg-white border-slate-200">
                            <NativeSelectOption value="terbaru">Dibuat (Terbaru)</NativeSelectOption>
                            <NativeSelectOption value="terlama">Dibuat (Terlama)</NativeSelectOption>
                            <NativeSelectOption value="deadline">Waktu Deadline</NativeSelectOption>
                        </NativeSelect>
                    </div>
                </div>
                <!-- Filters -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <NativeSelect v-model="selectedCategoryId" class="h-9 rounded-xl bg-slate-50 border-slate-200 text-sm">
                            <NativeSelectOption value="">Semua Kategori</NativeSelectOption>
                            <NativeSelectOption v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</NativeSelectOption>
                        </NativeSelect>
                    </div>
                    <div class="flex-1">
                        <NativeSelect v-model="selectedStatus" class="h-9 rounded-xl bg-slate-50 border-slate-200 text-sm">
                            <NativeSelectOption value="">Semua Status</NativeSelectOption>
                            <NativeSelectOption v-for="stat in TODO_STATUSES" :key="stat.value" :value="stat.value">{{ stat.label }}</NativeSelectOption>
                        </NativeSelect>
                    </div>
                    <div class="flex-1">
                        <NativeSelect v-model="selectedMonthYear" class="h-9 rounded-xl bg-slate-50 border-slate-200 text-sm">
                            <NativeSelectOption value="">Semua Bulan/Tahun</NativeSelectOption>
                            <NativeSelectOption v-for="my in monthYears" :key="my.value" :value="my.value">{{ my.label }}</NativeSelectOption>
                        </NativeSelect>
                    </div>
                </div>
            </div>
            
            <ScrollArea class="flex-1 min-h-0">
                <div class="p-4 flex flex-col gap-3">
                    <div v-if="filteredTodos.length === 0" class="py-12 text-center">
                        <FileText class="size-10 text-slate-300 mx-auto mb-3" />
                        <p class="text-sm font-medium text-slate-600">Tidak ada task yang cocok dengan pencarian.</p>
                    </div>
                    
                    <button
                        v-for="task in filteredTodos"
                        :key="task.id"
                        class="group flex w-full flex-col items-start gap-2 rounded-xl border border-slate-200/60 bg-white p-4 text-left transition-all hover:border-primary/30 hover:bg-primary/5 hover:shadow-md"
                        @click="selectTask(task)"
                    >
                        <div class="flex w-full items-start justify-between gap-3">
                            <h4 class="font-bold text-slate-800 line-clamp-2 leading-tight group-hover:text-primary transition-colors">{{ task.title }}</h4>
                            <component :is="getStatusIcon(task.status)" class="size-5 shrink-0" :class="getStatusColor(task.status)" />
                        </div>
                        
                        <div class="flex w-full items-center justify-between mt-auto pt-2 border-t border-slate-100">
                            <Badge variant="secondary" class="text-[10px]">{{ task.category?.name ?? 'Tanpa kategori' }}</Badge>
                            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-600 bg-slate-100 px-2 py-1 rounded-md">
                                <FileText class="size-3.5" /> 
                                <span>{{ task.notes?.length || 0 }} <span class="font-normal text-slate-500">Catatan</span></span>
                            </div>
                        </div>
                    </button>
                </div>
            </ScrollArea>
        </Card>

        <!-- Page 2: Task Detail & Notes -->
        <Card v-else class="flex h-full flex-col overflow-hidden border-slate-200/60 bg-white/80 backdrop-blur-md shadow-sm">
            <div class="border-b border-slate-200/60 p-4 bg-white flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <Button variant="ghost" size="sm" class="gap-2 -ml-2 text-muted-foreground hover:text-foreground" @click="backToList">
                        <ArrowLeft class="size-4" />
                        Kembali ke Daftar Task
                    </Button>
                    <div class="flex items-center gap-2">
                        <Badge variant="secondary" class="text-[10px]">{{ selectedTask.category?.name ?? 'Tanpa kategori' }}</Badge>
                        <component :is="getStatusIcon(selectedTask.status)" class="size-4 shrink-0" :class="getStatusColor(selectedTask.status)" />
                    </div>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-primary mb-1">Catatan Harian</p>
                    <h3 class="text-xl font-extrabold leading-tight">{{ selectedTask.title }}</h3>
                </div>
                
                <!-- Filters & Sorting for Notes -->
                <div class="flex flex-col sm:flex-row gap-2 mt-2">
                    <Button type="button" variant="outline" size="sm" class="h-8 text-xs shrink-0 rounded-lg border-primary/20 bg-primary/5 text-primary font-bold hover:bg-primary/10 transition-colors" @click="setFilterToToday" title="Lihat catatan untuk hari ini">
                        Hari Ini
                    </Button>
                    <NativeSelect v-model="noteFilterDate" class="h-8 rounded-lg bg-slate-50 border-slate-200 text-xs flex-1">
                        <NativeSelectOption value="">Semua Tanggal</NativeSelectOption>
                        <NativeSelectOption v-for="d in 31" :key="d" :value="d">{{ d }}</NativeSelectOption>
                    </NativeSelect>
                    <NativeSelect v-model="noteFilterMonth" class="h-8 rounded-lg bg-slate-50 border-slate-200 text-xs flex-1">
                        <NativeSelectOption value="">Semua Bulan</NativeSelectOption>
                        <NativeSelectOption value="1">Januari</NativeSelectOption>
                        <NativeSelectOption value="2">Februari</NativeSelectOption>
                        <NativeSelectOption value="3">Maret</NativeSelectOption>
                        <NativeSelectOption value="4">April</NativeSelectOption>
                        <NativeSelectOption value="5">Mei</NativeSelectOption>
                        <NativeSelectOption value="6">Juni</NativeSelectOption>
                        <NativeSelectOption value="7">Juli</NativeSelectOption>
                        <NativeSelectOption value="8">Agustus</NativeSelectOption>
                        <NativeSelectOption value="9">September</NativeSelectOption>
                        <NativeSelectOption value="10">Oktober</NativeSelectOption>
                        <NativeSelectOption value="11">November</NativeSelectOption>
                        <NativeSelectOption value="12">Desember</NativeSelectOption>
                    </NativeSelect>
                    <NativeSelect v-model="noteFilterYear" class="h-8 rounded-lg bg-slate-50 border-slate-200 text-xs flex-1">
                        <NativeSelectOption value="">Semua Tahun</NativeSelectOption>
                        <NativeSelectOption v-for="y in availableYears" :key="y" :value="y">{{ y }}</NativeSelectOption>
                    </NativeSelect>
                    <NativeSelect v-model="noteSortBy" class="h-8 rounded-lg bg-white border-slate-200 text-xs w-full sm:w-32 shadow-sm font-medium">
                        <NativeSelectOption value="terbaru">Terbaru</NativeSelectOption>
                        <NativeSelectOption value="terlama">Terlama</NativeSelectOption>
                    </NativeSelect>
                </div>
            </div>
            
            <ScrollArea class="flex-1 bg-slate-50/50 min-h-0">
                <div class="p-4 sm:p-6 space-y-4">
                    <div v-if="filteredTaskNotes.length === 0" class="py-12 text-center text-sm text-muted-foreground">
                        <FileText class="size-10 text-slate-300 mx-auto mb-3" />
                        <span v-if="taskNotes.length === 0">Belum ada catatan harian untuk task ini.</span>
                        <span v-else>Tidak ada catatan harian yang sesuai dengan filter.</span>
                    </div>
                    
                    <div v-for="note in filteredTaskNotes" :key="note.id" class="group relative rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:shadow-md">
                        <div class="flex justify-between items-start gap-4 mb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="flex size-8 items-center justify-center rounded-full bg-primary/10 text-xs font-extrabold text-primary ring-2 ring-primary/20 ring-offset-2">
                                    {{ note.creator?.name?.substring(0, 2).toUpperCase() || 'U' }}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-800">{{ note.creator?.name ?? 'Pengguna' }}</p>
                                    <p class="text-[11px] text-muted-foreground font-medium">{{ formatTimeAgo(note.created_at) }}</p>
                                </div>
                            </div>
                            <Button 
                                variant="ghost" 
                                size="icon-xs" 
                                class="opacity-0 group-hover:opacity-100 transition-opacity text-muted-foreground hover:text-destructive hover:bg-destructive/10"
                                @click="deleteNote(note.id)"
                                title="Hapus catatan"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-4 border border-slate-100">
                            <p class="text-sm text-slate-700 whitespace-pre-wrap leading-relaxed">{{ note.body }}</p>
                        </div>
                    </div>
                </div>
            </ScrollArea>
            
            <div class="border-t border-slate-200/60 bg-white p-4">
                <form @submit.prevent="submitNote">
                    <div class="flex flex-col sm:flex-row items-end sm:items-start gap-3">
                        <Textarea 
                            v-model="newNote" 
                            placeholder="Tulis progres atau catatan hari ini..." 
                            class="min-h-[80px] flex-1 resize-none bg-slate-50 border-slate-200 focus-visible:ring-primary/20 rounded-xl"
                            @keydown.ctrl.enter="submitNote"
                            @keydown.meta.enter="submitNote"
                        />
                        <Button type="submit" :disabled="!newNote.trim() || isSubmitting" class="h-10 w-full sm:h-[80px] sm:w-[100px] shrink-0 rounded-xl bg-primary hover:bg-primary/90 text-primary-foreground shadow-md transition-all gap-2">
                            <span class="sm:hidden font-bold">Kirim</span>
                            <Send class="size-4" />
                        </Button>
                    </div>
                    <p class="mt-2 text-right sm:text-left text-[10px] text-muted-foreground font-medium">
                        Gunakan <kbd class="rounded border px-1.5 py-0.5 font-mono text-[10px] bg-slate-100 shadow-sm mx-0.5">Ctrl</kbd> + <kbd class="rounded border px-1.5 py-0.5 font-mono text-[10px] bg-slate-100 shadow-sm mx-0.5">Enter</kbd> untuk menyimpan catatan
                    </p>
                </form>
            </div>
        </Card>
    </div>
</template>
