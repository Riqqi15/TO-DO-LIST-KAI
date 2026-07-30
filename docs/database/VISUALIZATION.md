# Cara Memvisualisasikan Database

## Pilihan paling mudah: phpMyAdmin

1. Dari root project, jalankan `docker compose up -d`.
2. Jalankan `php artisan migrate` bila migration belum berstatus `Ran`.
3. Buka `http://127.0.0.1:8080`.
4. Login menggunakan nilai `DB_USERNAME` dan `DB_PASSWORD` dari file `.env`.
   Jangan menggunakan contoh dokumentasi sebagai password produksi.
5. Pilih database sesuai `DB_DATABASE` pada `.env` (default contoh:
   `todo_list_kai`).
6. Untuk tabel individual, buka tab **Structure** lalu **Relation view**.
7. Untuk diagram seluruh database, buka menu **More** lalu **Designer**.
   Tambahkan tabel yang ingin ditampilkan dan atur posisinya. Garis relasi
   berasal dari foreign key migration.

phpMyAdmin hanya antarmuka visual. Database tetap berada pada container MySQL
dan Laravel dari host mengaksesnya melalui port `DB_PORT` (default contoh
`3307`). Antar-container menggunakan host `mysql` dan port `3306`.

## Alternatif: DBeaver

1. Buat koneksi baru bertipe MySQL.
2. Host: `127.0.0.1`.
3. Port, database, username, dan password: ambil dari `.env`.
4. Setelah tersambung, klik kanan schema, pilih **View Diagram**.
5. Gunakan refresh setelah migration baru agar tabel dan foreign key terbaru
   muncul.

## Diagram tanpa menjalankan database

Buka `docs/database/ERD.md` di viewer Markdown yang mendukung Mermaid. Diagram
tersebut cocok untuk dokumentasi dan handoff, sedangkan phpMyAdmin/DBeaver lebih
cocok untuk melihat data aktual.

## Pemeriksaan cepat

```powershell
php artisan migrate:status
php artisan schedule:list
php artisan queue:work --tries=3
php artisan schedule:work
```

Untuk demo email, buka Mailpit di `http://127.0.0.1:8025`. Queue worker dan
scheduler harus sama-sama berjalan agar reminder jatuh tempo diproses.
