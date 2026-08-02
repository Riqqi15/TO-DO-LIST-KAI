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
    LogOut,
    MoreHorizontal,
    Pencil,
    Plus,
    Settings,
    ShieldCheck,
    Trash2,
    UserPlus,
    UserRound,
    Users,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps({
    workspaces: { type: Array, default: () => [] },
    activeWorkspace: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
    user: { type: Object, default: null },
    invite: { type: Object, default: null },
});

const emit = defineEmits(['switch-workspace']);

const activeInviteCode = ref('');

const saveStoredInvite = (teamId, code, expiresAt) => {
    if (!teamId || !code) return;
    try {
        localStorage.setItem(`kai_invite_${teamId}`, JSON.stringify({
            code,
            expires_at: expiresAt || new Date(Date.now() + 5 * 60 * 1000).toISOString(),
        }));
    } catch {}
};

const loadStoredInvite = (teamId) => {
    if (!teamId) return '';
    try {
        const raw = localStorage.getItem(`kai_invite_${teamId}`);
        if (!raw) return '';
        const data = JSON.parse(raw);
        if (data?.code && data?.expires_at && new Date(data.expires_at) > new Date()) {
            return data.code;
        } else {
            localStorage.removeItem(`kai_invite_${teamId}`);
        }
    } catch {}
    return '';
};

