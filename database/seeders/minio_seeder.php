<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class minio_seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Log::info(database_path('seeders\supas.xls'));
        Storage::disk('minio')->put(
            'supas.xls',
            File::get(database_path('seeders\supas.xls'))
        );
    }
}
