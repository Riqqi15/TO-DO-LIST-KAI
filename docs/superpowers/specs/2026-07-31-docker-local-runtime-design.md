# Docker Local Runtime Design

**Tanggal:** 31 Juli 2026  
**Status:** Disetujui untuk implementasi  
**Target:** Demo dan testing backend lokal

## Tujuan

Satu perintah berikut menyalakan seluruh runtime backend:

```powershell
docker compose up -d --build
```

Runtime mencakup Laravel web, migration, queue worker, scheduler, MySQL,
phpMyAdmin, dan Mailpit. Pengguna tidak perlu menyalakan proses PHP Windows
secara terpisah.

## Arsitektur

Tambahkan satu image PHP CLI lokal melalui `Dockerfile`. Image memasang extension
yang dibutuhkan Laravel, terutama `pdo_mysql`, dan menyediakan Composer.

Image yang sama digunakan oleh empat service:

- `migrate`: menjalankan `php artisan migrate --force` setelah MySQL sehat.
- `app`: menjalankan Laravel di `0.0.0.0:8000`.
- `queue`: menjalankan worker database queue dengan retry terbatas.
- `scheduler`: menjalankan Laravel scheduler secara kontinu.

Service `app`, `queue`, dan `scheduler` baru dimulai setelah `migrate` selesai
dengan sukses. MySQL, phpMyAdmin, dan Mailpit tetap memakai service yang sudah
ada.

## Source dan Dependency

Repository di-bind-mount ke `/var/www/html` agar perubahan backend lokal langsung
terbaca tanpa rebuild image. Folder `vendor` dan hasil build frontend yang sudah
ada digunakan dari workspace.

Image tidak menyalin `.env`. Compose membaca `.env` lokal saat runtime, lalu
menimpa alamat antarkontainer berikut:

- `DB_HOST=mysql`
- `DB_PORT=3306`
- `MAIL_HOST=mailpit`
- `MAIL_PORT=1025`

Port host tetap:

- Laravel: `8000`
- MySQL: `3307`
- phpMyAdmin: `8080`
- Mailpit UI: `8025`
- Mailpit SMTP: `1025`

## Frontend

Vite tidak ditambahkan sebagai container pada fase ini. Laravel menyajikan aset
dari `public/build`. Setelah mengubah Vue atau CSS, jalankan `npm run build` di
host. Batas ini menjaga runtime Docker ringan dan fokus pada testing backend.

## Operasional

Sebelum pertama kali menyalakan stack baru, hentikan proses PHP Windows lama
untuk web, queue, dan scheduler agar port tidak bentrok dan reminder tidak
diproses ganda.

Compose menerapkan restart policy pada service jangka panjang. Service migration
bersifat one-shot dan tidak di-restart setelah berhasil.

Perintah operasional:

```powershell
docker compose up -d --build
docker compose ps
docker compose logs app queue scheduler
docker compose down
```

`docker compose down` tidak menghapus volume MySQL. Penghapusan data hanya
terjadi bila volume dihapus secara eksplisit, dan itu tidak termasuk scope.

## Penanganan Error

- MySQL harus lulus healthcheck sebelum migration.
- Kegagalan migration mencegah app, queue, dan scheduler dimulai.
- Queue worker memakai retry dan failed-job table Laravel.
- Scheduler dan queue memiliki service terpisah sehingga kegagalan salah satu
  dapat diperiksa melalui log service masing-masing.

## Verifikasi

Implementasi dianggap berhasil bila:

1. `docker compose config` valid.
2. `docker compose up -d --build` selesai.
3. Semua service jangka panjang berstatus running dan MySQL healthy.
4. Service `migrate` selesai dengan exit code `0`.
5. `GET /login` mengembalikan HTTP `200`.
6. Scheduler tercantum setiap menit.
7. Queue worker memproses job tanpa failed job baru.
8. Mailpit menerima email reminder yang jatuh tempo.

## Di Luar Scope

- Vite hot reload di Docker.
- Image production, Nginx, HTTPS, process supervisor, dan deployment cloud.
- SMTP Gmail atau provider email produksi.
- Perubahan kontrak fitur Todo dan reminder.
