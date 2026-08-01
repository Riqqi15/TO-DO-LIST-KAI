# To Do List KAI Frontend Design

## Tujuan

Membangun ulang antarmuka To Do List KAI sebagai dashboard produktivitas profesional yang nyaman untuk penggunaan harian. Frontend memakai Laravel, Inertia, Vue 3, Tailwind CSS 4, dan shadcn-vue. Ikon memakai Lucide, sistem ikon standar shadcn.

Implementasi mempertahankan route, controller, action, model, policy, migration, dan kontrak backend yang ada. Perubahan hanya mencakup source frontend, dependency frontend, dokumentasi, dan test frontend bila diperlukan.

## Prinsip Produk

- Dashboard memakai light mode saja.
- Kanban menjadi tampilan task awal.
- Pengguna dapat berpindah antara Kanban, Daftar, dan Kalender.
- Antarmuka memakai Bahasa Indonesia dan mempertahankan label domain backend.
- UI tidak menambahkan priority, assignee, atau calendar event terpisah.
- Tindakan penting mudah ditemukan; tindakan destruktif memerlukan konfirmasi.
- Desktop, tablet, mobile, keyboard, dan reduced motion mendapat dukungan penuh.

## Pendekatan

Frontend memakai pola **Workspace command center**. Sidebar menampung konteks workspace dan navigasi, topbar menampung tindakan global, dan area utama menampilkan satu konteks kerja pada satu waktu. Form pembuatan dan pengeditan muncul dalam Sheet atau Dialog agar halaman kerja tetap ringkas.

Pendekatan ini dipilih dibanding dashboard satu halaman yang padat dan navigasi halaman semu yang berat. Satu entry Inertia tetap menjadi sumber data utama, sedangkan state frontend mengatur perpindahan tampilan tanpa membuat REST client atau store global.

## Arsitektur Halaman

### App shell

- `AppSidebar` menampilkan identitas produk, workspace personal/tim, navigasi fitur, dan akses pengaturan.
- `WorkspaceHeader` menampilkan workspace aktif, filter, pencarian lokal, tombol Buat task, dan menu akun.
- Sidebar tetap terlihat pada desktop dan berubah menjadi drawer pada mobile.
- Konten memakai lebar cair agar Kanban tetap lapang pada layar besar.

### Area kerja

- `TaskBoard` membagi task menjadi Belum Dikerjakan, Sedang Dikerjakan, dan Selesai.
- `TaskList` menyediakan tampilan padat dengan urutan deadline.
- `TaskCalendar` mengambil event dari endpoint kalender backend.
- Segmented control menyimpan pilihan tampilan selama halaman aktif. Kanban menjadi nilai awal.
- Filter status dan kategori memakai query Inertia yang sudah didukung backend. Pencarian judul/deskripsi berjalan lokal karena backend tidak menyediakan parameter pencarian.

### Panel fitur

- `TaskSheet` menangani pembuatan dan pengeditan task.
- `TaskDetailsDialog` menampilkan deskripsi, pembuat, kategori, deadline, status, dan reminder.
- `StickyNotesPanel` menangani CRUD note dan konversi note menjadi task.
- `ActivityPanel` menyajikan log sebagai timeline yang mudah dibaca.
- `WorkspaceSettings` menangani kategori, tim, undangan, kapasitas, keluar tim, dan penghapusan tim.

`resources/js/Pages/Todo/Index.vue` tetap menjadi entry Inertia tipis. Perilaku yang dipakai beberapa komponen ditempatkan dalam composable feature, sedangkan helper format tanggal dan activity tetap berupa fungsi murni.

## Sistem Visual

### Warna

| Token | Nilai | Penggunaan |
|---|---|---|
| Canvas | `#F6F8FB` | Latar aplikasi |
| Surface | `#FFFFFF` | Panel, sheet, dan dialog |
| Ink | `#172033` | Teks utama |
| Muted | `#667085` | Teks sekunder |
| Action | `#3157D5` | Tindakan utama dan fokus |
| Success | `#12806A` | Task selesai dan umpan balik sukses |
| Warning | `#B76E00` | Deadline mendekat |
| Danger | `#C23B3B` | Terlambat dan tindakan destruktif |

Border memakai warna netral tipis. Bayangan hanya membedakan lapisan dialog, sheet, popover, dan sidebar dari canvas.

### Tipografi

- Plus Jakarta Sans menangani judul, navigasi, tombol, label, dan isi.
- IBM Plex Mono menangani angka, kode undangan, serta tanggal dan waktu.
- Skala teks mempertahankan ukuran isi minimal 14 px dan line-height yang nyaman.

Font tersedia sebagai dependency lokal agar UI tidak bergantung pada request font pihak ketiga saat runtime.

### Bentuk dan signature

Komponen memakai radius sedang, ruang kosong yang cukup, dan kepadatan terukur. Task card memiliki **deadline rail** tipis yang menandai kondisi normal, mendekati deadline, terlambat, atau selesai. Rail menunjukkan urgensi berdasarkan waktu tanpa menciptakan konsep priority baru.

## Komponen shadcn-vue

UI menyusun antarmuka dari source komponen shadcn-vue di `resources/js/components/ui`. Komponen utama meliputi Button, Input, Textarea, Label, Select, Card, Badge, Avatar, Tooltip, Dropdown Menu, Tabs, Sheet, Dialog, Alert Dialog, Popover, Calendar, Separator, Scroll Area, Skeleton, Toast/Sonner, dan Sidebar.

Ikon memakai `lucide-vue-next`. Setiap tombol ikon memiliki accessible name dan tooltip ketika label tidak terlihat.

## Pemetaan Fitur Backend

### Autentikasi

