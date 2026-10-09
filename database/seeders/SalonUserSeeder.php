<?php

namespace Database\Seeders;

use App\Models\Salon;
use App\Models\User;
use Illuminate\Database\Seeder;

class SalonUserSeeder extends Seeder
{
    public function run(): void
    {
        $salon = Salon::where('name', SalonSeeder::NAME)->firstOrFail();
        $owner = User::where('email', UserSeeder::OWNER_EMAIL)->firstOrFail();

        $salon->users()->syncWithoutDetaching([
            $owner->id => ['role' => 'owner', 'status' => 'active'],
        ]);
    }
}