watch(() => props.invite, (newInvite) => {
    if (newInvite?.code && selectedTeam.value) {
        activeInviteCode.value = newInvite.code;
        saveStoredInvite(selectedTeam.value.id, newInvite.code, newInvite.expires_at);
    }
}, { immediate: true, deep: true });

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
const isOwner = (workspace) => {
    if (!workspace || !props.user) return true;
    if (workspace.created_by != null) return Number(workspace.created_by) === Number(props.user.id);
    if (workspace.owner_id != null) return Number(workspace.owner_id) === Number(props.user.id);
    return true;
};

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

    if (props.invite?.code) {
        activeInviteCode.value = props.invite.code;
        saveStoredInvite(workspace.id, props.invite.code, props.invite.expires_at);
    } else {
        activeInviteCode.value = loadStoredInvite(workspace.id);
    }

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
    const codeToCopy = activeInviteCode.value || props.invite?.code;
    if (!codeToCopy) return;
    try {
        await navigator.clipboard.writeText(codeToCopy);
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
        <!-- Ruang Pribadi -->
        <section aria-labelledby="workspace-active-label">
            <p id="workspace-active-label" class="mb-1.5 px-2 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                Ruang Pribadi
            </p>

            <div class="space-y-0.5">
                <Button
                    v-for="workspace in personalWorkspaces"
                    :key="workspace.id"
                    variant="ghost"
                    class="h-9 w-full justify-start gap-2.5 px-2.5 text-xs transition-colors"
                    :class="isActive(workspace) 
                        ? 'bg-primary/10 text-primary font-bold hover:bg-primary/15' 
                        : 'text-muted-foreground hover:bg-sidebar-accent hover:text-foreground'"
                    :aria-current="isActive(workspace) ? 'page' : undefined"
                    @click="switchWorkspace(workspace)"
                >
                    <UserRound class="size-4 shrink-0" />
                    <span class="truncate min-w-0 flex-1 text-left">{{ workspace.name }}</span>
                    <Check v-if="isActive(workspace)" class="size-3.5 shrink-0" aria-hidden="true" />
                </Button>
            </div>
        </section>

        <!-- Workspace Tim -->
        <section aria-labelledby="workspace-team-label">
            <div class="mb-1 flex items-center justify-between px-2">
                <span id="workspace-team-label" class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                    Workspace Tim
                </span>
                <div class="flex items-center gap-0.5">
                    <Button variant="ghost" size="icon-xs" class="text-muted-foreground hover:text-foreground" title="Buat tim baru" aria-label="Buat tim" @click="openCreateTeam">
                        <Plus class="size-3.5" />
                    </Button>

                    <Button variant="ghost" size="icon-xs" class="text-muted-foreground hover:text-foreground" title="Gabung tim dengan kode" aria-label="Gabung tim" @click="openJoinTeam">
                        <UserPlus class="size-3.5" />
                    </Button>
                </div>
            </div>

            <div v-if="teamWorkspaces.length" class="space-y-0.5">
                <div v-for="workspace in teamWorkspaces" :key="workspace.id" class="group flex min-w-0 items-center">
                    <Button
                        variant="ghost"
                        class="h-9 min-w-0 flex-1 justify-start gap-2.5 px-2.5 text-xs transition-colors"
                        :class="isActive(workspace) 
                            ? 'bg-primary/10 text-primary font-bold hover:bg-primary/15' 
                            : 'text-muted-foreground hover:bg-sidebar-accent hover:text-foreground'"
                        :aria-current="isActive(workspace) ? 'page' : undefined"
                        @click="switchWorkspace(workspace)"
                    >
                        <Users class="size-4 shrink-0" />
                        <span class="min-w-0 flex-1 truncate text-left">{{ workspace.name }}</span>
                        <span class="text-[10px] font-normal opacity-60">{{ workspace.membership_rows_count ?? 1 }}</span>
                    </Button>

                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-xs"
                        class="shrink-0 text-muted-foreground hover:text-foreground"
                        :title="isOwner(workspace) ? 'Kelola tim' : 'Keluar dari tim'"
                        :aria-label="isOwner(workspace) ? `Kelola ${workspace.name}` : `Keluar dari ${workspace.name}`"
                        @click.stop.prevent="isOwner(workspace) ? openManageTeam(workspace) : askLeaveTeam(workspace)"
                    >
                        <Settings v-if="isOwner(workspace)" class="size-3.5" />
                        <LogOut v-else class="size-3.5 text-destructive" />
                    </Button>
                </div>
            </div>
            <p v-else class="px-2 py-1 text-xs text-muted-foreground/60 italic">Belum ada tim.</p>
        </section>

        <!-- Kategori -->
        <Collapsible class="space-y-1">
            <div class="flex items-center justify-between px-2">
                <CollapsibleTrigger as-child>
                    <button type="button" class="group flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground hover:text-foreground">
                        <ChevronDown class="size-3 transition-transform group-data-[state=open]:rotate-180" />
                        <span>Kategori ({{ categories.length }})</span>
                    </button>
                </CollapsibleTrigger>
                <Button variant="ghost" size="icon-xs" class="text-muted-foreground hover:text-foreground" :disabled="!activeWorkspace" title="Tambah kategori" aria-label="Tambah kategori" @click="openCreateCategory">
                    <Plus class="size-3.5" />
                </Button>
            </div>

            <CollapsibleContent class="space-y-0.5 pt-0.5">
                <div v-for="category in systemCategories" :key="category.id" class="flex h-8 items-center gap-2 rounded-md px-2.5 text-xs font-medium text-muted-foreground">
                    <span class="size-1.5 rounded-full bg-slate-300" />
                    <span class="min-w-0 flex-1 truncate">{{ category.name }}</span>
                    <span class="text-[9px] font-normal text-muted-foreground/60">Sistem</span>
                </div>

                <div v-for="category in customCategories" :key="category.id" class="group flex h-8 items-center gap-1 rounded-md px-2.5 text-xs font-medium text-muted-foreground hover:bg-sidebar-accent hover:text-foreground">
                    <span class="size-1.5 rounded-full bg-primary/70" />
                    <span class="min-w-0 flex-1 truncate">{{ category.name }}</span>
                    <div class="flex items-center opacity-0 transition-opacity group-hover:opacity-100">
                        <Button variant="ghost" size="icon-xs" class="h-6 w-6" :aria-label="`Ubah kategori ${category.name}`" @click="openEditCategory(category)">
                            <Pencil class="size-3" />
                        </Button>
                        <Button variant="ghost" size="icon-xs" class="h-6 w-6 text-destructive" :aria-label="`Hapus kategori ${category.name}`" @click="askDeleteCategory(category)">
                            <Trash2 class="size-3" />
                        </Button>
                    </div>
                </div>

                <p v-if="categories.length === 0" class="px-2 py-1.5 text-xs text-muted-foreground/60 italic">Belum ada kategori.</p>
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
                        <div v-if="activeInviteCode" class="mt-3 flex items-center gap-2 rounded-lg bg-secondary p-2.5">
                            <code class="min-w-0 flex-1 text-center font-mono text-base font-bold tracking-[0.18em]">{{ activeInviteCode }}</code>
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
