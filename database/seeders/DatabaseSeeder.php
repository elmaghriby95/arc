<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        Storage::disk('local')->deleteDirectory('documents/seed');

        $this->call([
            ArchiveSeeder::class,
        ]);

        $this->command?->info('تمت تهيئة قاعدة البيانات بنجاح.');
        $this->command?->info('حساب المدير: admin@arc.local / password');
    }
}
