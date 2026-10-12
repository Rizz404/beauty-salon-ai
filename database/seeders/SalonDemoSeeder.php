<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalonDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->call([
                SalonSeeder::class,
                ServiceSeeder::class,
                StylistSeeder::class,
                StylistImageSeeder::class,
                StylistSlotSeeder::class,
                SalonKnowledgeItemSeeder::class,
            ]);
        });

        $this->command?->info('Sample salon data is ready. No login accounts were created.');
    }
}
