<script setup>
import FieldError from '@/components/shared/FieldError.vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { router, useForm } from '@inertiajs/vue3';
import {
    Check,
    ChevronDown,
    Clipboard,
    Folder,
    KeyRound,
    LoaderCircle,
    MoreHorizontal,
    Pencil,
    Plus,
    ShieldCheck,
    Trash2,
    UserPlus,
    UserRound,
    Users,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps({
    workspaces: { type: Array, default: () => [] },
    activeWorkspace: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
    user: { type: Object, default: null },
    invite: { type: Object, default: null },
});

const emit = defineEmits(['switch-workspace']);

const personalWorkspaces = computed(() => props.workspaces.filter((workspace) => workspace.type === 'personal'));
const teamWorkspaces = computed(() => props.workspaces.filter((workspace) => workspace.type === 'team'));
const systemCategories = computed(() => props.categories.filter((category) => category.is_system));
const customCategories = computed(() => props.categories.filter((category) => !category.is_system));

const createTeamOpen = ref(false);
const joinTeamOpen = ref(false);
const createCategoryOpen = ref(false);
const editCategoryOpen = ref(false);
const manageTeamOpen = ref(false);
const categoryDeleteOpen = ref(false);
const leaveTeamOpen = ref(false);
const deleteTeamOpen = ref(false);
const selectedTeam = ref(null);
const selectedCategory = ref(null);

const teamForm = useForm({ name: '' });
const joinForm = useForm({ code: '' });
const categoryForm = useForm({ name: '' });
const editCategoryForm = useForm({ name: '' });
const capacityForm = useForm({ member_limit: 5 });
const deleteTeamForm = useForm({ confirmation: '' });

const isActive = (workspace) => Number(workspace?.id) === Number(props.activeWorkspace?.id);
const isOwner = (workspace) => Number(workspace?.created_by ?? workspace?.owner_id) === Number(props.user?.id);

const switchWorkspace = (workspace) => {
    if (!workspace || isActive(workspace)) return;
    emit('switch-workspace', workspace.id);
};

const openCreateTeam = () => {
    teamForm.clearErrors();
    createTeamOpen.value = true;
};

const openJoinTeam = () => {
    joinForm.clearErrors();
    joinTeamOpen.value = true;
};

const openCreateCategory = () => {
    categoryForm.clearErrors();
    createCategoryOpen.value = true;
};

const openEditCategory = (category) => {
    selectedCategory.value = category;
    editCategoryForm.name = category.name;
    editCategoryForm.clearErrors();
    editCategoryOpen.value = true;
};

const openManageTeam = (workspace) => {
    selectedTeam.value = workspace;
    capacityForm.member_limit = workspace.member_limit ?? 5;
    capacityForm.clearErrors();
    deleteTeamForm.reset();
    deleteTeamForm.clearErrors();
    manageTeamOpen.value = true;
};

const askDeleteCategory = (category) => {
    selectedCategory.value = category;
    categoryDeleteOpen.value = true;
};

const askLeaveTeam = (workspace) => {
    selectedTeam.value = workspace;
    leaveTeamOpen.value = true;
};

const createTeam = () => teamForm.post('/teams', {
    preserveScroll: true,
    onSuccess: () => {
        teamForm.reset();
        createTeamOpen.value = false;
    },
});

const joinTeam = () => joinForm.post('/teams/join', {
    preserveScroll: true,
    onSuccess: () => {
        joinForm.reset();
        joinTeamOpen.value = false;
    },
});

const createCategory = () => {
    if (!props.activeWorkspace) return;
    categoryForm.post(`/workspaces/${props.activeWorkspace.id}/categories`, {
        preserveScroll: true,
        onSuccess: () => {
            categoryForm.reset();
            createCategoryOpen.value = false;
        },
    });
};

const updateCategory = () => {
    if (!selectedCategory.value) return;
    editCategoryForm.patch(`/categories/${selectedCategory.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editCategoryOpen.value = false;
            selectedCategory.value = null;
        },
    });
};

const deleteCategory = () => {
    if (!selectedCategory.value) return;
    router.delete(`/categories/${selectedCategory.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            categoryDeleteOpen.value = false;
            selectedCategory.value = null;
        },
    });
};

