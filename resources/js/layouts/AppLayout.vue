<script setup>
import AppSidebar from '@/components/shared/AppSidebar.vue';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import UserSettingsDialog from '@/features/user/components/UserSettingsDialog.vue';
import { Menu } from '@lucide/vue';
import { ref } from 'vue';

defineProps({
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    eyebrow: { type: String, default: '' },
    workspaces: { type: Array, default: () => [] },
    activeWorkspace: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
    invite: { type: Object, default: null },
    activeSection: { type: String, default: 'tasks' },
    user: { type: Object, default: null },
});

const emit = defineEmits(['navigate', 'switch-workspace']);
const mobileOpen = ref(false);
const sidebarOpen = ref(true); // Default open as requested
const userSettingsOpen = ref(false);
</script>

<template>
    <TooltipProvider :delay-duration="250">
        <div class="min-h-screen bg-background text-foreground">
            <!-- Sidebar Desktop (Slide-out) -->
            <aside 
                class="fixed inset-y-0 left-0 z-30 hidden w-64 border-r border-sidebar-border bg-sidebar transition-transform duration-300 ease-in-out lg:block"
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            >
                <AppSidebar
                    :workspaces="workspaces"
                    :active-workspace="activeWorkspace"
                    :categories="categories"
                    :invite="invite"
                    :active-section="activeSection"
                    :user="user"
                    @navigate="emit('navigate', $event)"
                    @switch-workspace="emit('switch-workspace', $event)"
                    @open-settings="userSettingsOpen = true"
                />
            </aside>

            <div class="transition-all duration-300 ease-in-out" :class="sidebarOpen ? 'lg:pl-64' : 'lg:pl-0'">
                <header class="sticky top-0 z-20 border-b border-border/80 bg-background/92 backdrop-blur-xl">
                    <div class="flex min-h-20 items-center gap-3 px-4 sm:px-6 lg:px-8">
                        <Sheet v-model:open="mobileOpen">
                            <SheetTrigger as-child>
                                <Button variant="outline" size="icon" class="lg:hidden" aria-label="Buka navigasi">
                                    <Menu class="size-5" />
                                </Button>
                            </SheetTrigger>
                            <SheetContent side="left" class="w-[19rem] p-0">
                                <SheetTitle class="sr-only">Navigasi aplikasi</SheetTitle>
                                <AppSidebar
                                    :workspaces="workspaces"
                                    :active-workspace="activeWorkspace"
                                    :categories="categories"
                                    :invite="invite"
                                    :active-section="activeSection"
                                    :user="user"
                                    @close="mobileOpen = false"
                                    @navigate="emit('navigate', $event)"
                                    @switch-workspace="emit('switch-workspace', $event)"
                                    @open-settings="userSettingsOpen = true; mobileOpen = false;"
                                />
                            </SheetContent>
                        </Sheet>

                        <!-- Desktop Toggle -->
                        <Button 
                            variant="outline" 
                            size="icon" 
                            class="hidden lg:flex" 
                            aria-label="Toggle navigasi"
                            @click="sidebarOpen = !sidebarOpen"
                        >
                            <Menu class="size-5" />
                        </Button>

                        <div class="min-w-0 flex-1 ml-2">
                            <h1 class="truncate text-lg font-extrabold tracking-[-0.02em] sm:text-xl">{{ title }}</h1>
                            <p v-if="description" class="hidden truncate text-sm text-muted-foreground sm:block">{{ description }}</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <slot name="actions" />
                        </div>
                    </div>
                </header>

                <main class="px-4 py-5 sm:px-6 sm:py-7 lg:px-8">
                    <slot />
                </main>
            </div>
        </div>

        <UserSettingsDialog v-model:open="userSettingsOpen" :user="user" />
        <Toaster position="top-right" close-button rich-colors />
    </TooltipProvider>
</template>
