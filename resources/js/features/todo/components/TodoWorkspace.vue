<script setup>
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import ActivityPanel from '@/features/todo/components/ActivityPanel.vue';
import StickyNotesPanel from '@/features/todo/components/StickyNotesPanel.vue';
import TaskBoard from '@/features/todo/components/TaskBoard.vue';
import TaskCalendar from '@/features/todo/components/TaskCalendar.vue';
import TaskFormSheet from '@/features/todo/components/TaskFormSheet.vue';
import TaskOverviewDialog from '@/features/todo/components/TaskOverviewDialog.vue';
import TaskList from '@/features/todo/components/TaskList.vue';
import { TODO_STATUSES } from '@/features/todo/constants/todo-options';
import { deadlineMeta } from '@/features/todo/utils/todo-formatters';
import AppLayout from '@/layouts/AppLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { Activity, ArrowDownNarrowWide, CalendarDays, CheckCircle2, CircleDashed, Clock, FileText, Filter, FlaskConical, Globe, Hourglass, LayoutGrid, MessageSquareQuote, PanelLeft, Plus, Search, Sparkles, UserPlus, List, Circle, CircleDot, SlidersHorizontal } from '@lucide/vue';
import { useSessionStorage } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const page = usePage();
const props = computed(() => page.props);
const workspaces = computed(() => props.value.workspaces ?? []);
const activeWorkspace = computed(() => props.value.activeWorkspace ?? null);
const categories = computed(() => props.value.categories ?? []);
const todos = computed(() => props.value.todos ?? []);
const stickyNotes = computed(() => props.value.stickyNotes ?? []);
const activities = computed(() => props.value.activities ?? []);
const flash = computed(() => props.value.flash ?? {});
const user = computed(() => props.value.auth?.user ?? null);

const activeSection = useSessionStorage('todo_active_section', 'tasks');
const viewMode = useSessionStorage('todo_view_mode', 'board');
const search = useSessionStorage('todo_search', '');
const categoryFilter = useSessionStorage('todo_category', '');
const statusFilter = useSessionStorage('todo_status', '');
const formOpen = ref(false);
const formTodo = ref(null);
const overviewOpen = ref(false);
const selectedTodo = ref(null);
const deleteOpen = ref(false);

const filteredTodos = computed(() => todos.value.filter((todo) => {
    const needle = search.value.trim().toLowerCase();
    const matchesSearch = !needle || `${todo.title} ${todo.description ?? ''}`.toLowerCase().includes(needle);
    const matchesCategory = !categoryFilter.value || Number(todo.category_id) === Number(categoryFilter.value);
    const matchesStatus = !statusFilter.value || todo.status === statusFilter.value;
    return matchesSearch && matchesCategory && matchesStatus;
}));
const counts = computed(() => ({
    total: todos.value.length,
    pending: todos.value.filter((todo) => todo.status === 'belum_dikerjakan').length,
    ongoing: todos.value.filter((todo) => todo.status === 'sedang_dikerjakan').length,
    done: todos.value.filter((todo) => todo.status === 'selesai').length,
    urgent: todos.value.filter((todo) => ['Terlambat', 'Kurang dari 24 jam'].includes(deadlineMeta(todo).label)).length,
}));
const header = computed(() => ({
    tasks: { eyebrow: 'Pusat produktivitas', title: activeWorkspace.value?.name ?? 'Tasks', description: `${counts.value.total} task di workspace ini` },
    calendar: { eyebrow: 'Jadwal kerja', title: 'Kalender deadline', description: 'Lihat ritme kerja berdasarkan deadline task' },
    notes: { eyebrow: 'Ruang ide', title: 'Sticky Notes', description: `${stickyNotes.value.length} catatan di workspace ini` },
    activity: { eyebrow: 'Jejak perubahan', title: 'Activity', description: 'Riwayat permanen workspace' },
}[activeSection.value]));

const navigate = (section) => {
    activeSection.value = section;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};
