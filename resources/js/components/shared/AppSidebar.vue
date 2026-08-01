<script setup>
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import SidebarWorkspaceTools from '@/features/todo/components/SidebarWorkspaceTools.vue';
import { Link } from '@inertiajs/vue3';
import {
    Activity,
    CalendarDays,
    CheckCheck,
    LayoutDashboard,
    LogOut,
    StickyNote,
} from '@lucide/vue';

const props = defineProps({
    workspaces: { type: Array, default: () => [] },
    activeWorkspace: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
    invite: { type: Object, default: null },
    activeSection: { type: String, default: 'tasks' },
    user: { type: Object, default: null },
});

const emit = defineEmits(['navigate', 'switch-workspace', 'close']);

const navigation = [
    { id: 'tasks', label: 'Tugas', icon: LayoutDashboard },
    { id: 'calendar', label: 'Kalender', icon: CalendarDays },
    { id: 'notes', label: 'Catatan', icon: StickyNote },
    { id: 'activity', label: 'Aktivitas', icon: Activity },
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

const switchWorkspace = (workspaceId) => {
    emit('switch-workspace', workspaceId);
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

        <ScrollArea class="min-h-0 flex-1">
            <div class="px-3 pb-3">
                <SidebarWorkspaceTools
                    :workspaces="workspaces"
                    :active-workspace="activeWorkspace"
                    :categories="categories"
                    :user="user"
                    :invite="invite"
                    @switch-workspace="switchWorkspace"
                />
            </div>

            <Separator />

            <nav class="space-y-1.5 p-3" aria-label="Navigasi utama">
                <p class="mb-2 px-1 text-[10px] font-bold uppercase tracking-[0.15em] text-muted-foreground">Navigasi</p>
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
        </ScrollArea>

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
