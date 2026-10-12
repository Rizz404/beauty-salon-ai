<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment('local', 'testing')) {
            $this->call([
                UserSeeder::class,
                SalonDemoSeeder::class,
                SalonUserSeeder::class,
            ]);

            return;
        }

        $this->command?->warn('Demo data was skipped. To load the sample salon explicitly, run: php artisan db:seed --class=SalonDemoSeeder --force');
    }
}