const setView = (mode) => {
    viewMode.value = mode;
};
const switchWorkspace = (id) => router.get('/app', { workspace: id }, { preserveScroll: false, preserveState: false });
const createTodo = () => { formTodo.value = null; formOpen.value = true; };
const editTodo = (todo) => { selectedTodo.value = todo; formTodo.value = todo; formOpen.value = true; };
const openTodo = (todo) => router.visit(`/todos/${todo.id}`);
const openCalendarTodo = (todo) => router.visit(`/todos/${todo.id}`);
const askDeleteTodo = (todo) => { selectedTodo.value = todo; deleteOpen.value = true; };
const deleteTodo = () => router.delete(`/todos/${selectedTodo.value.id}`, { preserveScroll: true, onSuccess: () => { deleteOpen.value = false; selectedTodo.value = null; } });
const changeStatus = (todo, nextStatus) => {
    router.visit(`/todos/${todo.id}`);
};

watch(flash, (value) => {
    if (value.success) toast.success(value.success);
    if (value.team_invite?.code) toast.info(`Kode tim: ${value.team_invite.code}`, { description: 'Berlaku selama lima menit.' });
}, { deep: true, immediate: true });
watch(todos, (items) => {
    if (!selectedTodo.value) return;
    selectedTodo.value = items.find((todo) => todo.id === selectedTodo.value.id) ?? null;
    if (!selectedTodo.value) {
        overviewOpen.value = false;
    }
});
</script>

