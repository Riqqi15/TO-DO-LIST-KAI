<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { TODO_STATUSES } from '@/features/todo/constants/todo-options';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage();

const props = computed(() => page.props);
const workspaces = computed(() => props.value.workspaces ?? []);
const activeWorkspace = computed(() => props.value.activeWorkspace ?? null);
const categories = computed(() => props.value.categories ?? []);
const todos = computed(() => props.value.todos ?? []);
const stickyNotes = computed(() => props.value.stickyNotes ?? []);
const activities = computed(() => props.value.activities ?? []);
const errors = computed(() => props.value.errors ?? {});
const flash = computed(() => props.value.flash ?? {});
const user = computed(() => props.value.auth?.user ?? null);

const editableCategories = computed(() =>
    categories.value.filter((category) => !category.is_system),
);

const selectedWorkspaceId = ref(activeWorkspace.value?.id ?? '');

watch(activeWorkspace, (workspace) => {
    selectedWorkspaceId.value = workspace?.id ?? '';
});

const defaultDeadline = () => {
    const date = new Date(Date.now() + 24 * 60 * 60 * 1000);
    date.setSeconds(0, 0);

    return date.toISOString().slice(0, 16);
};

const todoForm = useForm({
    category_id: '',
    title: '',
    description: '',
    deadline_at: defaultDeadline(),
    manual_reminders: [],
});

const manualReminderDraft = ref('');

const categoryForm = useForm({ name: '' });
const teamForm = useForm({ name: '' });
const joinForm = useForm({ code: '' });
const noteForm = useForm({ content: '', color: 'yellow' });
const reminderForms = ref({});
const statusForms = ref({});
const updateCategoryNames = ref({});
const updateNoteForms = ref({});
const convertForms = ref({});
const capacityForm = useForm({ member_limit: 5 });
const deleteTeamForm = useForm({ confirmation: '' });

watch(categories, (items) => {
    if (!todoForm.category_id && items.length > 0) {
        todoForm.category_id = items[0].id;
    }
}, { immediate: true });

watch(activeWorkspace, (workspace) => {
    capacityForm.member_limit = workspace?.member_limit ?? 5;
    deleteTeamForm.confirmation = '';
});

const switchWorkspace = () => {
    if (!selectedWorkspaceId.value) {
        return;
    }

    router.get('/app', { workspace: selectedWorkspaceId.value }, {
        preserveScroll: true,
    });
};

const addManualReminderDraft = () => {
    if (!manualReminderDraft.value) {
        return;
    }

    todoForm.manual_reminders.push(manualReminderDraft.value);
    manualReminderDraft.value = '';
};

const removeManualReminderDraft = (index) => {
    todoForm.manual_reminders.splice(index, 1);
};

const createTodo = () => {
    if (!activeWorkspace.value) {
        return;
    }

    todoForm.post(`/workspaces/${activeWorkspace.value.id}/todos`, {
        preserveScroll: true,
        onSuccess: () => {
            todoForm.reset('title', 'description', 'manual_reminders');
            todoForm.deadline_at = defaultDeadline();
        },
    });
};

const todoStatusLabel = (status) =>
    TODO_STATUSES.find((item) => item.value === status)?.label ?? status;

const createCategory = () => {
    if (!activeWorkspace.value) {
        return;
    }

    categoryForm.post(`/workspaces/${activeWorkspace.value.id}/categories`, {
        preserveScroll: true,
        onSuccess: () => categoryForm.reset(),
    });
};

const updateCategory = (category) => {
    router.patch(`/categories/${category.id}`, {
        name: updateCategoryNames.value[category.id] ?? category.name,
    }, { preserveScroll: true });
};

const deleteCategory = (category) => {
    if (!window.confirm(`Hapus kategori "${category.name}"?`)) {
        return;
    }

    router.delete(`/categories/${category.id}`, { preserveScroll: true });
};

