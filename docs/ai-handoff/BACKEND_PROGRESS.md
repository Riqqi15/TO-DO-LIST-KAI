# Backend Progress and AI Handoff

Dokumen ini adalah sumber status utama backend To Do List KAI. Verifikasi ulang
Git, Docker, migration, queue, dan test sebelum melanjutkan karena runtime dapat
berubah setelah snapshot ini.

**Snapshot:** 31 Juli 2026, Asia/Jakarta

**Branch kerja:** `feat/backend-complete`

**Target saat ini:** demo lokal, belum dinyatakan siap produksi

**Commit implementasi backend:** `4c5d03c` -
`feat: complete collaborative todo backend` (lokal, belum dipush pada snapshot).

## Status ringkas

| Fase | Status | Bukti utama |
|---|---|---|
| Fondasi Laravel/Inertia | Selesai | Laravel 12, Inertia 3, Vue 3, Tailwind 4 |
| Auth email/password | Selesai | Fortify, verified middleware, reset password |
| Workspace personal/tim | Selesai | Policy/action/transaction dan feature test |
| Kategori dan Todo | Selesai | CRUD, 3 status, deadline wajib, tanpa priority |
| Activity log | Selesai | Snapshot, actor nullable, mutation model ditolak |
| Sticky note | Selesai | CRUD kolaboratif dan konversi ke Todo |
| Reminder email | Selesai untuk demo | H-7/H-3/manual, queue job, delivery tracking/retry |
| Kalender | Selesai | Query diturunkan dari `todos.deadline_at` |
| Visualisasi DB | Selesai | ERD Mermaid dan panduan phpMyAdmin/DBeaver |
| UI shadcn-vue final | Belum dimulai | Backend props dan route sudah tersedia |

## Kontrak produk yang sudah diimplementasikan

- Setiap user mendapat workspace personal secara idempoten setelah email
  terverifikasi dan dapat menjadi anggota banyak tim.
- Task tim tidak memiliki assignee. Semua anggota tim dapat membuat, membaca,
  mengedit, dan mengubah status task.
- Hanya pembuat task/sticky note atau owner tim yang dapat menghapusnya.
- Owner wajib memindahkan ownership sebelum keluar dari tim.
- Kapasitas tim default 5 dan dapat dipilih 10, owner ikut dihitung.
- Kode tim disimpan sebagai SHA-256, hanya satu kode aktif, reusable selama
  lima menit, dan join memakai transaction serta row lock.
- Penghapusan tim membutuhkan teks persis
  `konfirmasi hapus tim <nama tim>` dan menghapus data operasional permanen.
- Status task hanya `belum_dikerjakan`, `sedang_dikerjakan`, dan `selesai`.
- Tidak ada priority dan tidak ada tabel calendar event.
- Kategori sistem: Meeting, Report Progress, Lainnya. Kategori custom yang
  dipakai task tidak dapat dihapus.
- Deadline wajib minimal lima menit dari saat penyimpanan. Input WIB dikonversi
  ke UTC; payload tampilan menyertakan `deadline_wib`.
- Reminder otomatis H-7 dan H-3 hanya dibuat bila jadwalnya belum lewat.
  Jika keduanya lewat, reminder manual wajib. Waktu manual fleksibel sampai
  menit, harus di masa depan dan sebelum deadline.
- Menyelesaikan task membatalkan reminder; task dapat dibuka lagi. Bila tidak
  ada jadwal masa depan, request reopen dapat mengirim `manual_reminder_at`.
- Reminder tim menghitung semua anggota aktif yang emailnya terverifikasi pada
  waktu pengiriman, bukan pada waktu reminder dibuat.
- Sticky note tetap tersimpan setelah dikonversi dan menyimpan link ke Todo.
- Activity log tidak memiliki endpoint update/delete dan model menolak mutasi.
  Snapshot tetap ada ketika subject atau workspace operasional dihapus.

Spesifikasi keputusan asli tetap berada di
`docs/superpowers/specs/2026-07-29-todo-backend-design.md`.

## Struktur implementasi penting

- `app/Domain/Workspace`: enum, model, dan action workspace/tim.
- `app/Domain/Category`: model dan action kategori.
- `app/Domain/Todo`: status, model, dan action CRUD/status.
- `app/Domain/Reminder`: model, enum, automatic/manual scheduling, delivery.
- `app/Domain/StickyNote`: model dan action CRUD/konversi.
- `app/Domain/ActivityLog`: model immutable dan pencatatan snapshot.
- `app/Http/Requests`: validasi boundary HTTP.
- `app/Http/Controllers`: adapter tipis untuk Inertia/web route.
- `app/Policies`: isolasi akses workspace dan content.
- `app/Jobs/ProcessDueReminders.php`: queued reminder processor.
- `app/Notifications/Todo/TodoReminderNotification.php`: email reminder.
- `routes/console.php`: scheduler reminder setiap menit.
- `app/Http/Controllers/Todo/TodoPageController.php`: props dashboard dan
  calendar JSON untuk widget Inertia.

