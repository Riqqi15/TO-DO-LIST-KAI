# To Do List KAI Backend Design

## Tujuan

Membangun backend aplikasi To Do List internal berbasis Laravel, Inertia, Vue,
MySQL, dan queue yang mendukung pekerjaan personal sekaligus kolaborasi tim.
Desain ditujukan untuk penggunaan awal sekitar 20-30 pengguna tanpa menambah
abstraksi yang belum diperlukan.

## Scope Fitur

- Autentikasi email dan password dengan verifikasi email.
- Workspace personal untuk setiap pengguna terverifikasi.
- Banyak workspace tim untuk setiap pengguna.
- Tracking status dan CRUD task/activity.
- Kategori bawaan serta kategori custom.
- Reminder email otomatis H-7 dan H-3.
- Beberapa reminder manual per task.
- Kalender berbasis deadline task.
- Sticky note bebas yang dapat dikonversi menjadi task.
- Activity log permanen.

Prioritas task, penanggung jawab task tim, event kalender terpisah, REST API,
microservice, CQRS, dan repository abstraction tidak termasuk scope awal.

## Pendekatan Arsitektur

Backend menggunakan modular monolith dengan clean architecture ringan:

```text
Route
  -> Controller
  -> Form Request dan Policy
  -> Domain Action
  -> Eloquent Model
  -> Inertia Response
```

Controller hanya menerjemahkan request dan response. Form Request menangani
validasi input. Policy menangani akses. Satu Action menangani satu use case.
Model Eloquent menangani persistence. Job dan Notification menangani pekerjaan
email di background.

Pendekatan full DDD atau CQRS tidak digunakan karena kompleksitasnya belum
sebanding dengan kebutuhan aplikasi internal ini.

## Organisasi Kode

```text
app/
|-- Domain/
|   |-- Workspace/
|   |   |-- Actions/
|   |   |-- Enums/
|   |   `-- Models/
|   |-- Todo/
|   |   |-- Actions/
|   |   |-- Enums/
|   |   `-- Models/
|   |-- Category/
|   |-- StickyNote/
|   |-- Reminder/
|   |   `-- Jobs/
|   `-- ActivityLog/
|-- Http/
|   |-- Controllers/
|   `-- Requests/
|-- Notifications/
|   |-- Auth/
|   `-- Todo/
`-- Policies/
```

## Model Workspace

Semua data personal dan tim berada di dalam workspace. Model ini menghindari
kolom `user_id` dan `team_id` yang saling nullable pada setiap tabel fitur.

```text
User
  -> WorkspaceMember
    -> Workspace (personal atau team)
      -> Todos
      -> Categories
      -> StickyNotes
      -> ActivityLogs