const createTeam = () => {
    teamForm.post('/teams', {
        preserveScroll: true,
        onSuccess: () => teamForm.reset(),
    });
};

const joinTeam = () => {
    joinForm.post('/teams/join', {
        preserveScroll: true,
        onSuccess: () => joinForm.reset(),
    });
};

const generateInvite = () => {
    if (!activeWorkspace.value) {
        return;
    }

    router.post(`/workspaces/${activeWorkspace.value.id}/invite`, {}, {
        preserveScroll: true,
    });
};

const updateCapacity = () => {
    if (!activeWorkspace.value) {
        return;
    }

    capacityForm.patch(`/workspaces/${activeWorkspace.value.id}/capacity`, {
        preserveScroll: true,
    });
};

const leaveTeam = () => {
    if (!activeWorkspace.value || !window.confirm('Keluar dari tim ini?')) {
        return;
    }

    router.delete(`/workspaces/${activeWorkspace.value.id}/leave`);
};

const deleteTeam = () => {
    if (!activeWorkspace.value) {
        return;
    }

    deleteTeamForm.delete(`/workspaces/${activeWorkspace.value.id}`, {
        preserveScroll: true,
    });
};

const createNote = () => {
    if (!activeWorkspace.value) {
        return;
    }

    noteForm.post(`/workspaces/${activeWorkspace.value.id}/sticky-notes`, {
        preserveScroll: true,
        onSuccess: () => noteForm.reset('content'),
    });
};

const notePayload = (note) => {
    if (!updateNoteForms.value[note.id]) {
        updateNoteForms.value[note.id] = {
            content: note.content,
            color: note.color,
        };
    }

    return updateNoteForms.value[note.id];
};

const updateNote = (note) => {
    router.patch(`/sticky-notes/${note.id}`, notePayload(note), {
        preserveScroll: true,
    });
};

const deleteNote = (note) => {
    if (!window.confirm('Hapus sticky note ini?')) {
        return;
    }

    router.delete(`/sticky-notes/${note.id}`, { preserveScroll: true });
};

const convertForm = (note) => {
    if (!convertForms.value[note.id]) {
        convertForms.value[note.id] = {
            category_id: categories.value[0]?.id ?? '',
            title: note.content.slice(0, 80),
            description: note.content,
            deadline_at: defaultDeadline(),
            manual_reminders: [],
        };
    }

    return convertForms.value[note.id];
};

const convertNote = (note) => {
    router.post(`/sticky-notes/${note.id}/convert`, convertForm(note), {
        preserveScroll: true,
    });
};

const deleteTodo = (todo) => {
    if (!window.confirm(`Hapus task "${todo.title}"?`)) {
        return;
    }

    router.delete(`/todos/${todo.id}`, { preserveScroll: true });
};

const statusForm = (todo) => {
    if (!statusForms.value[todo.id]) {
        statusForms.value[todo.id] = {
            status: todo.status,
            manual_reminder_at: '',
        };
    }

    return statusForms.value[todo.id];
};

const changeStatus = (todo) => {
    router.patch(`/todos/${todo.id}/status`, statusForm(todo), {
        preserveScroll: true,
    });
};

const reminderForm = (todo) => {
    if (!reminderForms.value[todo.id]) {
        reminderForms.value[todo.id] = { scheduled_at: '' };
    }

    return reminderForms.value[todo.id];
};

const createReminder = (todo) => {
    router.post(`/todos/${todo.id}/reminders`, reminderForm(todo), {
        preserveScroll: true,
        onSuccess: () => {
            reminderForms.value[todo.id] = { scheduled_at: '' };
        },
    });
};

const deleteReminder = (reminder) => {
    router.delete(`/reminders/${reminder.id}`, { preserveScroll: true });
};

const hasErrors = computed(() => Object.keys(errors.value).length > 0);

