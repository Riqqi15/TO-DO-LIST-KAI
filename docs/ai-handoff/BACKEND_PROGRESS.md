# Backend Progress and AI Handoff

Dokumen ini adalah titik masuk utama untuk melanjutkan backend To Do List KAI
tanpa bergantung pada riwayat percakapan. Selalu verifikasi kondisi aktual
repository karena status Git, Docker, database, dan dependency dapat berubah.

**Snapshot pemeriksaan:** 31 Juli 2026, Asia/Jakarta.

## 1. Tujuan Produk

Aplikasi internal To Do List untuk pekerjaan personal dan kolaborasi tim.
Target awal sekitar 20-30 pengguna. Fitur utama yang sudah disepakati:

- Workspace personal dan banyak workspace tim.
- CRUD task/activity dan tracking status.
- Kategori bawaan dan kategori custom.
- Reminder email otomatis serta manual.
- Kalender berbasis deadline task.
- Sticky note yang dapat dikonversi menjadi task.
- Activity log permanen.
- Autentikasi email dan password dengan verifikasi email.

Desain lengkap dan aturan bisnis resmi berada di:

`docs/superpowers/specs/2026-07-29-todo-backend-design.md`

## 2. Arti Status dalam Dokumen Ini

- **Selesai:** file atau konfigurasi sudah ada dan diverifikasi di repository.
- **Disetujui, belum diimplementasikan:** keputusan desain sudah dikunci, tetapi
  migration, model, route, action, atau test belum dibuat.
- **Belum dimulai:** belum ada implementasi maupun verifikasi.
- **Ditunda:** sengaja tidak dikerjakan pada demo lokal, tetapi tidak boleh
  dilupakan sebelum staging atau produksi.
- **Runtime belum aktif:** konfigurasi tersedia, tetapi service tidak dapat
  diverifikasi sedang berjalan pada snapshot ini.

## 3. Stack yang Sudah Ada

Status: **Selesai**.

- PHP `^8.2` dan Laravel `^12.0`.
- Inertia Laravel `^3.2`.
- Vue `^3.5`, Vite `^7`, dan Tailwind CSS `^4`.
- Satu aplikasi Laravel-Inertia; route web merender Page Vue.
- Root route saat ini hanya merender `Todo/Index`.
- MySQL 8.4, phpMyAdmin, dan Mailpit didefinisikan di `compose.yaml`.
- Queue, cache, dan session dikonfigurasi menggunakan database pada
  `.env.example`.
- Alias frontend `@` mengarah ke `resources/js` tanpa `baseUrl` deprecated.

Endpoint development yang direncanakan oleh konfigurasi:

- Laravel: `http://127.0.0.1:8000` setelah server dijalankan.
- MySQL host: `127.0.0.1:3307`.
- phpMyAdmin: `http://127.0.0.1:8080`.
- Mailpit UI: `http://127.0.0.1:8025`.
- Mailpit SMTP: `127.0.0.1:1025`.

Jangan menyalin nilai dari `.env` ke dokumentasi. `.env.example` adalah contoh
lokal yang dapat dilihat, sedangkan `.env` tetap diabaikan Git.

## 4. Pekerjaan yang Sudah Dilakukan

### Fondasi project

Status: **Selesai**.

- Laravel, Inertia, Vue, Vite, dan Tailwind tersedia.
- Struktur awal clean architecture ringan dibuat.
- `resources/js/Pages/Todo/Index.vue` menjadi entry Inertia.
- Feature frontend Todo dan layout dasar tersedia.
- Bootstrap tidak digunakan.

Commit terkait:

- `8aa97a3` - initialize Laravel Inertia project.
- `3941bd3` - define clean architecture structure.
- `27d8ca7` - establish Todo feature architecture.
- `499064d` - complete Todo architecture scaffold.

### Infrastruktur backend lokal

Status file: **Selesai**.

- `compose.yaml` menyediakan MySQL, phpMyAdmin, dan Mailpit.
- `.env.example` mengarah ke MySQL port 3307 dan Mailpit port 1025.
- Compose configuration lolos parsing pada pemeriksaan 31 Juli 2026.

Commit terkait:

- `2e978cb` - add Docker backend services.