const generateInvite = () => {
    if (!selectedTeam.value) return;
    router.post(`/workspaces/${selectedTeam.value.id}/invite`, {}, { preserveScroll: true });
};

const copyInvite = async () => {
    if (!props.invite?.code) return;
    try {
        await navigator.clipboard.writeText(props.invite.code);
        toast.success('Kode tim disalin.');
    } catch {
        toast.error('Kode tidak dapat disalin. Salin kode secara manual.');
    }
};

const updateCapacity = () => {
    if (!selectedTeam.value) return;
    capacityForm.patch(`/workspaces/${selectedTeam.value.id}/capacity`, { preserveScroll: true });
};

const leaveTeam = () => {
    if (!selectedTeam.value) return;
    router.delete(`/workspaces/${selectedTeam.value.id}/leave`, {
        onSuccess: () => {
            leaveTeamOpen.value = false;
            selectedTeam.value = null;
        },
    });
};

const openDeleteTeam = () => {
    deleteTeamForm.reset();
    deleteTeamForm.clearErrors();
    manageTeamOpen.value = false;
    deleteTeamOpen.value = true;
};

const deleteTeam = () => {
    if (!selectedTeam.value) return;
    deleteTeamForm.delete(`/workspaces/${selectedTeam.value.id}`, {
        onSuccess: () => {
            deleteTeamOpen.value = false;
            selectedTeam.value = null;
        },
    });
};
</script>

