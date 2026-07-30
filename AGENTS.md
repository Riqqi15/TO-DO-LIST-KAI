# AI Collaboration Guide

Dokumen ini berlaku untuk seluruh repository.

## Wajib Dibaca Sebelum Mengubah Kode

1. `docs/ai-handoff/BACKEND_PROGRESS.md`
2. `docs/superpowers/specs/2026-07-29-todo-backend-design.md`
3. `docs/superpowers/specs/2026-07-29-todo-clean-architecture-design.md`

Instruksi terbaru pengguna selalu mengalahkan dokumentasi lama. Jika kode dan
dokumentasi berbeda, verifikasi kondisi repository lalu jelaskan perbedaannya;
jangan diam-diam menganggap fitur yang baru direncanakan sudah selesai.

## Batas Arsitektur

- Aplikasi adalah satu project Laravel + Inertia + Vue, bukan frontend dan REST
  API yang terpisah.
- Backend memakai modular monolith dengan Domain Actions, Form Requests,
  Policies, Eloquent Models, Jobs, dan Notifications.
- Kerjakan backend terlebih dahulu sesuai urutan pada handoff.
- Frontend tidak memakai Bootstrap. Komponen UI nantinya memakai shadcn-vue.
- Jangan menambahkan priority, assignee task tim, event kalender terpisah,
  microservice, CQRS, atau repository abstraction pada scope awal.
- Jangan menyimpan atau menulis ulang isi `.env`, password, token, atau secret
  ke dokumentasi dan commit.

## Cara Melanjutkan Pekerjaan

- Mulai dengan `git status -sb`, commit terbaru, route list, migration status,
  dan kondisi Docker; runtime dapat berubah sejak handoff ditulis.
- Ikuti desain yang sudah disetujui dan implementation plan yang nantinya dibuat.
- Implementasikan satu fase backend dalam satu waktu dan uji sesuai risikonya.
- Setelah satu fase selesai, perbarui bagian status, bukti verifikasi, dan next
  action pada `docs/ai-handoff/BACKEND_PROGRESS.md`.
- Bedakan jelas status `selesai`, `baru didesain`, `belum dimulai`, dan
  `ditunda sebelum produksi`.