<template>
    <AppLayout
        :title="header.title"
        :description="header.description"
        :eyebrow="header.eyebrow"
        :workspaces="workspaces"
        :active-workspace="activeWorkspace"
        :categories="categories"
        :invite="flash.team_invite"
        :active-section="activeSection"
        :user="user"
        @navigate="navigate"
        @switch-workspace="switchWorkspace"
    >
        <template #actions>
            <Button v-if="activeWorkspace && ['tasks', 'calendar'].includes(activeSection)" class="font-bold shadow-sm shadow-primary/15" @click="createTodo"><Plus class="size-4" /><span class="hidden sm:inline">Buat task</span><span class="sm:hidden">Task</span></Button>
        </template>

        <div v-if="!activeWorkspace" class="grid min-h-[65vh] place-items-center"><Card class="max-w-md border-dashed p-9 text-center shadow-none"><div class="mx-auto grid size-12 place-items-center rounded-2xl bg-secondary text-primary"><Sparkles class="size-5" /></div><h2 class="mt-4 text-lg font-extrabold">Workspace belum tersedia</h2><p class="mt-2 text-sm leading-6 text-muted-foreground">Verifikasi email untuk membuat workspace personal, lalu muat ulang halaman.</p></Card></div>

        <template v-else-if="activeSection === 'tasks'">
            <div class="mb-5 grid grid-cols-2 gap-3 xl:grid-cols-5">
                <Card class="border-border/80 p-4 shadow-none"><div class="flex items-center justify-between"><p class="text-xs font-bold text-muted-foreground">Semua task</p><LayoutGrid class="size-4 text-primary" /></div><p class="mt-3 font-mono text-2xl font-semibold">{{ counts.total }}</p></Card>
                <Card class="border-border/80 p-4 shadow-none"><div class="flex items-center justify-between"><p class="text-xs font-bold text-muted-foreground">Belum dikerjakan</p><Circle class="size-4 text-slate-400" /></div><p class="mt-3 font-mono text-2xl font-semibold">{{ counts.pending }}</p></Card>
                <Card class="border-border/80 p-4 shadow-none"><div class="flex items-center justify-between"><p class="text-xs font-bold text-muted-foreground">Sedang dikerjakan</p><CircleDot class="size-4 text-blue-600" /></div><p class="mt-3 font-mono text-2xl font-semibold">{{ counts.ongoing }}</p></Card>
                <Card class="border-border/80 p-4 shadow-none"><div class="flex items-center justify-between"><p class="text-xs font-bold text-muted-foreground">Mendesak</p><CalendarDays class="size-4 text-red-600" /></div><p class="mt-3 font-mono text-2xl font-semibold">{{ counts.urgent }}</p></Card>
                <Card class="border-border/80 p-4 shadow-none"><div class="flex items-center justify-between"><p class="text-xs font-bold text-muted-foreground">Selesai</p><CheckCircle2 class="size-4 text-emerald-600" /></div><p class="mt-3 font-mono text-2xl font-semibold">{{ counts.done }}</p></Card>
            </div>

            <Card class="mb-5 border-border/80 p-3 shadow-none">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex flex-1 flex-col gap-2 sm:flex-row">
                        <div class="relative min-w-0 flex-1 sm:max-w-md"><Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" /><Input v-model="search" class="h-10 pl-9" placeholder="Cari judul atau deskripsi..." /></div>
                        <div class="flex gap-2"><NativeSelect v-model="categoryFilter" aria-label="Filter kategori" class="h-10 min-w-40 flex-1"><NativeSelectOption value="">Semua kategori</NativeSelectOption><NativeSelectOption v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</NativeSelectOption></NativeSelect><NativeSelect v-model="statusFilter" aria-label="Filter status" class="h-10 min-w-40 flex-1"><NativeSelectOption value="">Semua status</NativeSelectOption><NativeSelectOption v-for="status in TODO_STATUSES" :key="status.value" :value="status.value">{{ status.label }}</NativeSelectOption></NativeSelect></div>
                    </div>
                    <Tabs :model-value="viewMode" @update:model-value="setView"><TabsList class="grid h-10 w-full grid-cols-2 xl:w-auto"><TabsTrigger value="board" aria-label="Board"><LayoutGrid class="size-4" /><span class="hidden sm:inline">Board</span></TabsTrigger><TabsTrigger value="list" aria-label="Daftar"><List class="size-4" /><span class="hidden sm:inline">Daftar</span></TabsTrigger></TabsList></Tabs>
                </div>
                <div v-if="search || categoryFilter || statusFilter" class="mt-3 flex items-center gap-2 border-t pt-3 text-xs text-muted-foreground"><SlidersHorizontal class="size-3.5" /><span>Menampilkan {{ filteredTodos.length }} dari {{ todos.length }} task</span><Button variant="link" size="xs" class="ml-auto h-auto p-0 text-xs" @click="search = ''; categoryFilter = ''; statusFilter = ''">Reset filter</Button></div>
            </Card>

            <div v-if="viewMode === 'board'" class="overflow-x-auto pb-3"><TaskBoard :todos="filteredTodos" @open="openTodo" @edit="editTodo" @delete="askDeleteTodo" @status="changeStatus" /></div>
            <TaskList v-else :todos="filteredTodos" @open="openTodo" @status="changeStatus" />
        </template>

        <TaskCalendar v-else-if="activeSection === 'calendar'" :workspace-id="activeWorkspace.id" :todos="todos" @open="openCalendarTodo" />
        <StickyNotesPanel v-else-if="activeSection === 'notes'" :notes="stickyNotes" :workspace-id="activeWorkspace.id" />
        <ActivityPanel v-else-if="activeSection === 'activity'" :activities="activities" />

        <TaskFormSheet v-if="activeWorkspace" v-model:open="formOpen" :todo="formTodo" :workspace-id="activeWorkspace.id" :categories="categories" />
        <TaskOverviewDialog v-model:open="overviewOpen" :todo="selectedTodo" @edit="editTodo" />
        <AlertDialog v-model:open="deleteOpen"><AlertDialogContent><AlertDialogHeader><AlertDialogTitle>Hapus task “{{ selectedTodo?.title }}”?</AlertDialogTitle><AlertDialogDescription>Task dan reminder terkait akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.</AlertDialogDescription></AlertDialogHeader><AlertDialogFooter><AlertDialogCancel>Batal</AlertDialogCancel><AlertDialogAction class="bg-destructive text-white hover:bg-destructive/90" @click="deleteTodo">Hapus permanen</AlertDialogAction></AlertDialogFooter></AlertDialogContent></AlertDialog>
    </AppLayout>
</template>