Status runtime snapshot 31 Juli 2026: **Runtime belum aktif**.

- Docker daemon tidak dapat diakses dari sesi pemeriksaan.
- Koneksi MySQL `127.0.0.1:3307` ditolak.
- `php artisan migrate:status` belum dapat memverifikasi database aktif.
- Jangan menyimpulkan migration belum pernah dijalankan; nyalakan Docker lalu
  periksa ulang status migration.

### Konfigurasi editor

Status: **Selesai**.

- `baseUrl` deprecated dihapus dari `jsconfig.json`.
- Alias menggunakan `./resources/js/*`.
- Source frontend dibatasi melalui `include`; `vendor`, `public`, dan
  `node_modules` dikecualikan.

Commit terkait:

- `4fd6b29` - fix deprecated jsconfig alias setup.

### Desain backend

Status: **Selesai ditulis dan di-commit; menunggu review akhir pengguna**.

- Pendekatan: modular monolith dengan clean architecture ringan.
- Dokumen: `docs/superpowers/specs/2026-07-29-todo-backend-design.md`.
- Commit lokal: `e6d8528` - define todo backend architecture.
- Commit ini belum berada di `origin/main` pada snapshot awal handoff.

## 5. Yang Belum Diimplementasikan

Status seluruh item berikut: **Disetujui, belum diimplementasikan**, kecuali
disebut berbeda.

- Laravel Fortify dan seluruh route autentikasi.
- Verifikasi email dan password reset melalui Mailpit.
- Workspace personal, workspace tim, dan membership.
- Kapasitas tim default 5 dan opsi 10 orang, termasuk owner.
- Kode bergabung tim yang aktif 5 menit dan dapat dipakai banyak pengguna.
- Transfer ownership sebelum owner keluar.
- Migration, model, policy, request, controller, dan action untuk workspace.
- Migration serta CRUD kategori.
- Migration serta CRUD task/activity.
- Activity log immutable.
- Sticky note dan konversi menjadi task.
- Reminder H-7, H-3, dan beberapa reminder manual.
- Scheduler, queue job, notification email, dan delivery tracking.
- Query kalender berbasis deadline.
- Test bisnis, policy, concurrency, dan reminder.
- UI final berbasis shadcn-vue.

Route list pada snapshot hanya berisi route root, route internal Inertia
DevTools, route storage, dan health check. Belum ada route login, workspace,
task CRUD, sticky note, reminder, atau kalender.

Migration yang tersedia baru migration bawaan users, cache, dan jobs. Belum ada
migration domain Todo atau Workspace.

## 6. Ketidaksesuaian Scaffold yang Harus Diperbaiki Saat Implementasi

File berikut berasal dari scaffold lama dan tidak lagi sesuai keputusan final:

- `app/Domain/Todo/Enums/TodoStatus.php` masih berisi `Pending`,
  `BelumSelesai`, dan `Selesai`.
- Status final harus menjadi `belum_dikerjakan`, `sedang_dikerjakan`, dan
  `selesai`.
- `app/Domain/Todo/Enums/TodoPriority.php` masih ada, sedangkan priority sudah
  dikeluarkan dari scope awal.

Jangan menganggap enum lama sebagai requirement. Perbaikannya dilakukan dalam
fase implementasi Todo dan harus disertai test.

## 7. Keputusan Backend yang Sudah Dikunci

### Workspace dan tim

- Setiap user terverifikasi memperoleh satu workspace personal.
- User dapat bergabung ke banyak tim.
- Task tim tidak memiliki assignee dan berlaku untuk seluruh anggota.
- Semua anggota boleh membuat, melihat, mengedit, dan mengubah status.
- Hanya pembuat atau owner yang boleh menghapus task dan sticky note.
- Owner wajib memindahkan ownership sebelum keluar.
- Jika owner satu-satunya anggota, owner dapat mengundang anggota atau menghapus
  tim.
- Penghapusan tim dikonfirmasi dengan mengetik
  `konfirmasi hapus tim <nama tim>`, tanpa konfirmasi password tambahan.
- Data operasional dihapus permanen; activity log tetap disimpan sebagai arsip.

### Kode tim dan kapasitas