<template>
    <div class="space-y-4">
        <section aria-labelledby="workspace-active-label">
            <p id="workspace-active-label" class="mb-2 px-1 text-[10px] font-bold uppercase tracking-[0.15em] text-muted-foreground">
                Workspace aktif
            </p>

            <div class="space-y-1">
                <Button
                    v-for="workspace in personalWorkspaces"
                    :key="workspace.id"
                    variant="ghost"
                    class="relative h-auto min-h-12 w-full justify-start gap-2.5 overflow-hidden px-3 py-2 text-left"
                    :class="isActive(workspace) ? 'bg-primary/10 text-primary shadow-[inset_3px_0_0_var(--primary)] hover:bg-primary/12' : 'text-sidebar-foreground hover:bg-sidebar-accent'"
                    :aria-current="isActive(workspace) ? 'page' : undefined"
                    @click="switchWorkspace(workspace)"
                >
                    <UserRound class="size-4 shrink-0" />
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-xs font-bold">{{ workspace.name }}</span>
                        <span class="mt-0.5 block truncate text-[10px] font-medium text-muted-foreground">Ruang pribadi</span>
                    </span>
                    <Check v-if="isActive(workspace)" class="size-3.5 shrink-0" aria-hidden="true" />
                </Button>
            </div>

            <div class="mt-3 rounded-xl border border-sidebar-border/80 bg-white/55 p-2">
                <div class="flex items-center justify-between gap-2 px-1 pb-2">
                    <div class="flex min-w-0 items-center gap-2">
                        <Users class="size-3.5 shrink-0 text-primary" />
                        <span class="truncate text-[11px] font-extrabold">Workspace Tim</span>
                    </div>
                    <Badge variant="secondary" class="h-5 px-1.5 font-mono text-[9px]">{{ teamWorkspaces.length }}</Badge>
                </div>

                <div class="mb-2 grid grid-cols-2 gap-1.5">
                    <Button variant="outline" size="xs" class="h-7 bg-white px-2 text-[10px] font-bold" @click="openCreateTeam">
                        <Plus class="size-3" />Buat tim
                    </Button>
                    <Button variant="outline" size="xs" class="h-7 bg-white px-2 text-[10px] font-bold" @click="openJoinTeam">
                        <UserPlus class="size-3" />Gabung
                    </Button>
                </div>

                <div v-if="teamWorkspaces.length" class="space-y-1">
                    <div v-for="workspace in teamWorkspaces" :key="workspace.id" class="flex min-w-0 items-center gap-0.5">
                        <Button
                            variant="ghost"
                            class="relative h-auto min-h-10 min-w-0 flex-1 justify-start gap-2 overflow-hidden px-2 py-1.5 text-left"
                            :class="isActive(workspace) ? 'bg-primary/10 text-primary shadow-[inset_3px_0_0_var(--primary)] hover:bg-primary/12' : 'text-sidebar-foreground hover:bg-sidebar-accent'"
                            :aria-current="isActive(workspace) ? 'page' : undefined"
                            @click="switchWorkspace(workspace)"
                        >
                            <ShieldCheck class="size-3.5 shrink-0" />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[11px] font-bold">{{ workspace.name }}</span>
                                <span class="block truncate text-[9px] font-medium text-muted-foreground">
                                    {{ workspace.membership_rows_count ?? 1 }} anggota<span v-if="isActive(workspace)"> · Aktif</span>
                                </span>
                            </span>
                        </Button>

                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button
                                    variant="ghost"
                                    size="icon-xs"
                                    :aria-label="isOwner(workspace) ? `Kelola ${workspace.name}` : `Keluar dari ${workspace.name}`"
                                    @click.stop="isOwner(workspace) ? openManageTeam(workspace) : askLeaveTeam(workspace)"
                                >
                                    <MoreHorizontal class="size-3.5" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent side="right">{{ isOwner(workspace) ? 'Kelola tim' : 'Keluar dari tim' }}</TooltipContent>
                        </Tooltip>
                    </div>
                </div>
                <p v-else class="px-2 py-2 text-center text-[10px] leading-4 text-muted-foreground">Belum ada workspace tim.</p>
            </div>
        </section>

        <Collapsible class="rounded-xl border border-sidebar-border/80 bg-white/55 p-2">
            <div class="flex items-center gap-1">
                <CollapsibleTrigger as-child>
                    <Button variant="ghost" class="group h-8 min-w-0 flex-1 justify-start gap-2 px-1.5 text-[11px] font-extrabold">
                        <Folder class="size-3.5 text-primary" />
                        <span class="min-w-0 flex-1 truncate text-left">Kategori</span>
                        <Badge variant="secondary" class="h-5 px-1.5 font-mono text-[9px]">{{ categories.length }}</Badge>
                        <ChevronDown class="size-3.5 transition-transform group-data-[state=open]:rotate-180" />
                    </Button>
                </CollapsibleTrigger>
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button variant="ghost" size="icon-xs" :disabled="!activeWorkspace" aria-label="Tambah kategori" @click="openCreateCategory">
                            <Plus class="size-3.5" />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent side="right">Tambah kategori</TooltipContent>
                </Tooltip>
            </div>

            <CollapsibleContent>
                <p v-if="activeWorkspace" class="truncate px-2 pb-1 pt-2 text-[9px] text-muted-foreground">Untuk {{ activeWorkspace.name }}</p>
                <div class="space-y-0.5 pt-1">
                    <div v-for="category in systemCategories" :key="category.id" class="flex min-h-8 items-center gap-2 rounded-lg px-2 text-[10px] font-semibold text-muted-foreground">
                        <span class="size-1.5 rounded-full bg-slate-400" />
                        <span class="min-w-0 flex-1 truncate">{{ category.name }}</span>
                        <span class="text-[8px] font-bold uppercase tracking-wide">Sistem</span>
                    </div>

                    <div v-for="category in customCategories" :key="category.id" class="flex min-w-0 items-center gap-0.5 rounded-lg hover:bg-sidebar-accent">
                        <div class="flex min-h-8 min-w-0 flex-1 items-center gap-2 px-2 text-[10px] font-semibold">
                            <span class="size-1.5 rounded-full bg-primary" />
                            <span class="truncate">{{ category.name }}</span>
                        </div>
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button variant="ghost" size="icon-xs" :aria-label="`Ubah kategori ${category.name}`" @click="openEditCategory(category)">
                                    <Pencil class="size-3" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent side="right">Ubah nama</TooltipContent>
                        </Tooltip>
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button variant="ghost" size="icon-xs" class="text-destructive" :aria-label="`Hapus kategori ${category.name}`" @click="askDeleteCategory(category)">
                                    <Trash2 class="size-3" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent side="right">Hapus kategori</TooltipContent>
                        </Tooltip>
                    </div>

                    <p v-if="categories.length === 0" class="px-2 py-3 text-center text-[10px] leading-4 text-muted-foreground">Belum ada kategori.</p>
                </div>
            </CollapsibleContent>
        </Collapsible>

        <Dialog v-model:open="createTeamOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Buat workspace tim</DialogTitle>
                    <DialogDescription>Buat ruang kerja baru untuk mengelola tugas bersama.</DialogDescription>
                </DialogHeader>
                <form id="create-team-form" class="space-y-2" @submit.prevent="createTeam">
                    <Label for="sidebar-team-name">Nama tim</Label>
                    <Input id="sidebar-team-name" v-model="teamForm.name" maxlength="100" placeholder="Contoh: Tim Operasional" required :aria-invalid="Boolean(teamForm.errors.name)" />
                    <FieldError :message="teamForm.errors.name" />
                </form>
                <DialogFooter>
                    <Button variant="outline" @click="createTeamOpen = false">Batal</Button>
                    <Button form="create-team-form" type="submit" :disabled="teamForm.processing">
                        <LoaderCircle v-if="teamForm.processing" class="size-4 animate-spin" />Buat tim
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="joinTeamOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Gabung workspace tim</DialogTitle>
                    <DialogDescription>Masukkan kode delapan karakter dari owner tim.</DialogDescription>
                </DialogHeader>
                <form id="join-team-form" class="space-y-2" @submit.prevent="joinTeam">
                    <Label for="sidebar-team-code">Kode tim</Label>
                    <Input id="sidebar-team-code" :model-value="joinForm.code" class="font-mono uppercase tracking-[0.18em]" maxlength="8" placeholder="XXXXXXXX" required :aria-invalid="Boolean(joinForm.errors.code)" @input="joinForm.code = $event.target.value.toUpperCase()" />
                    <FieldError :message="joinForm.errors.code" />
                </form>
                <DialogFooter>
                    <Button variant="outline" @click="joinTeamOpen = false">Batal</Button>
                    <Button form="join-team-form" type="submit" :disabled="joinForm.processing">
                        <LoaderCircle v-if="joinForm.processing" class="size-4 animate-spin" />Gabung tim
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="createCategoryOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Tambah kategori</DialogTitle>
                    <DialogDescription>Kategori baru tersedia di {{ activeWorkspace?.name }}.</DialogDescription>
                </DialogHeader>
                <form id="create-category-form" class="space-y-2" @submit.prevent="createCategory">
                    <Label for="sidebar-category-name">Nama kategori</Label>
                    <Input id="sidebar-category-name" v-model="categoryForm.name" maxlength="80" placeholder="Contoh: Laporan mingguan" required :aria-invalid="Boolean(categoryForm.errors.name)" />
                    <FieldError :message="categoryForm.errors.name" />
                </form>
                <DialogFooter>
                    <Button variant="outline" @click="createCategoryOpen = false">Batal</Button>
                    <Button form="create-category-form" type="submit" :disabled="categoryForm.processing">
                        <LoaderCircle v-if="categoryForm.processing" class="size-4 animate-spin" />Tambah kategori
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="editCategoryOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Ubah nama kategori</DialogTitle>
                    <DialogDescription>Perubahan berlaku pada seluruh tugas yang memakai kategori ini.</DialogDescription>
                </DialogHeader>
                <form id="edit-category-form" class="space-y-2" @submit.prevent="updateCategory">
                    <Label for="sidebar-category-edit-name">Nama kategori</Label>
                    <Input id="sidebar-category-edit-name" v-model="editCategoryForm.name" maxlength="80" required :aria-invalid="Boolean(editCategoryForm.errors.name)" />
                    <FieldError :message="editCategoryForm.errors.name" />
                </form>
                <DialogFooter>
                    <Button variant="outline" @click="editCategoryOpen = false">Batal</Button>
                    <Button form="edit-category-form" type="submit" :disabled="editCategoryForm.processing">
                        <LoaderCircle v-if="editCategoryForm.processing" class="size-4 animate-spin" />Simpan perubahan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="manageTeamOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Kelola {{ selectedTeam?.name }}</DialogTitle>
                    <DialogDescription>Atur akses dan kapasitas workspace tim.</DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <section class="rounded-xl border p-4">
                        <div class="flex items-start gap-3">
                            <div class="grid size-9 place-items-center rounded-lg bg-secondary text-primary"><KeyRound class="size-4" /></div>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-sm font-extrabold">Kode undangan</h3>
                                <p class="mt-1 text-xs leading-5 text-muted-foreground">Kode berlaku selama lima menit.</p>
                            </div>
                        </div>
                        <div v-if="invite?.code" class="mt-3 flex items-center gap-2 rounded-lg bg-secondary p-2.5">
                            <code class="min-w-0 flex-1 text-center font-mono text-base font-bold tracking-[0.18em]">{{ invite.code }}</code>
                            <Button variant="outline" size="icon-sm" aria-label="Salin kode tim" @click="copyInvite"><Clipboard class="size-4" /></Button>
                        </div>
                        <Button class="mt-3 w-full" variant="outline" @click="generateInvite">Buat kode baru</Button>
                    </section>

                    <section class="rounded-xl border p-4">
                        <div class="flex items-start gap-3">
                            <div class="grid size-9 place-items-center rounded-lg bg-secondary text-primary"><Users class="size-4" /></div>
                            <div>
                                <h3 class="text-sm font-extrabold">Kapasitas anggota</h3>
                                <p class="mt-1 text-xs leading-5 text-muted-foreground">Owner termasuk dalam kapasitas tim.</p>
                            </div>
                        </div>
                        <form class="mt-3 flex gap-2" @submit.prevent="updateCapacity">
                            <NativeSelect v-model="capacityForm.member_limit" class="h-9 flex-1" aria-label="Kapasitas anggota">
                                <NativeSelectOption :value="5">5 anggota</NativeSelectOption>
                                <NativeSelectOption :value="10">10 anggota</NativeSelectOption>
                            </NativeSelect>
                            <Button type="submit" :disabled="capacityForm.processing">
                                <LoaderCircle v-if="capacityForm.processing" class="size-4 animate-spin" />Simpan
                            </Button>
                        </form>
                        <FieldError :message="capacityForm.errors.member_limit" />
                    </section>

                    <section class="rounded-xl border border-destructive/25 bg-destructive/5 p-4">
                        <h3 class="text-sm font-extrabold text-destructive">Hapus workspace tim</h3>
                        <p class="mt-1 text-xs leading-5 text-muted-foreground">Seluruh data operasional tim akan dihapus permanen.</p>
                        <Button class="mt-3" variant="destructive" @click="openDeleteTeam"><Trash2 class="size-4" />Hapus tim</Button>
                    </section>
                </div>
            </DialogContent>
        </Dialog>

        <AlertDialog v-model:open="categoryDeleteOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus kategori {{ selectedCategory?.name }}?</AlertDialogTitle>
                    <AlertDialogDescription>Kategori yang masih dipakai tugas tidak dapat dihapus. Sistem akan memeriksanya sebelum melanjutkan.</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Batal</AlertDialogCancel>
                    <AlertDialogAction class="bg-destructive text-white hover:bg-destructive/90" @click="deleteCategory">Hapus kategori</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <AlertDialog v-model:open="leaveTeamOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Keluar dari {{ selectedTeam?.name }}?</AlertDialogTitle>
                    <AlertDialogDescription>Anda akan kehilangan akses ke tugas dan catatan tim. Data yang pernah Anda buat tetap berada di workspace.</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Batal</AlertDialogCancel>
                    <AlertDialogAction @click="leaveTeam">Keluar dari tim</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <AlertDialog v-model:open="deleteTeamOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus tim secara permanen?</AlertDialogTitle>
                    <AlertDialogDescription>Ketik persis <strong>konfirmasi hapus tim {{ selectedTeam?.name }}</strong> untuk melanjutkan.</AlertDialogDescription>
                </AlertDialogHeader>
                <div class="space-y-2">
                    <Label for="sidebar-delete-team-confirmation">Konfirmasi</Label>
                    <Input id="sidebar-delete-team-confirmation" v-model="deleteTeamForm.confirmation" :placeholder="`konfirmasi hapus tim ${selectedTeam?.name ?? ''}`" :aria-invalid="Boolean(deleteTeamForm.errors.confirmation)" />
                    <FieldError :message="deleteTeamForm.errors.confirmation" />
                </div>
                <AlertDialogFooter>
                    <AlertDialogCancel>Batal</AlertDialogCancel>
                    <Button variant="destructive" :disabled="deleteTeamForm.processing" @click="deleteTeam">
                        <LoaderCircle v-if="deleteTeamForm.processing" class="size-4 animate-spin" />Hapus permanen
                    </Button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