const formatPayload = (value) => {
    if (!value) {
        return '-';
    }

    if (typeof value === 'string') {
        return value;
    }

    return JSON.stringify(value, null, 2);
};
</script>

<template>
    <AppLayout>
        <div class="mx-auto max-w-7xl space-y-6">
            <header class="rounded-lg border border-slate-200 bg-white p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase text-slate-500">
                            UI test backend
                        </p>
                        <h1 class="mt-1 text-2xl font-bold text-slate-950">
                            To Do List KAI
                        </h1>
                        <p class="mt-1 text-sm text-slate-600">
                            Login sebagai {{ user?.name ?? user?.email ?? 'user' }}.
                        </p>
                    </div>

                    <label class="grid gap-1 text-sm font-medium text-slate-700">
                        Workspace aktif
                        <select
                            v-model="selectedWorkspaceId"
                            class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 lg:w-80"
                            @change="switchWorkspace"
                        >
                            <option
                                v-for="workspace in workspaces"
                                :key="workspace.id"
                                :value="workspace.id"
                            >
                                {{ workspace.name }} ({{ workspace.type }})
                            </option>
                        </select>
                    </label>
                </div>
            </header>

            <div
                v-if="flash.success || flash.team_invite || flash.todo_id || flash.workspace_id"
                class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900"
            >
                <p
                    v-if="flash.success"
                    class="font-semibold"
                >
                    {{ flash.success }}
                </p>
                <p v-if="flash.team_invite">
                    Kode invite: <strong>{{ flash.team_invite.code }}</strong>
                    berlaku sampai {{ flash.team_invite.expires_at }}.
                </p>
                <p v-if="flash.workspace_id">
                    Workspace ID: {{ flash.workspace_id }}
                </p>
                <p v-if="flash.todo_id">
                    Todo ID: {{ flash.todo_id }}
                </p>
            </div>

            <div
                v-if="hasErrors"
                class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800"
            >
                <p class="font-semibold">Validation error</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li
                        v-for="(message, field) in errors"
                        :key="field"
                    >
                        {{ field }}: {{ message }}
                    </li>
                </ul>
            </div>

            <div
                v-if="!activeWorkspace"
                class="rounded-lg border border-slate-200 bg-white p-8 text-center text-slate-600"
            >
                Belum ada workspace aktif. Buat tim atau selesaikan verifikasi email agar workspace personal tersedia.
            </div>

            <template v-else>
                <section class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                    <form
                        class="rounded-lg border border-slate-200 bg-white p-5"
                        @submit.prevent="createTodo"
                    >
                        <h2 class="text-lg font-semibold text-slate-950">
                            Buat task
                        </h2>
                        <div class="mt-4 grid gap-3 md:grid-cols-2">
                            <label class="grid gap-1 text-sm font-medium text-slate-700 md:col-span-2">
                                Judul
                                <input
                                    v-model="todoForm.title"
                                    class="rounded-md border border-slate-300 px-3 py-2"
                                    maxlength="180"
                                    required
                                >
                            </label>
                            <label class="grid gap-1 text-sm font-medium text-slate-700">
                                Kategori
                                <select
                                    v-model="todoForm.category_id"
                                    class="rounded-md border border-slate-300 px-3 py-2"
                                    required
                                >
                                    <option
                                        v-for="category in categories"
                                        :key="category.id"
                                        :value="category.id"
                                    >
                                        {{ category.name }}
                                    </option>
                                </select>
                            </label>
                            <label class="grid gap-1 text-sm font-medium text-slate-700">
                                Deadline WIB
                                <input
                                    v-model="todoForm.deadline_at"
                                    type="datetime-local"
                                    class="rounded-md border border-slate-300 px-3 py-2"
                                    required
                                >
                            </label>
                            <label class="grid gap-1 text-sm font-medium text-slate-700 md:col-span-2">
                                Deskripsi
                                <textarea
                                    v-model="todoForm.description"
                                    class="min-h-24 rounded-md border border-slate-300 px-3 py-2"
                                />
                            </label>
                        </div>

                        <div class="mt-4 rounded-md border border-slate-200 p-3">
                            <p class="text-sm font-semibold text-slate-700">
                                Reminder manual opsional
                            </p>
                            <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                                <input
                                    v-model="manualReminderDraft"
                                    type="datetime-local"
                                    class="rounded-md border border-slate-300 px-3 py-2"
                                >
                                <button
                                    type="button"
                                    class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold"
                                    @click="addManualReminderDraft"
                                >
                                    Tambah reminder
                                </button>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <button
                                    v-for="(reminder, index) in todoForm.manual_reminders"
                                    :key="`${reminder}-${index}`"
                                    type="button"
                                    class="rounded-full bg-slate-100 px-3 py-1 text-xs"
                                    @click="removeManualReminderDraft(index)"
                                >
                                    {{ reminder }} x
                                </button>
                            </div>
                        </div>

                        <button
                            type="submit"
                            class="mt-4 rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
                            :disabled="todoForm.processing"
                        >
                            Simpan task
                        </button>
                    </form>

                    <div class="space-y-6">
                        <form
                            class="rounded-lg border border-slate-200 bg-white p-5"
                            @submit.prevent="createCategory"
                        >
                            <h2 class="text-lg font-semibold text-slate-950">
                                Kategori
                            </h2>
                            <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                                <input
                                    v-model="categoryForm.name"
                                    class="rounded-md border border-slate-300 px-3 py-2"
                                    placeholder="Nama kategori"
                                    required
                                >
                                <button
                                    type="submit"
                                    class="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white"
                                    :disabled="categoryForm.processing"
                                >
                                    Buat
                                </button>
                            </div>
                            <div class="mt-3 space-y-2">
                                <div
                                    v-for="category in editableCategories"
                                    :key="category.id"
                                    class="flex gap-2"
                                >
                                    <input
                                        v-model="updateCategoryNames[category.id]"
                                        class="min-w-0 flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm"
                                        :placeholder="category.name"
                                    >
                                    <button
                                        type="button"
                                        class="rounded-md border border-slate-300 px-3 py-2 text-sm"
                                        @click="updateCategory(category)"
                                    >
                                        Ubah
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-md border border-red-200 px-3 py-2 text-sm text-red-700"
                                        @click="deleteCategory(category)"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="rounded-lg border border-slate-200 bg-white p-5">
                            <h2 class="text-lg font-semibold text-slate-950">
                                Tim
                            </h2>
                            <form class="mt-4 flex flex-col gap-2 sm:flex-row" @submit.prevent="createTeam">
                                <input
                                    v-model="teamForm.name"
                                    class="rounded-md border border-slate-300 px-3 py-2"
                                    placeholder="Nama tim baru"
                                >
                                <button class="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white">
                                    Buat tim
                                </button>
                            </form>
                            <form class="mt-3 flex flex-col gap-2 sm:flex-row" @submit.prevent="joinTeam">
                                <input
                                    v-model="joinForm.code"
                                    class="rounded-md border border-slate-300 px-3 py-2 uppercase"
                                    maxlength="8"
                                    placeholder="Kode tim"
                                >
                                <button class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold">
                                    Join
                                </button>
                            </form>

                            <div
                                v-if="activeWorkspace.type === 'team'"
                                class="mt-4 space-y-3 border-t border-slate-200 pt-4"
                            >
                                <button
                                    type="button"
                                    class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold"
                                    @click="generateInvite"
                                >
                                    Generate kode invite
                                </button>
                                <form class="flex gap-2" @submit.prevent="updateCapacity">
                                    <select
                                        v-model="capacityForm.member_limit"
                                        class="rounded-md border border-slate-300 px-3 py-2"
                                    >
                                        <option :value="5">5 anggota</option>
                                        <option :value="10">10 anggota</option>
                                    </select>
                                    <button class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                                        Ubah kapasitas
                                    </button>
                                </form>
                                <button
                                    type="button"
                                    class="rounded-md border border-slate-300 px-3 py-2 text-sm"
                                    @click="leaveTeam"
                                >
                                    Keluar tim
                                </button>
                                <form class="grid gap-2" @submit.prevent="deleteTeam">
                                    <input
                                        v-model="deleteTeamForm.confirmation"
                                        class="rounded-md border border-red-200 px-3 py-2 text-sm"
                                        :placeholder="`konfirmasi hapus tim ${activeWorkspace.name}`"
                                    >
                                    <button class="rounded-md bg-red-700 px-3 py-2 text-sm font-semibold text-white">
                                        Hapus tim permanen
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-lg font-semibold text-slate-950">
                            Task
                        </h2>
                        <p class="text-sm text-slate-500">
                            {{ todos.length }} item
                        </p>
                    </div>
                    <div class="mt-4 grid gap-3">
                        <article
                            v-for="todo in todos"
                            :key="todo.id"
                            class="rounded-md border border-slate-200 p-4"
                        >
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase text-slate-500">
                                        {{ todo.category?.name ?? 'Tanpa kategori' }} | {{ todo.deadline_wib }}
                                    </p>
                                    <h3 class="mt-1 text-base font-semibold text-slate-950">
                                        {{ todo.title }}
                                    </h3>
                                    <p class="mt-1 whitespace-pre-line text-sm text-slate-600">
                                        {{ todo.description || '-' }}
                                    </p>
                                    <p class="mt-2 text-sm font-medium text-slate-700">
                                        Status: {{ todoStatusLabel(todo.status) }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="rounded-md border border-red-200 px-3 py-2 text-sm font-semibold text-red-700"
                                    @click="deleteTodo(todo)"
                                >
                                    Hapus task
                                </button>
                            </div>

                            <div class="mt-4 grid gap-3 border-t border-slate-200 pt-4 lg:grid-cols-2">
                                <form class="flex flex-col gap-2 sm:flex-row" @submit.prevent="changeStatus(todo)">
                                    <select
                                        v-model="statusForm(todo).status"
                                        class="rounded-md border border-slate-300 px-3 py-2 text-sm"
                                    >
                                        <option
                                            v-for="status in TODO_STATUSES"
                                            :key="status.value"
                                            :value="status.value"
                                        >
                                            {{ status.label }}
                                        </option>
                                    </select>
                                    <input
                                        v-model="statusForm(todo).manual_reminder_at"
                                        type="datetime-local"
                                        class="rounded-md border border-slate-300 px-3 py-2 text-sm"
                                        title="Diisi saat reopen butuh reminder manual"
                                    >
                                    <button class="rounded-md bg-slate-950 px-3 py-2 text-sm font-semibold text-white">
                                        Ubah status
                                    </button>
                                </form>

                                <form class="flex flex-col gap-2 sm:flex-row" @submit.prevent="createReminder(todo)">
                                    <input
                                        v-model="reminderForm(todo).scheduled_at"
                                        type="datetime-local"
                                        class="rounded-md border border-slate-300 px-3 py-2 text-sm"
                                        required
                                    >
                                    <button class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold">
                                        Tambah reminder
                                    </button>
                                </form>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <button
                                    v-for="reminder in todo.reminders ?? []"
                                    :key="reminder.id"
                                    type="button"
                                    class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700"
                                    @click="deleteReminder(reminder)"
                                >
                                    {{ reminder.kind }} | {{ reminder.status }} | hapus
                                </button>
                            </div>
                        </article>
                        <p
                            v-if="todos.length === 0"
                            class="rounded-md border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500"
                        >
                            Belum ada task di workspace ini.
                        </p>
                    </div>
                </section>

                <section class="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
                    <div class="rounded-lg border border-slate-200 bg-white p-5">
                        <h2 class="text-lg font-semibold text-slate-950">
                            Sticky note
                        </h2>
                        <form class="mt-4 grid gap-3" @submit.prevent="createNote">
                            <textarea
                                v-model="noteForm.content"
                                class="min-h-24 rounded-md border border-slate-300 px-3 py-2"
                                placeholder="Isi catatan"
                                required
                            />
                            <select
                                v-model="noteForm.color"
                                class="rounded-md border border-slate-300 px-3 py-2"
                            >
                                <option value="yellow">Yellow</option>
                                <option value="blue">Blue</option>
                                <option value="green">Green</option>
                                <option value="pink">Pink</option>
                                <option value="purple">Purple</option>
                            </select>
                            <button class="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white">
                                Buat note
                            </button>
                        </form>

                        <div class="mt-4 space-y-3">
                            <article
                                v-for="note in stickyNotes"
                                :key="note.id"
                                class="rounded-md border border-slate-200 p-3"
                            >
                                <textarea
                                    v-model="notePayload(note).content"
                                    class="min-h-20 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                />
                                <select
                                    v-model="notePayload(note).color"
                                    class="mt-2 rounded-md border border-slate-300 px-3 py-2 text-sm"
                                >
                                    <option value="yellow">Yellow</option>
                                    <option value="blue">Blue</option>
                                    <option value="green">Green</option>
                                    <option value="pink">Pink</option>
                                    <option value="purple">Purple</option>
                                </select>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="rounded-md border border-slate-300 px-3 py-2 text-sm"
                                        @click="updateNote(note)"
                                    >
                                        Ubah
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-md border border-red-200 px-3 py-2 text-sm text-red-700"
                                        @click="deleteNote(note)"
                                    >
                                        Hapus
                                    </button>
                                </div>
                                <form class="mt-3 grid gap-2" @submit.prevent="convertNote(note)">
                                    <input
                                        v-model="convertForm(note).title"
                                        class="rounded-md border border-slate-300 px-3 py-2 text-sm"
                                        placeholder="Judul task hasil convert"
                                    >
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        <select
                                            v-model="convertForm(note).category_id"
                                            class="rounded-md border border-slate-300 px-3 py-2 text-sm"
                                        >
                                            <option
                                                v-for="category in categories"
                                                :key="category.id"
                                                :value="category.id"
                                            >
                                                {{ category.name }}
                                            </option>
                                        </select>
                                        <input
                                            v-model="convertForm(note).deadline_at"
                                            type="datetime-local"
                                            class="rounded-md border border-slate-300 px-3 py-2 text-sm"
                                        >
                                    </div>
                                    <button class="rounded-md bg-slate-950 px-3 py-2 text-sm font-semibold text-white">
                                        Convert ke task
                                    </button>
                                </form>
                            </article>
                        </div>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-white p-5">
                        <h2 class="text-lg font-semibold text-slate-950">
                            Activity log
                        </h2>
                        <div class="mt-4 max-h-[32rem] space-y-2 overflow-auto">
                            <article
                                v-for="activity in activities"
                                :key="activity.id"
                                class="rounded-md border border-slate-200 p-3 text-sm"
                            >
                                <p class="font-semibold text-slate-900">
                                    {{ activity.action }}
                                </p>
                                <p class="text-slate-600">
                                    {{ activity.actor?.name ?? 'System' }} | {{ activity.created_at }}
                                </p>
                                <pre class="mt-2 overflow-auto rounded bg-slate-50 p-2 text-xs text-slate-600">{{ formatPayload(activity.snapshot ?? activity.changes) }}</pre>
                            </article>
                            <p
                                v-if="activities.length === 0"
                                class="rounded-md border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500"
                            >
                                Belum ada activity log.
                            </p>
                        </div>
                    </div>
                </section>
            </template>
        </div>
    </AppLayout>
</template>