```

Aturannya:

- Setiap pengguna terverifikasi otomatis memperoleh satu workspace personal.
- Satu pengguna dapat menjadi anggota banyak workspace tim.
- Workspace tim memiliki satu owner dan beberapa member.
- Owner ikut dihitung dalam kapasitas anggota.
- Kapasitas awal tim adalah lima orang: satu owner dan maksimal empat member.
- Owner dapat menaikkan kapasitas menjadi sepuluh orang.
- Kapasitas tidak dapat melebihi sepuluh orang.
- Kapasitas tidak dapat diturunkan menjadi lima jika anggota aktif lebih dari
  lima.

## Struktur Data

### `users`

Menyimpan identitas, email unik, password hash, waktu verifikasi email, dan
kolom autentikasi yang diperlukan Laravel Fortify.

### `workspaces`

Menyimpan nama, jenis `personal` atau `team`, `member_limit`, dan metadata
workspace. Workspace personal tidak dapat menerima anggota lain.

### `workspace_members`

Menyimpan relasi pengguna dan workspace dengan role `owner` atau `member`.
Kombinasi `workspace_id` dan `user_id` harus unik.

### `team_invites`

Menyimpan `workspace_id`, hash kode, `expires_at`, `revoked_at`, dan pembuat
kode. Tidak menggunakan `used_at` atau `used_by` karena satu kode dapat dipakai
banyak pengguna selama aktif.

### `categories`

Menyimpan kategori bawaan sistem atau kategori custom workspace. Kategori
bawaan tidak memiliki workspace dan ditandai sebagai kategori sistem. Kategori
custom selalu memiliki `workspace_id` dan `created_by`.

### `todos`

Menyimpan workspace, pembuat, kategori, judul, deskripsi, status, dan deadline.
Task tim tidak memiliki assignee.

### `todo_reminders`

Menyimpan task, tipe `automatic_7_days`, `automatic_3_days`, atau `manual`,
waktu terjadwal, serta status aktif atau dibatalkan.

### `reminder_deliveries`

Menyimpan satu baris per reminder dan penerima, status `pending`, `sent`, atau
`failed`, jumlah percobaan, waktu terkirim, dan ringkasan error terakhir.
Kombinasi reminder dan pengguna harus unik.

### `sticky_notes`

Menyimpan workspace, pembuat, isi, warna, `converted_to_todo_id`, dan
`converted_at`.

### `activity_logs`

Menyimpan workspace opsional, pelaku, jenis aksi, tipe dan ID subject, waktu,
serta snapshot perubahan yang aman. `workspace_id` dapat menjadi null dengan
perilaku `ON DELETE SET NULL`, dan log tidak memiliki foreign key wajib ke
subject. Snapshot menyimpan nama workspace serta identitas subject yang aman
agar log tetap bermakna setelah data operasional dihapus permanen. Event
keamanan akun yang tidak terkait workspace juga menggunakan `workspace_id`
null.

## Status Task

Nilai status:

- `belum_dikerjakan`, label "Belum Dikerjakan".
- `sedang_dikerjakan`, label "Sedang Dikerjakan".
- `selesai`, label "Selesai".

Status awal adalah `belum_dikerjakan`. Semua anggota aktif workspace dapat
mengubah status. Task selesai dapat dibuka kembali menjadi
`sedang_dikerjakan`. Seluruh perubahan status dicatat.

## Field dan Validasi Task

- Judul wajib.
- Deskripsi opsional.
- Kategori wajib.
- Deadline tanggal dan jam wajib.
- Deadline minimal lima menit setelah waktu pembuatan atau perubahan.
- Waktu UI menggunakan `Asia/Jakarta`.
- Waktu database disimpan dalam UTC.
- UI menampilkan tanggal, jam, dan menit; detik disimpan sebagai `00`.
- Task tidak memiliki priority pada versi awal.
- Task tim tidak memiliki penanggung jawab.

## Kategori

Kategori bawaan:

- Meeting.
- Report Progress.
- Lainnya.

Aturannya:

- Kategori bawaan tidak dapat diedit atau dihapus.
- Anggota workspace dapat membuat dan menggunakan kategori custom.
- Nama kategori custom tidak boleh sama dengan kategori bawaan atau kategori
  custom lain dalam workspace yang sama, tanpa membedakan huruf besar-kecil.
- Pembuat kategori atau owner tim dapat mengganti nama dan menghapus kategori.
- Kategori custom tidak dapat dihapus selama masih digunakan task.
- Pengguna harus memindahkan atau menghapus task terkait sebelum menghapus
  kategori.

## Hak Akses

### Workspace Personal

Pemilik memiliki akses penuh terhadap task, kategori custom, reminder, sticky
note, kalender, dan activity log miliknya.

### Workspace Tim

Semua data operasional merupakan milik workspace tim. `created_by` adalah
metadata pembuat, bukan penanggung jawab.

| Operasi | Pembuat | Member lain | Owner |
|---|---:|---:|---:|
| Melihat task dan note | Ya | Ya | Ya |
| Membuat task, note, dan kategori | Ya | Ya | Ya |
| Mengedit task dan note | Ya | Ya | Ya |
| Mengubah status, deadline, kategori, dan reminder | Ya | Ya | Ya |
| Menghapus task atau note | Ya | Tidak | Ya |
| Mengedit atau menghapus kategori custom | Ya | Tidak | Ya |
| Membuat kode tim | Tidak | Tidak | Ya |
| Mengeluarkan anggota | Tidak | Tidak | Ya |
| Memindahkan ownership | Tidak | Tidak | Ya |
| Menghapus tim | Tidak | Tidak | Ya |

Policy utama:

```text
view/update task = anggota aktif workspace
delete task      = pembuat task atau owner workspace
manage team      = owner workspace
```

Jika pembuat keluar dari tim, data yang pernah dibuat tetap berada di workspace.
Mantan anggota kehilangan akses dan owner menjadi pihak yang dapat menghapus
data tersebut. Nama pelaku tetap tersimpan di activity log.

## Kode Bergabung Tim

- Hanya owner yang dapat membuat kode.
- Hanya satu kode yang aktif per tim.
- Membuat kode baru langsung mencabut kode lama.
- Kode berlaku selama lima menit.
- Kode dapat dipakai banyak pengguna selama belum kedaluwarsa atau dicabut.
- Kode disimpan dalam bentuk hash.
- Pengguna harus login dan memiliki email terverifikasi.
- Pengguna yang sudah menjadi anggota tidak memperoleh membership kedua.
- Percobaan memasukkan kode dibatasi dengan rate limiter.
- Claim membership dilakukan dalam transaksi dan mengunci perhitungan jumlah
  anggota agar kapasitas tidak terlewati saat ada request bersamaan.

Pesan error membedakan kode kedaluwarsa, kode tidak valid, dan tim penuh.

## Ownership Tim

Owner tidak dapat langsung keluar. Owner harus memilih anggota aktif dengan
email terverifikasi sebagai owner baru. Perpindahan dilakukan dalam satu
transaksi:

1. Memastikan penerima masih menjadi anggota aktif.
2. Mengubah role penerima menjadi owner.
3. Mengubah role owner lama menjadi member.
4. Mencatat activity log.

Owner lama baru dapat keluar setelah transaksi berhasil. Jika owner adalah
satu-satunya anggota, pilihannya adalah mengundang anggota untuk menerima
ownership atau menghapus tim.

Penghapusan tim tidak meminta password lagi. Owner harus mengetik:

```text
konfirmasi hapus tim <nama tim>
```

Nama tim harus cocok dengan nama yang tersimpan setelah spasi awal dan akhir
dihapus. Backend memvalidasi kalimat, session, CSRF, dan policy owner.

Penghapusan tim menghapus data operasional secara permanen dalam transaksi.
Activity log tetap disimpan sebagai arsip audit dengan snapshot nama workspace
dan subject yang relevan. Arsip tersebut tidak lagi tampil sebagai workspace
aktif bagi pengguna, tetapi tetap tersedia untuk kebutuhan audit sistem.

## Sticky Note

- Sticky note dapat berada di workspace personal atau tim.
- Note menyimpan isi dan warna.
- Semua anggota aktif tim dapat mengedit note tim.
- Hanya pembuat atau owner yang dapat menghapus note.
- Anggota dapat mengonversi note menjadi task.
- Konversi membuka form task dengan isi note sebagai data awal.
- Pengguna tetap harus memilih kategori dan deadline.
- Setelah berhasil, note menyimpan relasi task dan waktu konversi.
- Note tetap ada sebagai riwayat setelah dikonversi.
- Jika task hasil konversi dihapus, penanda waktu konversi tetap ada dan relasi
  task dapat menjadi null.

## Reminder

### Jadwal

- Reminder otomatis dibuat tujuh hari sebelum deadline jika waktunya belum
  lewat.
- Reminder otomatis dibuat tiga hari sebelum deadline jika waktunya belum
  lewat.
- Pengguna dapat membuat beberapa reminder manual.
- Reminder manual harus setelah waktu sekarang dan sebelum deadline.
- Jika H-7 dan H-3 sudah lewat saat task dibuat, minimal satu reminder manual
  wajib diisi.
- UI menjelaskan bahwa reminder otomatis tidak tersedia dan form tidak dapat
  disimpan sebelum reminder manual valid tersedia.
- Backend menerapkan aturan yang sama agar validasi tidak dapat dilewati.

### Penerima

- Reminder personal dikirim kepada pemilik workspace.
- Reminder tim dikirim kepada semua anggota aktif dengan email terverifikasi.
- Daftar penerima ditentukan ketika reminder akan dikirim.
- Anggota yang bergabung sebelum waktu pengiriman ikut menerima email.
- Anggota yang sudah keluar tidak menerima email berikutnya.

### Pemrosesan

```text
Laravel Scheduler setiap menit
  -> mengambil reminder jatuh tempo
  -> dispatch Queue Job
  -> membuat delivery unik per penerima
  -> Laravel Notification mengirim email
