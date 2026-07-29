# To Do List Clean Architecture Design

## Tujuan

Menjaga project Laravel, Inertia, dan Vue tetap mudah dikembangkan ketika fitur
To Do List bertambah, tanpa memperkenalkan lapisan abstraksi yang belum
dibutuhkan.

## Keputusan Arsitektur

Project menggunakan clean architecture ringan dengan organisasi feature-first.
Laravel tetap memakai konvensi framework untuk route dan adapter HTTP, sedangkan
aturan bisnis To Do dipusatkan di `app/Domain/Todo`. Frontend mempertahankan
Inertia Page sebagai entry point tipis dan menempatkan implementasi fitur di
`resources/js/features/todo`.

Struktur empat lapis yang ketat tidak digunakan karena aplikasi belum memiliki
kompleksitas yang membenarkan repository interface, entity murni, mapper, atau
adapter persistence terpisah.

## Struktur Backend

```text
app/
├── Domain/
│   └── Todo/
│       ├── Actions/
│       ├── Enums/
│       └── Models/
├── Http/
│   ├── Controllers/
│   │   └── Todo/
│   └── Requests/
│       └── Todo/
└── Notifications/
    └── Todo/
```

Tanggung jawab setiap bagian:

- `Domain/Todo/Actions`: satu use case per class, seperti membuat, memperbarui,
  menghapus, atau mengirim reminder tugas.
- `Domain/Todo/Enums`: nilai status dan prioritas yang berlaku di seluruh
  backend.
- `Domain/Todo/Models`: model Eloquent untuk fitur To Do.
- `Http/Controllers/Todo`: menerjemahkan request web menjadi pemanggilan action
  dan response Inertia.
- `Http/Requests/Todo`: validasi serta otorisasi input.
- `Notifications/Todo`: email reminder yang bergantung pada fasilitas Laravel.

Folder hanya dibuat ketika memiliki implementasi nyata. Struktur tidak diisi
dengan `.gitkeep`, repository interface, DTO, atau service generik.

## Struktur Frontend

```text
resources/js/
├── app.js
├── Pages/
│   └── Todo/
│       └── Index.vue
├── features/
│   └── todo/
│       ├── components/
│       ├── composables/
│       ├── constants/
│       └── utils/
├── components/
│   ├── ui/
│   └── shared/
├── layouts/
└── lib/
```

Tanggung jawab setiap bagian:

- `Pages/Todo/Index.vue`: entry Inertia yang mengatur `Head`, menerima props,
  dan menyusun komponen fitur. File ini tidak memuat detail UI yang besar.
- `features/todo/components`: komponen yang hanya dipakai fitur To Do.
- `features/todo/composables`: state dan perilaku UI yang perlu dipakai oleh
  lebih dari satu komponen fitur.
- `features/todo/constants`: opsi status dan prioritas frontend.
- `features/todo/utils`: helper murni yang khusus untuk fitur To Do.
- `components/ui`: source komponen shadcn-vue.
- `components/shared`: komponen aplikasi yang dipakai lintas fitur.
- `layouts`: kerangka halaman aplikasi.
- `lib`: utility global, termasuk helper `cn`.

Folder `presentation`, `services`, dan state store global tidak dibuat. Inertia
menjadi jalur komunikasi UI dan Laravel sehingga tidak diperlukan REST client
terpisah.

## Alur Data

```text
Route -> Controller -> Action -> Model
                    |
                    v
            Inertia response
                    |
                    v
          Page -> Feature components -> shadcn-vue components
```

Mutasi form nantinya menggunakan `useForm` dari Inertia. Controller hanya
mengoordinasikan request dan response; aturan perubahan data berada di action.

## Penanganan Error

- Kesalahan validasi berasal dari Form Request Laravel dan ditampilkan pada
  field terkait melalui error bag Inertia.
- Kegagalan operasi domain menghasilkan exception yang dapat dipetakan menjadi
  pesan pengguna oleh adapter HTTP.
- UI menyediakan state kosong, state proses, dan konfirmasi sebelum penghapusan.
- Reminder email mencatat waktu pengiriman agar tugas yang sama tidak dikirim
  berulang kali.

## Batas Scope Perapian

Tahap perapian hanya menetapkan batas folder dan memindahkan kode yang sudah
ada. Tahap ini tidak membuat CRUD, migration Todo, autentikasi, pengiriman
email, atau UI final. Folder untuk bagian tersebut baru dibuat ketika fiturnya
diimplementasikan.

## Verifikasi

- Resolver Inertia tetap menemukan `Pages/Todo/Index.vue`.
- Alias `@` tetap mengarah ke `resources/js`.
- Build Vite berhasil setelah setiap pemindahan frontend.
- Route Laravel yang ada tetap dapat dimuat.
- Tidak ada import yang menunjuk ke lokasi lama.

