<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('categories')->insert([
            ['name' => 'NPD ACC', 'slug' => 'npd-acc', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'G63', 'slug' => 'g63', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'SPK', 'slug' => 'spk', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Laporan Pendahuluan', 'slug' => 'laporan-pendahuluan', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Laporan Draft', 'slug' => 'laporan-draft', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Laporan Akhir', 'slug' => 'laporan-akhir', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'KAK', 'slug' => 'kak', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'RAB, AHS', 'slug' => 'rab-ahs', 'is_system' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Buat akun demo melalui form
        // register agar alur verifikasi email dan provisioning workspace teruji.
    }
}
