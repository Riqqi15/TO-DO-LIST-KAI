# Sidebar Workspace Navigation Implementation Plan

**Goal:** Memindahkan kategori dan workspace tim ke sidebar, menghapus halaman Pengaturan, memisahkan Kalender dari mode Tugas, dan mengganti Kanban menjadi Board tanpa mengubah backend.

**Architecture:** `TodoWorkspace` tetap membaca props Inertia dan mengatur area utama. `AppLayout` meneruskan workspace, kategori, user, dan flash undangan ke dua instance `AppSidebar`. Komponen baru `SidebarWorkspaceTools` menangani daftar workspace serta seluruh dialog kategori dan tim dengan route yang sudah ada.

**Stack:** Laravel 12, Inertia 3, Vue 3, Tailwind CSS 4, shadcn-vue, Lucide.

---

## Task 1: Tambahkan komponen shadcn untuk bagian yang dapat dibuka

**Files:**

- Create: `resources/js/components/ui/collapsible/*`

**Steps:**

1. Tambahkan source Collapsible shadcn-vue melalui registry CLI agar bagian kategori mengikuti pola komponen resmi.
2. Pastikan alias dan ekspor komponen sesuai struktur komponen UI yang sudah ada.
3. Jalankan build cepat untuk memeriksa import.

## Task 2: Bangun alat workspace dan kategori di sidebar

**Files:**

- Create: `resources/js/features/todo/components/SidebarWorkspaceTools.vue`
- Modify: `resources/js/components/shared/AppSidebar.vue`

**Steps:**

1. Pisahkan workspace personal dan tim dari prop `workspaces`.
2. Render Ruang Pribadi lebih dahulu dan workspace tim di bawahnya sebagai tombol langsung.
3. Tambahkan status aktif, jumlah anggota, dan pemotongan nama panjang.
4. Tambahkan tombol Buat dan Gabung yang membuka Dialog shadcn.
5. Tambahkan Dropdown Menu pada tiap tim untuk undangan, kapasitas, keluar, dan hapus sesuai peran.
6. Pindahkan CRUD kategori ke Collapsible Kategori dengan Dialog tambah/ubah dan Alert Dialog hapus.
7. Pertahankan `useForm`, endpoint, error backend, progress, konfirmasi, flash kode undangan, dan clipboard.
8. Hapus dropdown workspace serta menu Pengaturan dari `AppSidebar`.

## Task 3: Teruskan data sidebar melalui layout

**Files:**

- Modify: `resources/js/layouts/AppLayout.vue`
- Modify: `resources/js/features/todo/components/TodoWorkspace.vue`

**Steps:**

1. Tambahkan prop `categories` dan `invite` pada `AppLayout`.
2. Teruskan kedua prop ke sidebar desktop dan mobile.
3. Teruskan kategori dan `flash.team_invite` dari `TodoWorkspace`.
4. Pastikan perpindahan workspace tetap memakai event `switch-workspace` dan route `/app?workspace=<id>`.

## Task 4: Sederhanakan navigasi area utama

**Files:**

- Modify: `resources/js/features/todo/components/TodoWorkspace.vue`
- Delete: `resources/js/features/todo/components/WorkspaceSettings.vue`

**Steps:**

1. Hapus import, header, render branch, dan state navigasi Pengaturan.
2. Ubah nilai awal tampilan dari `kanban` menjadi `board`.
3. Ubah label dan accessible name Kanban menjadi Board.
4. Ubah pengalih mode menjadi dua kolom: Board dan Daftar.
5. Render Kalender hanya ketika `activeSection === 'calendar'`.
6. Render statistik, filter, dan pengalih Board/Daftar hanya pada halaman Tugas.
7. Hapus `WorkspaceSettings.vue` setelah seluruh tindakannya tersedia di sidebar.

## Task 5: Verifikasi statis dan otomatis

**Files:**

- Verify only.

**Steps:**

1. Cari semua referensi `kanban`, `settings`, dan `WorkspaceSettings` pada frontend.
2. Jalankan `npm run build`.
3. Jalankan `php artisan test`.
4. Jalankan `git diff --check`.
5. Pastikan diff tidak memuat file backend.

## Task 6: QA browser desktop dan mobile

**Files:**

- Verify only.

**Steps:**

1. Buka aplikasi lokal dan masuk dengan akun QA yang tersedia.
2. Periksa daftar Ruang Pribadi dan workspace tim, termasuk status aktif.
3. Uji dialog kategori, buat tim, gabung tim, dan menu pengelolaan tim tanpa melakukan tindakan destruktif permanen.
4. Pastikan halaman Tugas hanya menampilkan Board dan Daftar.
5. Pastikan Kalender hanya dapat dibuka melalui sidebar dan tampil sebagai halaman tersendiri.
6. Uji drawer mobile, fokus dialog, overflow horizontal, dan label kontrol.
7. Periksa console browser.

## Task 7: Final review dan commit

**Files:**

- Review all changed frontend files.

**Steps:**

1. Tinjau perubahan terhadap spesifikasi yang disetujui.
2. Pastikan worktree hanya memuat perubahan dalam scope.
3. Commit implementasi frontend dengan pesan yang menjelaskan perubahan sidebar.