```

Job bersifat idempotent. Constraint unik pada reminder dan penerima mencegah
email ganda. Kegagalan satu penerima tidak membatalkan delivery penerima lain.
Queue dapat mencoba ulang delivery gagal dan menyimpan error terakhir.

### Perubahan Task

- Perubahan deadline menghitung ulang H-7 dan H-3 yang belum terkirim.
- Reminder manual setelah deadline baru otomatis dibatalkan dan pengguna
  diberi informasi.
- Status `selesai` menonaktifkan seluruh reminder mendatang.
- Membuka kembali task menghitung ulang reminder otomatis yang masih berada di
  masa depan dan mengaktifkan kembali reminder manual yang masih valid.
- Penghapusan task membatalkan reminder yang belum dikirim.

## Kalender

Kalender merupakan tampilan task dan tidak memiliki tabel event sendiri.

- Task diambil berdasarkan rentang `deadline_at`.
- Semua query dibatasi ke workspace aktif.
- Waktu dikonversi dari UTC ke WIB.
- Klik tanggal kosong membuka form task dengan deadline awal terisi.
- Klik task membuka detail dan activity log.
- Reminder dapat ditampilkan sebagai indikator pada task terkait.

## Activity Log

Activity log bersifat permanen dan immutable. Pengguna tidak memiliki endpoint
untuk mengedit atau menghapus log.

Log mencatat:

- Pembuat, editor, pengubah status, dan penghapus.
- Perubahan deadline dan kategori.
- Pembuatan, pembatalan, dan perubahan reminder.
- Konversi sticky note.
- Pengguna bergabung atau keluar dari tim.
- Perubahan kapasitas.
- Perpindahan ownership.
- Penghapusan tim.
- Event keamanan penting seperti login, logout, reset password, dan perubahan
  pengaturan keamanan.

Password, token autentikasi, kode tim mentah, recovery code, dan data rahasia
lain tidak pernah dimasukkan ke log.

## Autentikasi Demo Lokal

Laravel Fortify digunakan sebagai backend autentikasi tanpa mengganti UI
Inertia/Vue.

Scope demo lokal:

- Registrasi email dan password.
- Verifikasi email.
- Password reset.
- Password hashing Laravel.
- Login throttling berdasarkan email dan IP.
- Regenerasi session setelah login.
- Invalidasi session dan regenerasi token CSRF saat logout.
- Route fitur dilindungi middleware `auth` dan `verified`.
- Email autentikasi dikirim ke Mailpit di `http://localhost:8025`.
- Password minimal delapan karakter untuk kemudahan demo.

