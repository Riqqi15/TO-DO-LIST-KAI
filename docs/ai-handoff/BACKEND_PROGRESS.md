# Backend Progress and AI Handoff

Dokumen ini adalah sumber status utama backend To Do List KAI. Verifikasi ulang
Git, Docker, migration, queue, dan test sebelum melanjutkan karena runtime dapat
berubah setelah snapshot ini.

**Snapshot:** 1 Agustus 2026, Asia/Jakarta

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
| Sticky note | Selesai | CRUD kolaboratif, pin, dan urutan pin persistent |
| Reminder email | Selesai untuk demo | H-7/H-3/manual, queue job, delivery tracking/retry |
| Kalender | Selesai | Query diturunkan dari `todos.deadline_at` |
| Visualisasi DB | Selesai | ERD Mermaid dan panduan phpMyAdmin/DBeaver |
| UI shadcn-vue final | Selesai | Dashboard responsif, auth, Kanban/Daftar/Kalender, dan browser QA |

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
- Task menyimpan `started_at` dan `completed_at`. Form perubahan status memakai
  `status_at`, sedangkan `deadline_at` berubah saat task kembali ke
  `belum_dikerjakan`.
- Tidak ada priority dan tidak ada tabel calendar event.
- Kategori sistem: Meeting, Report Progress, Lainnya. Kategori custom yang
  dipakai task tidak dapat dihapus.
- Deadline wajib minimal lima menit dari saat penyimpanan. Input WIB dikonversi
  ke UTC; payload tampilan menyertakan `deadline_wib`.
- Reminder otomatis H-7 dan H-3 hanya dibuat bila jadwalnya belum lewat.
  Jika keduanya lewat, reminder manual wajib. Waktu manual fleksibel sampai
  menit, harus di masa depan dan sebelum deadline.
- Menyelesaikan task membatalkan reminder; task dapat dibuka lagi tanpa
  membuat reminder melalui form status. Reminder manual tetap dikelola pada
  bagian Reminder.
- Reminder tim menghitung semua anggota aktif yang emailnya terverifikasi pada
  waktu pengiriman, bukan pada waktu reminder dibuat.
- Sticky note dapat dipin, dilepas pinnya, dan diurutkan secara manual. Data
  konversi lama tetap tersimpan, tetapi fitur konversi tidak lagi aktif.
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
4. `2026_08_01_000004_add_status_dates_and_pin_order.php`

Seluruh migration domain berstatus `Ran` pada MySQL Docker pada snapshot ini.

## Route aplikasi

Semua route domain berada di middleware `auth` dan `verified`:

- Tim: create, join, invite, capacity, transfer ownership, remove member,
  leave, delete.
- Kategori: create, update, delete.
- Todo: create, update, change status, delete.
- Reminder manual: create, delete.
- Sticky note: create, update, delete, toggle pin, dan reorder pin.
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
- Runtime Docker lokal: `app` healthy, `mysql` dan `mailpit` healthy,
  `migrate` selesai dengan exit code `0`, serta `queue` dan `scheduler`
  berstatus running.
- `GET /login` melalui Docker: HTTP **200** sekitar **0,03 detik** pada
  verifikasi akhir.
- Scheduler Docker menjalankan `process-due-todo-reminders` setiap menit;
  queue memproses job verifikasi sekitar **19 ms** dan `queue:failed`
  melaporkan tidak ada failed job.
- Mailpit melalui Docker: HTTP **200**.

Checkpoint frontend pada 1 Agustus 2026:

- UI final memakai shadcn-vue, Lucide, Tailwind CSS 4, Plus Jakarta Sans, dan
  IBM Plex Mono.
- Dashboard menyediakan Board, Daftar, Kalender, pencarian lokal, filter,
  task detail, task create/edit, status, dan reminder.
- Sticky note, pin note, activity timeline, kategori, pembuatan/join tim,
  kode undangan, kapasitas, keluar tim, dan hapus tim tersedia di UI.
- Halaman login, registrasi, lupa/reset password, dan verifikasi email memakai
  visual system yang sama.
Checkpoint dashboard awal sebelum penambahan tanggal status dan pin:

- `npm run build`: berhasil; halaman Inertia dipecah menjadi chunk terpisah dan
  chunk dashboard sekitar **200,88 kB** sebelum gzip.
- `php artisan test`: **41 test lulus, 187 assertion**.
- Browser QA desktop berhasil untuk registrasi, verifikasi email, create task
  dengan reminder, perubahan status, Daftar, Kalender, sticky note, Activity,
  kategori custom, dan task detail.
- Browser console tidak melaporkan warning/error dan layout tidak memiliki
  horizontal overflow pada body.
- Transfer ownership dan pengeluaran anggota belum dirender karena props
  Inertia belum memuat identitas anggota. UI sengaja tidak meminta ID internal
  atau menebak anggota.

Checkpoint status date dan pinned note pada 1 Agustus 2026:

- Board menampilkan Deadline, Mulai, atau Selesai sesuai status aktif.
- Pemilihan status dari kartu membuka dialog agar pengguna mengonfirmasi
  tanggal status sebelum menyimpan.
- Status form tidak lagi membuat reminder. Backend memvalidasi deadline,
  tanggal mulai, dan tanggal selesai secara terpisah.
- Sticky note memakai Pin, PinOff, dan GripVertical. Pinned notes tampil pada
  grup teratas dan urutannya disimpan melalui SortableJS serta endpoint reorder.
- `php artisan test`: **45 test lulus, 230 assertion**.
- `npm run build`: **3.089 module** ditransformasi dan build produksi berhasil.
- `php artisan route:list --except-vendor`: **25 route**.
- Migration status date dan pin berstatus `Ran` pada MySQL lokal.
- Browser QA desktop berhasil untuk create task, perubahan status berurutan,
  label tanggal Board, create note, pin, kontrol drag aktif, dan console tanpa
  error. Unpin dan persistence reorder diverifikasi melalui feature test backend.

Checkpoint penyempurnaan UI (3 Agustus 2026):
- **Kalender**: Penambahan `TaskOverviewDialog` untuk melihat detail awal sebelum mengubah status atau reminder, serta fungsi inline edit untuk judul dan deskripsi terpadu pada form detail task kalender.
- **Sticky Notes**: Penambahan filter catatan berdasarkan warna, serta opsi pengurutan otomatis berdasarkan urutan warna yang sekaligus menyesuaikan logika *drag-and-drop* pin secara dinamis.
- Seluruh penyempurnaan lolos _build_ (`npm run build`) tanpa peringatan error.

Test mencakup auth, isolasi workspace, kode reusable/expired, kapasitas,
transfer owner, exact delete confirmation, kategori terpakai, Todo/status,
tanggal status, deadline dekat, reopen, policy delete, pin/reorder sticky note,
email recipient,
idempotensi delivery, kalender, dan immutability activity log.

## Menjalankan demo lokal

```powershell
docker compose up -d --build
```

Perintah tersebut otomatis menjalankan:

- migration satu kali setelah MySQL sehat;
- Laravel web pada port `8000`;
- database queue worker dengan tiga percobaan;
- scheduler reminder setiap menit;
- MySQL, phpMyAdmin, dan Mailpit.

Laravel, dependency Composer, dan hasil build Vue berada di image
`todo-list-kai-laravel:local`. Setelah mengubah source, jalankan kembali
`docker compose up -d --build`. Build berikutnya menggunakan cache selama file
dependency tidak berubah. Pendekatan image self-contained dipakai karena bind
mount seluruh `vendor` dari Windows membuat request Laravel sangat lambat.

Periksa kondisi runtime:

```powershell
docker compose ps -a
docker compose logs app queue scheduler
```

`migrate` yang berstatus `Exited (0)` adalah kondisi normal karena service itu
memang one-shot. Jangan menjalankan `php artisan serve`, `queue:work`, atau
`schedule:work` lagi di Windows saat stack Docker aktif agar port dan reminder
tidak diproses ganda.

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

Backend feature scope dan UI shadcn-vue yang disetujui sudah diimplementasikan.
Pekerjaan berikutnya berfokus pada UAT dan kesiapan staging/produksi.

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