Migration domain:

1. `2026_07_31_000001_create_workspace_tables.php`
2. `2026_07_31_000002_create_todo_tables.php`
3. `2026_07_31_000003_create_collaboration_tables.php`

Ketiganya sudah berstatus `Ran` pada MySQL Docker, batch 3, pada snapshot ini.

## Route aplikasi

Semua route domain berada di middleware `auth` dan `verified`:

- Tim: create, join, invite, capacity, transfer ownership, remove member,
  leave, delete.
- Kategori: create, update, delete.
- Todo: create, update, change status, delete.
- Reminder manual: create, delete.
- Sticky note: create, update, delete, convert.
- Kalender: `GET /workspaces/{workspace}/calendar`.
- Dashboard Inertia: `GET /app` dengan workspace, kategori, todo, sticky note,
  activity, dan timezone props.

Gunakan `php artisan route:list --except-vendor` untuk daftar aktual. Snapshot
terakhir menampilkan 24 route non-vendor.

## Verifikasi terakhir

Hasil sebelum final commit pada 31 Juli 2026:

- `php artisan test`: **41 test lulus, 187 assertion**.
- `php artisan route:list --except-vendor`: **24 route**.
- `php artisan schedule:list`: job `process-due-todo-reminders` setiap menit.
- `php artisan migrate --force`: tiga migration domain berhasil dijalankan.
- `php artisan migrate:status`: seluruh migration berstatus `Ran`.
- `vendor/bin/pint --test`: lulus tanpa perubahan format.
- `composer validate --no-check-publish`: valid; constraint Fortify dipatok ke
  `^1.37` dan `composer update --lock` melaporkan tidak ada advisory keamanan.
- `npm.cmd run build`: berhasil, **608 module** ditransformasi.

Test mencakup auth, isolasi workspace, kode reusable/expired, kapasitas,
transfer owner, exact delete confirmation, kategori terpakai, Todo/status,
deadline dekat, reopen, policy delete, sticky conversion, email recipient,
idempotensi delivery, kalender, dan immutability activity log.

## Menjalankan demo lokal

```powershell
docker compose up -d
php artisan migrate
npm run build
php artisan serve
```

Jalankan dua terminal background:

```powershell
php artisan queue:work --tries=3
php artisan schedule:work
```

- Aplikasi: `http://127.0.0.1:8000`
- phpMyAdmin: `http://127.0.0.1:8080`
- Mailpit: `http://127.0.0.1:8025`

Daftar melalui UI, lalu klik link verifikasi di Mailpit. Jangan menambahkan
akun demo dengan password lemah ke seeder. `DatabaseSeeder` sengaja tidak
membuat akun.

## Visualisasi database

- ERD: `docs/database/ERD.md`.
- Langkah phpMyAdmin/DBeaver: `docs/database/VISUALIZATION.md`.
- phpMyAdmin login menggunakan `DB_USERNAME` dan `DB_PASSWORD` dari `.env`;
  jangan menyalin secret ke dokumen atau commit.

## Batas demo dan pekerjaan berikutnya

Backend feature scope yang disetujui sudah diimplementasikan. Pekerjaan utama
berikutnya adalah UI final menggunakan shadcn-vue dan menghubungkan form/kanban/
kalender ke route backend yang tersedia.

Sebelum staging/produksi, kerjakan dan verifikasi:

- HTTPS, cookie secure, `APP_DEBUG=false`, secret rotation.
- SMTP/provider email sungguhan dan domain email terverifikasi.
- Process supervisor untuk queue/scheduler dan monitoring failed jobs.
- Backup/restore database, logging/alerting, dan retention activity log.
- 2FA, recovery codes, kebijakan password produksi, dan session management.
- Load/concurrency test MySQL untuk kursi terakhir tim dan worker reminder.
- Security review serta UAT; keberhasilan demo lokal bukan bukti production
  readiness.

## Protokol untuk AI berikutnya

1. Baca `AGENTS.md`, dokumen ini, dan backend design spec.
2. Jalankan `git status -sb`, `git log -5 --oneline`, `artisan test`,
   `migrate:status`, `route:list`, dan `schedule:list`.
3. Jangan membuat REST frontend terpisah; tetap Laravel + Inertia + Vue.
4. Jangan menambah priority, assignee, atau calendar event table tanpa keputusan
   baru dari pengguna.
5. Update dokumen ini setelah progress material, dengan hasil verifikasi nyata.
6. Jangan pernah memasukkan isi `.env` atau password database ke dokumentasi.
