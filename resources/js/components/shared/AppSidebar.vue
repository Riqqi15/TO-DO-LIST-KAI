<script setup>
import kaiLogo from '@/assets/kai-logo.svg';
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
    Settings,
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

const emit = defineEmits(['navigate', 'switch-workspace', 'close', 'open-settings']);

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
        <!-- Sleek Header -->
        <div class="flex h-16 items-center gap-3 px-4 border-b border-sidebar-border/60">
            <img :src="kaiLogo" alt="Logo KAI" class="h-6 w-auto object-contain shrink-0" />
            <div class="min-w-0 flex-1">
                <p class="text-xs font-bold tracking-tight text-foreground">To Do List KAI</p>
                <p class="text-[10px] font-medium text-muted-foreground uppercase tracking-widest">Workspace</p>
            </div>
        </div>

        <ScrollArea class="min-h-0 flex-1">
            <div class="p-3">
                <SidebarWorkspaceTools
                    :workspaces="workspaces"
                    :active-workspace="activeWorkspace"
                    :categories="categories"
                    :user="user"
                    :invite="invite"
                    @switch-workspace="switchWorkspace"
                />
            </div>

            <Separator class="my-1 opacity-60" />

            <nav class="space-y-0.5 p-3" aria-label="Navigasi utama">
                <p class="mb-1.5 px-2 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                    Navigasi
                </p>
                <Button
                    v-for="item in navigation"
                    :key="item.id"
                    variant="ghost"
                    class="h-9 w-full justify-start gap-2.5 px-2.5 text-xs font-medium transition-colors"
                    :class="activeSection === item.id 
                        ? 'bg-primary/10 text-primary font-bold hover:bg-primary/15' 
                        : 'text-muted-foreground hover:bg-sidebar-accent hover:text-foreground'"
                    @click="navigate(item.id)"
                >
                    <component :is="item.icon" class="size-4 shrink-0" />
                    {{ item.label }}
                </Button>
            </nav>
        </ScrollArea>

        <!-- User profile footer -->
        <div class="mt-auto border-t border-sidebar-border/60 p-3">
            <div class="flex items-center gap-2.5 rounded-lg p-1.5 transition-colors hover:bg-sidebar-accent">
                <Avatar class="size-8 border border-border/60">
                    <AvatarFallback class="bg-secondary text-[10px] font-bold text-secondary-foreground">
                        {{ initials(user?.name) }}
                    </AvatarFallback>
                </Avatar>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-bold leading-tight text-foreground">{{ user?.name ?? 'Pengguna' }}</p>
                    <p class="truncate text-[10px] text-muted-foreground leading-tight mt-0.5">{{ user?.email }}</p>
                </div>
                <Button variant="ghost" size="icon-xs" class="text-muted-foreground hover:text-foreground" type="button" title="Pengaturan akun" @click="emit('open-settings')">
                    <Settings class="size-3.5" />
                </Button>
                <Button variant="ghost" size="icon-xs" class="text-muted-foreground hover:text-destructive" as-child title="Keluar">
                    <Link href="/logout" method="post" as="button" aria-label="Keluar">
                        <LogOut class="size-3.5" />
                    </Link>
                </Button>
            </div>
        </div>
    </div>
</template>
