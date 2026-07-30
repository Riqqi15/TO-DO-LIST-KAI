# To Do List KAI

Aplikasi Laravel 12 + Inertia + Vue untuk task personal dan brainstorming tim.
Backend memakai modular monolith dengan Domain Actions, Form Requests, Policies,
Eloquent, queue job, notification, dan activity log. Bootstrap tidak digunakan;
UI final diarahkan ke shadcn-vue.

## Fitur backend

- Autentikasi email/password, verifikasi email, reset password, dan throttling.
- Workspace personal otomatis dan banyak workspace tim.
- Kode tim reusable selama 5 menit, kapasitas 5 atau 10 termasuk owner.
- Kategori sistem/custom dan task kolaboratif tanpa assignee/priority.
- Status Belum Dikerjakan, Sedang Dikerjakan, dan Selesai.
- Deadline wajib WIB, reminder otomatis H-7/H-3 dan reminder manual fleksibel.
- Email reminder untuk semua anggota tim terverifikasi dengan delivery tracking.
- Sticky note kolaboratif yang dapat dijadikan task.
- Activity log permanen dan kalender berbasis deadline task.

## Menjalankan lokal

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
docker compose up -d
php artisan migrate
npm install
npm run build
php artisan serve
```

Pada terminal terpisah jalankan proses background:

```powershell
php artisan queue:work --tries=3
php artisan schedule:work
```

Endpoint lokal:

- Aplikasi: `http://127.0.0.1:8000`
- phpMyAdmin: `http://127.0.0.1:8080`
- Mailpit: `http://127.0.0.1:8025`

Daftar melalui aplikasi, lalu buka email verifikasi di Mailpit. Workspace
personal dibuat idempoten setelah email berhasil diverifikasi.

## Verifikasi

```powershell
php artisan test
php artisan route:list --except-vendor
php artisan migrate:status
php artisan schedule:list
npm run build
```

Dokumentasi lanjutan:

- `docs/ai-handoff/BACKEND_PROGRESS.md`
- `docs/database/ERD.md`
- `docs/database/VISUALIZATION.md`
- `docs/superpowers/specs/2026-07-29-todo-backend-design.md`

Backend ini ditujukan untuk demo lokal. Checklist keamanan staging/produksi
pada dokumen handoff harus diselesaikan sebelum aplikasi dipublikasikan.
