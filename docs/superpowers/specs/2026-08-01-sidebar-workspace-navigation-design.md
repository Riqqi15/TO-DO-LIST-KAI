# Sidebar Workspace Navigation Design

## Tujuan

Menyederhanakan navigasi dashboard dengan memindahkan pengelolaan kategori dan workspace tim ke sidebar. Pengguna dapat berpindah workspace, membuat atau bergabung dengan tim, dan mengelola kategori tanpa membuka halaman Pengaturan.

Perubahan hanya mencakup frontend. Route, controller, action, model, policy, validasi, dan kontrak data backend tetap dipertahankan.

## Keputusan Produk

- Dropdown workspace diganti dengan daftar workspace langsung.
- Ruang Pribadi tampil lebih dahulu; workspace tim berada tepat di bawahnya.
- Pengguna dapat membuat dan bergabung dengan tim dari sidebar.
- Kategori menjadi bagian yang dapat dibuka atau ditutup di sidebar.
- Menu dan halaman Pengaturan dihapus.
- Halaman Tugas hanya menyediakan mode **Board** dan **Daftar**.
- Istilah **Kanban** diganti menjadi **Board** pada UI dan state frontend.
- Kalender hanya dibuka melalui menu Kalender di sidebar.

## Struktur Sidebar

### Workspace aktif

Bagian Workspace aktif menampilkan daftar langsung tanpa dropdown:

1. Ruang Pribadi tampil sebagai item pertama.
2. Workspace Tim tampil tepat di bawah Ruang Pribadi.
3. Workspace aktif memiliki latar, ikon, dan label status yang jelas.
4. Klik item workspace memakai navigasi Inertia yang sudah ada dan menyegarkan data workspace aktif.

Header Workspace Tim menyediakan tindakan **Buat** dan **Gabung**. Kedua tindakan membuka dialog agar sidebar tetap ringkas. Setiap workspace tim memiliki menu tindakan kontekstual:

- owner dapat membuat kode undangan, mengubah kapasitas, dan menghapus tim;
- anggota dapat keluar dari tim sesuai policy backend;
- tindakan destruktif tetap meminta konfirmasi.

Backend saat ini hanya menyediakan jumlah anggota, bukan identitas anggota. UI tidak menambahkan pengelolaan anggota atau transfer kepemilikan yang membutuhkan ID anggota.

### Kategori

Kategori berada setelah Workspace Tim dan memakai pola buka/tutup. Daftar menampilkan kategori sistem dan kategori custom. Tombol plus membuka dialog tambah kategori. Menu tindakan pada kategori custom menyediakan ubah nama dan hapus; kategori sistem tetap read-only.

Validasi dan pesan domain berasal dari backend. Dialog tetap terbuka saat validasi gagal dan menampilkan pesan di dekat field terkait.

### Navigasi utama

Navigasi utama hanya memuat:

- Tugas
- Kalender
- Catatan
- Aktivitas

Menu Pengaturan dihapus. Pada mobile, struktur yang sama tampil dalam drawer sidebar. Dialog menyesuaikan lebar viewport dan mempertahankan target sentuh yang nyaman.

## Area Utama

Halaman Tugas hanya menyediakan dua mode:

- **Board** mengelompokkan tugas berdasarkan status.
- **Daftar** menyajikan tugas dalam daftar terstruktur.

Pilihan Kalender dihapus dari pengalih mode Tugas. Menu Kalender di sidebar membuka kalender sebagai konteks halaman tersendiri. Perubahan ini menghilangkan dua jalur navigasi untuk fungsi yang sama.

State frontend mengganti nilai `kanban` menjadi `board`. Komponen papan tugas dan nilai status backend tidak berubah.

## Dialog dan Umpan Balik

Dialog sidebar mencakup:

- tambah atau ubah kategori;
- buat workspace tim;
- gabung tim dengan kode;
- undangan dan kapasitas tim;
- konfirmasi keluar atau menghapus tim.

Form memakai `useForm` Inertia dan route yang sudah tersedia. Tombol submit menampilkan progress dan nonaktif selama request. Flash berhasil tetap tampil melalui sistem toast aplikasi. Error validasi tampil pada field terkait; error domain umum mengikuti pesan backend.

## Sistem Visual dan Aksesibilitas

Komponen memakai source shadcn-vue dan ikon Lucide yang sudah terpasang. Sidebar mempertahankan tipografi, warna, dan kepadatan dashboard saat ini.

- Tombol ikon memiliki tooltip dan accessible name.
- Item aktif tidak hanya dibedakan melalui warna.
- Nama workspace dan kategori panjang dipotong tanpa memperlebar sidebar.
- Dialog, menu, serta navigasi dapat digunakan dengan keyboard.
- Focus ring tetap terlihat.
- Tindakan destruktif memakai Alert Dialog.
- Semua kontrol mobile mempertahankan area sentuh minimal 40 piksel.

## Arsitektur Frontend

Fungsi kategori dan workspace tim dipindahkan dari `WorkspaceSettings` ke komponen domain sidebar. `AppLayout` meneruskan data kategori, workspace, undangan, dan pengguna ke sidebar desktop serta mobile. `TodoWorkspace` tetap menjadi pengatur konteks area utama.

Komponen dan kondisi render halaman Pengaturan dihapus setelah seluruh fiturnya tersedia di sidebar. Tidak ada endpoint baru, store global, atau perubahan payload backend.

## Verifikasi

1. Jalankan build produksi Vite.
2. Jalankan seluruh test Laravel.
3. Pastikan tidak ada file backend yang berubah.
4. Uji memilih Ruang Pribadi dan workspace tim.
5. Uji tambah, ubah, dan hapus kategori.
6. Uji membuat tim, bergabung dengan kode, undangan, kapasitas, keluar, dan hapus tim sesuai peran.
7. Pastikan pengalih Tugas hanya menampilkan Board dan Daftar.
8. Pastikan Kalender hanya tersedia melalui sidebar.
9. Periksa dialog, fokus keyboard, error konsol, dan overflow pada desktop serta mobile.

## Batas Scope

Implementasi tidak mengubah backend, tidak menambahkan payload anggota, dan tidak memperluas fitur tim di luar data yang tersedia. Pengelolaan anggota individual serta transfer ownership tetap menunggu kontrak data backend yang memuat identitas anggota.