- Hanya satu kode aktif per tim.
- Membuat kode baru mencabut kode lama.
- Kode disimpan sebagai hash dan berlaku lima menit.
- Kode yang sama dapat dipakai banyak user sampai kedaluwarsa, dicabut, atau
  kapasitas penuh.
- Kapasitas default lima orang dan dapat dinaikkan menjadi sepuluh.
- Owner termasuk dalam hitungan kapasitas.
- Claim membership harus transactional agar kursi terakhir tidak diklaim lebih
  dari batas.

### Task

- Field utama: judul, deskripsi opsional, status, kategori, deadline,
  workspace, dan pembuat.
- Tidak ada priority pada scope awal.
- Tidak ada assignee pada task tim.
- Status: Belum Dikerjakan, Sedang Dikerjakan, dan Selesai.
- Semua anggota dapat menyelesaikan atau membuka kembali task.
- Deadline wajib berupa tanggal, jam, dan menit dalam WIB.
- Deadline minimal lima menit dari waktu penyimpanan.
- Database menyimpan waktu dalam UTC.
- Penghapusan bersifat permanen.

### Kategori dan sticky note

- Kategori bawaan: Meeting, Report Progress, dan Lainnya.
- Workspace dapat membuat kategori custom.
- Kategori custom tidak dapat dihapus saat masih digunakan task.
- Sticky note bersifat bebas, memiliki warna, dan dapat dikonversi menjadi task.
- Konversi tetap meminta kategori dan deadline karena keduanya wajib.
- Note tetap disimpan setelah dikonversi.

### Reminder dan kalender

- Reminder otomatis dibuat pada H-7 dan H-3 jika waktunya masih di masa depan.
- Reminder manual dapat dibuat lebih dari satu.
- Jika H-7 dan H-3 sudah lewat, minimal satu reminder manual wajib diisi.
- Reminder tim dikirim kepada semua anggota aktif dengan email terverifikasi.
- Status Selesai menghentikan reminder; membuka kembali task mengaktifkan jadwal
  masa depan yang masih valid.
- Kalender tidak memiliki tabel event; kalender membaca `deadline_at` task.

### Autentikasi demo

- Email dan password dengan verifikasi email.
- Laravel Fortify menjadi backend autentikasi.
- Email development masuk ke Mailpit.
- Route aplikasi menggunakan middleware `auth` dan `verified`.
- Login throttling, hashing, session regeneration, logout invalidation, dan CSRF
  tetap diterapkan pada demo lokal.

## 8. Catatan Keamanan yang Ditunda, Bukan Dibatalkan

Status: **Ditunda sampai staging/produksi**.

- 2FA TOTP dan recovery code.
- Password minimum 12 karakter dan pemeriksaan password bocor.
- HTTPS serta cookie Secure, HttpOnly, dan SameSite yang sesuai.
- SMTP atau email provider sungguhan sebagai pengganti Mailpit.
- Pencabutan session perangkat lain setelah reset atau perubahan password.
- Response autentikasi generik agar keberadaan email tidak bocor.
- Process supervisor untuk queue dan scheduler.
- Backup database, monitoring, dan failed-job handling.

Jangan menyatakan aplikasi siap produksi sebelum seluruh item ini diverifikasi.

## 9. Urutan Pekerjaan Berikutnya

### Fase 0 - Review dan planning

Status: **Sedang berjalan**.

1. Pengguna meninjau backend design spec.
2. Setelah pengguna menyetujui spec, gunakan skill `writing-plans`.
3. Buat implementation plan terperinci dengan checkpoint dan test.
4. Jangan mulai implementasi fitur sebelum plan disetujui.

### Fase 1 - Autentikasi demo lokal

- Pasang dan konfigurasi Fortify untuk Inertia.
- Buat UI/backend register, login, logout, verifikasi email, dan reset password.
- Hubungkan email autentikasi ke Mailpit.
- Buat personal workspace setelah verifikasi email.
- Tambahkan test autentikasi dan lifecycle session.

Kriteria selesai: user dapat register, memverifikasi email melalui Mailpit,
login, logout, reset password, dan mengakses route terproteksi.

### Fase 2 - Workspace dan membership

