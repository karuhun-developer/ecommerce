<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Flush cache
        Cache::flush();

        $this->call([
            PermissionSeeder::class,
            UserSeeder::class,
            SuperadminMenuSeeder::class,
        ]);
    }
}
