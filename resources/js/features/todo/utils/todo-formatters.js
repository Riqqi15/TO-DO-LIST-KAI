import { TODO_STATUSES } from '@/features/todo/constants/todo-options';

export const statusLabel = (status) => TODO_STATUSES.find((item) => item.value === status)?.label ?? status;

export const statusTone = (status) => ({
    belum_dikerjakan: 'bg-slate-100 text-slate-700',
    sedang_dikerjakan: 'bg-blue-50 text-blue-700',
    selesai: 'bg-emerald-50 text-emerald-700',
}[status] ?? 'bg-muted text-muted-foreground');

export const formatDateTime = (value) => {
    if (!value) return '-';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'Asia/Jakarta',
    }).format(date).replace('.', ':');
};

export const formatShortDate = (value) => {
    if (!value) return '-';
    const date = new Date(value);
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'Asia/Jakarta',
    }).format(date).replace('.', ':');
};

export const toWibDateTimeInput = (value = new Date()) => {
    const date = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    const parts = Object.fromEntries(new Intl.DateTimeFormat('en-CA', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
        timeZone: 'Asia/Jakarta',
    }).formatToParts(date).map(({ type, value: part }) => [type, part]));
    return `${parts.year}-${parts.month}-${parts.day}T${parts.hour}:${parts.minute}`;
};

export const statusDateMeta = (todo) => {
    if (todo.status === 'sedang_dikerjakan') {
        return { label: 'Mulai', value: todo.started_at, tone: 'text-blue-700' };
    }
    if (todo.status === 'selesai') {
        return { label: 'Selesai', value: todo.completed_at, tone: 'text-emerald-700' };
    }
    return { label: 'Deadline', value: todo.deadline_at, tone: deadlineMeta(todo).tone };
};

export const statusDateInput = (todo, status) => {
    if (!todo) return '';
    if (status === 'belum_dikerjakan') return todo.deadline_wib?.replace(' ', 'T') ?? toWibDateTimeInput(todo.deadline_at);
    if (status === 'sedang_dikerjakan') return toWibDateTimeInput(todo.started_at);
    return toWibDateTimeInput(todo.completed_at);
};

export const deadlineMeta = (todo) => {
    if (todo.status === 'selesai') {
        return { label: 'Selesai', color: '#12806a', tone: 'text-emerald-700', icon: 'check' };
    }

    const deadline = new Date(todo.deadline_at);
    const hours = (deadline.getTime() - Date.now()) / 3_600_000;
    if (hours < 0) return { label: 'Terlambat', color: '#c23b3b', tone: 'text-red-700', icon: 'alert' };
    if (hours <= 24) return { label: 'Kurang dari 24 jam', color: '#c23b3b', tone: 'text-red-700', icon: 'clock' };
    if (hours <= 72) return { label: 'Mendekati deadline', color: '#b76e00', tone: 'text-amber-700', icon: 'clock' };
    return { label: 'Terjadwal', color: '#3157d5', tone: 'text-blue-700', icon: 'calendar' };
};

export const defaultDeadline = (days = 1) => {
    const date = new Date(Date.now() + days * 24 * 60 * 60 * 1000);
    date.setSeconds(0, 0);
    const offset = date.getTimezoneOffset() * 60_000;
    return new Date(date.getTime() - offset).toISOString().slice(0, 16);
};

export const toDateTimeInput = (todo) => todo?.deadline_wib?.replace(' ', 'T') ?? defaultDeadline();

export const reminderKindLabel = (kind) => ({
    automatic_7_days: 'Otomatis H-7',
    automatic_3_days: 'Otomatis H-3',
    manual: 'Manual',
}[kind] ?? kind);

export const reminderStatusLabel = (status) => ({
    scheduled: 'Terjadwal',
    cancelled: 'Dibatalkan',
    sent: 'Terkirim',
    failed: 'Gagal',
}[status] ?? status);

export const activityLabel = (action) => ({
    'todo.created': 'Task dibuat',
    'todo.updated': 'Task diperbarui',
    'todo.deleted': 'Task dihapus',
    'todo.status_changed': 'Status task diubah',
    'category.created': 'Kategori dibuat',
    'category.updated': 'Kategori diperbarui',
    'category.deleted': 'Kategori dihapus',
    'sticky_note.created': 'Sticky note dibuat',
    'sticky_note.updated': 'Sticky note diperbarui',
    'sticky_note.deleted': 'Sticky note dihapus',
    'sticky_note.pinned': 'Sticky note dipin',
    'sticky_note.unpinned': 'Pin sticky note dilepas',
    'sticky_note.pins_reordered': 'Urutan pin diperbarui',
    'team.created': 'Tim dibuat',
    'team.joined': 'Anggota bergabung',
    'team.left': 'Anggota keluar',
    'team.capacity_updated': 'Kapasitas tim diperbarui',
    'team.ownership_transferred': 'Kepemilikan dipindahkan',
}[action] ?? action.replaceAll('.', ' '));

export const summarizeActivity = (activity) => {
    const data = activity.changes ?? activity.snapshot;
    if (!data) return 'Tidak ada rincian tambahan.';
    if (typeof data === 'string') return data;
    const source = data.new ?? data.status ?? data;
    const entries = Object.entries(source).slice(0, 3);
    return entries.map(([key, value]) => `${key.replaceAll('_', ' ')}: ${typeof value === 'object' ? JSON.stringify(value) : value}`).join(' · ');
};