- Buat migration, model, enum, policy, request, controller, dan actions.
- Implementasikan tim, kapasitas 5/10, kode 5 menit, join, remove member,
  transfer owner, leave, dan delete team.
- Tambahkan transaction, lock, rate limiting, dan test concurrency.

Kriteria selesai: pemisahan workspace terbukti melalui test dan tidak ada user
yang dapat membaca data workspace lain.

### Fase 3 - Kategori dan Todo CRUD

- Ganti enum status lama dengan tiga status final.
- Hapus enum priority dari scope implementasi.
- Implementasikan kategori bawaan dan custom.
- Implementasikan task CRUD, deadline wajib, policy kolaboratif, dan reopen.

Kriteria selesai: personal dan tim dapat mengelola task sesuai policy serta
semua validasi deadline lulus test.

### Fase 4 - Activity log

- Buat log immutable dengan snapshot aman.
- Pastikan hard delete subject tidak menghapus log.
- Catat perubahan workspace, task, kategori, sticky note, dan keamanan penting.

Kriteria selesai: operasi domain gagal secara atomik jika log wajib tidak dapat
disimpan, dan log tetap ada setelah subject dihapus.

### Fase 5 - Sticky note

- Implementasikan CRUD, warna, policy, dan konversi menjadi task.
- Pertahankan note serta relasi konversinya setelah task dibuat.

Kriteria selesai: konversi menghasilkan task valid tanpa menghapus note asal.

### Fase 6 - Reminder dan email

- Implementasikan jadwal H-7, H-3, dan manual.
- Implementasikan scheduler, queued notification, delivery tracking, retry, dan
  idempotensi.
- Hitung penerima tim pada waktu pengiriman.

Kriteria selesai: Mailpit menerima email yang tepat tanpa delivery ganda dan
failed delivery dapat dicoba ulang.

### Fase 7 - Kalender

- Buat query task berdasarkan rentang deadline dan workspace aktif.
- Konversikan UTC ke Asia/Jakarta pada boundary presentasi.

Kriteria selesai: kalender hanya menampilkan task dari workspace aktif pada
tanggal dan waktu WIB yang benar.

### Fase 8 - Integrasi dan hardening

- Jalankan seluruh feature/unit test dan build frontend.
- Verifikasi migration dari database kosong.
- Audit policy, index, transaction, queue, dan scheduler.
- Kerjakan checklist keamanan staging sebelum deployment nyata.

## 10. Titik Mulai untuk AI Berikutnya

Lakukan langkah berikut sebelum mengubah kode:

```powershell
git status -sb
git log -5 --oneline
php artisan route:list
docker compose config --quiet
docker compose ps
php artisan migrate:status
```

Jika Docker belum aktif, laporkan kondisi tersebut dan nyalakan hanya setelah
memastikan Docker Desktop pengguna tersedia. Jangan menjalankan migration baru
ke database bersama tanpa memastikan target database.

Kemudian:

1. Baca backend design spec secara penuh.
2. Pastikan pengguna sudah menyetujui spec.
3. Susun implementation plan; jangan langsung membuat migration atau memasang
   package.
4. Mulai dari fase autentikasi setelah plan disetujui.

Prompt ringkas untuk melanjutkan:

> Baca `AGENTS.md`, `docs/ai-handoff/BACKEND_PROGRESS.md`, dan backend design
> spec. Verifikasi repository serta runtime. Lanjutkan dari fase yang berstatus
> sedang berjalan, jangan menganggap item desain sudah terimplementasi, dan
> perbarui handoff setelah menyelesaikan satu fase.

## 11. Protokol Update Dokumentasi

Setelah setiap sesi implementasi, AI yang mengerjakan wajib memperbarui dokumen
ini:

1. Ubah status fase yang benar-benar selesai.
2. Cantumkan file dan migration yang ditambahkan.
3. Cantumkan command verifikasi beserta hasilnya secara ringkas.
4. Catat error atau blocker yang belum selesai.
5. Catat commit lokal dan apakah sudah dipush.
6. Tentukan satu next action yang konkret.

Jangan menandai fase selesai hanya karena file sudah dibuat. Fase selesai jika
kriteria selesai dan test relevan sudah terverifikasi.