Halaman login, registrasi, lupa password, reset password, dan verifikasi email memakai visual system yang sama. Form mempertahankan endpoint Fortify, CSRF, error bag, dan perilaku redirect yang ada.

### Workspace dan tim

- Pemilih workspace mengirim `GET /app?workspace=<id>` melalui Inertia.
- Pengguna dapat membuat tim dan bergabung dengan kode delapan karakter.
- Owner dapat membuat kode undangan lima menit dan menyalinnya dari flash response.
- Owner dapat memilih kapasitas 5 atau 10.
- Anggota dapat keluar sesuai policy backend.
- Owner dapat menghapus tim setelah mengetik kalimat konfirmasi persis.

Backend menyediakan route transfer ownership dan mengeluarkan anggota, tetapi props halaman hanya memuat jumlah anggota. UI tidak meminta ID internal dan tidak menebak daftar anggota. Komponen pengelolaan anggota hanya dirender jika payload anggota kelak tersedia; implementasi ini tidak mengubah backend untuk menambah payload tersebut.

### Kategori

Kategori sistem tampil sebagai read-only. Kategori custom mendukung tambah, ubah nama, dan hapus. UI menampilkan pesan backend ketika kategori masih dipakai task atau pengguna tidak memiliki izin.

### Task

- Buat dan edit: kategori, judul, deskripsi, deadline WIB, dan beberapa reminder manual.
- Ubah status: tiga nilai enum backend, termasuk alur membuka kembali task.
- Hapus: Alert Dialog menjelaskan bahwa penghapusan permanen.
- Filter: status dan kategori diteruskan ke query halaman.
- Tampilan: Kanban, Daftar, dan Kalender memakai data workspace yang sama.

UI menghitung apakah H-7 dan H-3 telah lewat. Jika keduanya lewat, form menjelaskan kebutuhan reminder manual sebelum submit. Backend tetap menjadi sumber validasi akhir.

### Reminder

Task detail membedakan reminder otomatis dan manual serta menampilkan statusnya. Pengguna dapat menambah beberapa reminder manual dan menghapus reminder manual. Reminder otomatis hanya tampil sebagai informasi karena backend melarang penghapusan manual terhadap jenis tersebut.

### Sticky note

Pengguna dapat membuat, mengedit, mewarnai, dan menghapus note. Dialog konversi meminta judul, kategori, deadline, dan reminder yang diperlukan. Note tetap tampil setelah konversi dan menunjukkan status konversinya.

### Activity log

Timeline menampilkan aksi, pelaku, waktu, dan perubahan utama. Formatter mengubah snapshot JSON menjadi pasangan label-nilai yang dapat dibaca. Activity log tidak memiliki tindakan edit atau hapus.

## Alur Data

Props dari `TodoPageController@index` menjadi sumber workspace, kategori, task, sticky note, activity, timezone, auth, error, dan flash. Mutasi memakai `useForm` Inertia menuju route yang ada. Redirect backend menyegarkan props dan mempertahankan aturan otorisasi di server.

Kalender memakai `GET /workspaces/{workspace}/calendar`. Komponen membatalkan request lama saat rentang berubah agar respons lama tidak menimpa tampilan terbaru.

State lokal mencakup tampilan aktif, pencarian, dialog yang terbuka, dan draft form. State domain tetap berasal dari props Inertia; frontend tidak membuat store global atau REST layer.

## Error dan Feedback

- Error validasi tampil di bawah field terkait.
- Error domain umum tampil sebagai toast dengan instruksi yang dapat dilakukan pengguna.
- Tombol submit menampilkan progress dan nonaktif selama request.
- Flash sukses memakai kata kerja yang sama dengan tindakan, misalnya `Task dibuat`.
- Empty state menjelaskan kondisi dan menawarkan tindakan berikutnya.
- Alert Dialog melindungi penghapusan task, note, kategori, dan tim.
- Kegagalan kalender menampilkan tombol Coba lagi tanpa menghilangkan mode lain.

## Responsivitas dan Aksesibilitas

- Target sentuh minimal 40 px.
- Focus ring selalu terlihat pada navigasi keyboard.
- Dialog dan Sheet mengelola fokus, Escape, serta scroll lock.
- Form memakai label programatis, deskripsi, dan `aria-invalid`.
- Kontras warna memenuhi WCAG AA untuk teks dan kontrol utama.
- Kanban dapat digeser horizontal pada mobile tanpa memotong kartu.
- Animasi singkat menghormati `prefers-reduced-motion`.
- Informasi status tidak hanya bergantung pada warna; label dan ikon selalu menyertai warna.

## Verifikasi

1. Jalankan build produksi Vite.
2. Jalankan seluruh test Laravel setelah manifest tersedia.
3. Uji halaman auth dan dashboard pada viewport desktop dan mobile.
4. Uji buat/edit/hapus task, status, filter, kalender, reminder, kategori, sticky note, dan pengaturan tim.
5. Uji empty, loading, validation, success, error, dan destructive states.
6. Uji keyboard, focus, accessible name, tooltip, kontras, dan reduced motion.
7. Ambil screenshot browser untuk meninjau hierarki, kepadatan, overflow, Sheet, dan Dialog.
8. Periksa `git diff` untuk memastikan file backend tidak berubah.

Baseline sebelum implementasi mencatat 37 test lulus dan 4 test gagal karena `public/build/manifest.json` belum tersedia pada host. Build frontend harus membuat manifest sebelum test akhir dijalankan.

## Batas Scope

Implementasi tidak mengubah backend, menambah endpoint, menambah payload anggota, menambah priority, menambah assignee, atau membuat event kalender tersendiri. Transfer ownership dan pengeluaran anggota menunggu payload anggota yang aman untuk UI. Semua kemampuan lain yang didukung kontrak backend masuk dalam implementasi frontend.