Mailpit hanya alat development dan bukan layanan email produksi.

## Catatan Wajib Sebelum Staging atau Produksi

Pekerjaan berikut sengaja ditunda, tetapi wajib dilakukan sebelum aplikasi
dianggap siap produksi:

- Aktifkan 2FA TOTP dengan recovery code.
- Wajibkan password minimal dua belas karakter.
- Tolak password yang ditemukan pada basis data kebocoran.
- Gunakan HTTPS.
- Aktifkan cookie `Secure`, `HttpOnly`, dan `SameSite` yang sesuai.
- Ganti Mailpit dengan SMTP atau penyedia email sebenarnya.
- Cabut session perangkat lain setelah perubahan atau reset password.
- Gunakan response autentikasi generik yang tidak membocorkan keberadaan email.
- Uji rate limiting login, reset password, dan pengiriman verifikasi.
- Jalankan queue worker dan scheduler dengan process supervisor.
- Siapkan backup database, monitoring, dan penanganan failed jobs.

## Error Handling dan Konsistensi

Operasi berikut wajib memakai transaksi:

- Membuat task, reminder, dan activity log.
- Mengklaim kode tim.
- Memindahkan ownership.
- Mengubah deadline dan menghitung ulang reminder.
- Menghapus tim.

Aturan error:

