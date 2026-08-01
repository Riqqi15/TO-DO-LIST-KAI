<script setup>
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Separator } from '@/components/ui/separator';
import { Link } from '@inertiajs/vue3';
import {
    Activity,
    CalendarDays,
    CheckCheck,
    LayoutDashboard,
    LogOut,
    Settings2,
    StickyNote,
    Users,
} from '@lucide/vue';

const props = defineProps({
    workspaces: { type: Array, default: () => [] },
    activeWorkspace: { type: Object, default: null },
    activeSection: { type: String, default: 'tasks' },
    user: { type: Object, default: null },
});

const emit = defineEmits(['navigate', 'switch-workspace', 'close']);

const navigation = [
    { id: 'tasks', label: 'Tasks', icon: LayoutDashboard },
    { id: 'calendar', label: 'Kalender', icon: CalendarDays },
    { id: 'notes', label: 'Sticky Notes', icon: StickyNote },
    { id: 'activity', label: 'Activity', icon: Activity },
    { id: 'settings', label: 'Pengaturan', icon: Settings2 },
];

const initials = (name) => (name || 'KAI')
    .split(' ')
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase();

const navigate = (section) => {
    emit('navigate', section);
    emit('close');
};
</script>

<template>
    <div class="flex h-full flex-col bg-sidebar text-sidebar-foreground">
        <div class="flex h-20 items-center gap-3 px-5">
            <div class="grid size-10 place-items-center rounded-xl bg-primary text-primary-foreground shadow-sm shadow-primary/20">
                <CheckCheck class="size-5" stroke-width="2.4" />
            </div>
            <div>
                <p class="text-sm font-extrabold tracking-[-0.02em]">To Do List KAI</p>
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">Workspace</p>
            </div>
        </div>

        <div class="px-4 pb-4">
            <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.14em] text-muted-foreground" for="workspace-switcher">
                Workspace aktif
            </label>
            <NativeSelect
                id="workspace-switcher"
                class="h-11 w-full bg-white font-semibold"
                :model-value="activeWorkspace?.id ?? ''"
                @change="emit('switch-workspace', $event.target.value)"
            >
                <NativeSelectOption
                    v-for="workspace in workspaces"
                    :key="workspace.id"
                    :value="workspace.id"
                >
                    {{ workspace.name }}
                </NativeSelectOption>
            </NativeSelect>
            <div v-if="activeWorkspace" class="mt-2 flex items-center gap-2 px-1 text-xs text-muted-foreground">
                <Users class="size-3.5" />
                <span>
                    {{ activeWorkspace.type === 'team' ? `${activeWorkspace.membership_rows_count ?? 1} anggota` : 'Workspace personal' }}
                </span>
            </div>
        </div>

        <Separator />

        <nav class="flex-1 space-y-1.5 p-3" aria-label="Navigasi utama">
            <Button
                v-for="item in navigation"
                :key="item.id"
                :variant="activeSection === item.id ? 'secondary' : 'ghost'"
                class="h-10 w-full justify-start gap-3 px-3"
                :class="activeSection === item.id ? 'font-bold text-primary' : 'font-medium text-muted-foreground'"
                @click="navigate(item.id)"
            >
                <component :is="item.icon" class="size-4.5" />
                {{ item.label }}
            </Button>
        </nav>

        <div class="mt-auto p-3">
            <Separator class="mb-3" />
            <div class="flex items-center gap-3 rounded-xl p-2">
                <Avatar class="size-9 border border-border">
                    <AvatarFallback class="bg-secondary text-xs font-bold text-secondary-foreground">
                        {{ initials(user?.name) }}
                    </AvatarFallback>
                </Avatar>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold">{{ user?.name ?? 'Pengguna' }}</p>
                    <p class="truncate text-xs text-muted-foreground">{{ user?.email }}</p>
                </div>
                <Button variant="ghost" size="icon-sm" as-child title="Keluar">
                    <Link href="/logout" method="post" as="button" aria-label="Keluar">
                        <LogOut class="size-4" />
                    </Link>
                </Button>
            </div>
        </div>
    </div>
</template>