- Data workspace yang tidak dapat diakses diperlakukan tidak ditemukan.
- Pengguna yang bukan anggota aktif ditolak oleh policy.
- Validation error dikembalikan ke field Inertia terkait.
- Kode kedaluwarsa, kode salah, dan tim penuh memiliki pesan berbeda.
- Email gagal tidak membatalkan pembuatan atau perubahan task.
- Operasi domain penting hanya berhasil jika activity log ikut tersimpan.

## Index dan Constraint

Index minimum:

- `workspace_members(workspace_id, user_id)` unik.
- `workspace_members(user_id, workspace_id)`.
- `todos(workspace_id, status, deadline_at)`.
- `todos(workspace_id, category_id)`.
- `categories(workspace_id, name)` unik untuk kategori custom.
- `team_invites(workspace_id, expires_at, revoked_at)`.
- `todo_reminders(scheduled_at, active)`.
- `reminder_deliveries(reminder_id, user_id)` unik.
- `activity_logs(workspace_id, created_at)`.

Foreign key menggunakan perilaku delete yang eksplisit. Activity log tidak
bergantung pada foreign key subject yang dapat dihapus, sedangkan penghapusan
workspace mengubah `activity_logs.workspace_id` menjadi null tanpa menghapus
log.

## Strategi Pengujian

Feature dan unit test mencakup:

- Pembuatan workspace personal setelah verifikasi email.
- Pemisahan data antar-workspace.
- Pengguna dapat berada di banyak tim.
- Policy pembuat, member, dan owner.
- Pembuat yang keluar tidak menghapus data tim.
- Perpindahan ownership dan larangan owner keluar sebelum transfer.
- Penghapusan tim dengan kalimat konfirmasi.
- Kode tim aktif selama lima menit dan dapat dipakai banyak pengguna.
- Kode baru mencabut kode lama.
- Batas kapasitas lima dan sepuluh termasuk owner.
- Race condition saat beberapa pengguna mengklaim kursi terakhir.
- CRUD task dan ketiga status.
- Membuka kembali task selesai.
- Larangan menghapus kategori yang masih digunakan.
- Konversi sticky note menjadi task.
- Perhitungan H-7, H-3, dan beberapa reminder manual.
- Kewajiban reminder manual ketika jadwal otomatis sudah lewat.
- Deadline minimum lima menit.
- Idempotensi delivery dan retry email gagal.
- Penghentian serta pengaktifan ulang reminder.
- Verifikasi email, reset password, session, dan login throttling.
- Penghapusan permanen subject tanpa menghapus activity log.

## Urutan Implementasi yang Direkomendasikan

1. Autentikasi Fortify dan verifikasi email melalui Mailpit.
2. Workspace personal, tim, membership, kapasitas, dan policy.
3. Kategori dan CRUD task.
4. Activity log.
5. Sticky note dan konversi.
6. Scheduler, queue, reminder, delivery, dan email.
7. Query kalender.
8. Hardening, pengujian integrasi, dan persiapan staging.
