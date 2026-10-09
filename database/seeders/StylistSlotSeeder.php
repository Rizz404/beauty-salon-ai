<?php

namespace Database\Seeders;

use App\Models\Salon;
use App\Models\Stylist;
use App\Models\StylistSlot;
use App\Modules\Appointment\Support\StylistAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class StylistSlotSeeder extends Seeder
{
    public function run(): void
    {
        $salon = Salon::where('name', SalonSeeder::NAME)->firstOrFail();

        foreach ($salon->stylists as $stylist) {
            $this->seedSchedule($salon, $stylist);
        }
    }

    /**
     * Opens every 30-minute block of the opening hours for the booking window.
     * A deterministic spread of already-taken blocks, in runs of one to four,
     * makes some days busy and some times unavailable.
     */
    private function seedSchedule(Salon $salon, Stylist $stylist): void
    {
        $today = CarbonImmutable::now($salon->timezone)->startOfDay();
        $open = CarbonImmutable::createFromFormat('H:i', substr((string) $salon->opening_time, 0, 5));
        $close = CarbonImmutable::createFromFormat('H:i', substr((string) $salon->closing_time, 0, 5));
        $rows = [];

        for ($day = 0; $day <= StylistAvailability::MAX_ADVANCE_DAYS; $day++) {
            $date = $today->addDays($day)->toDateString();
            $takenUntil = null;

            for ($block = $open; $block->lessThan($close); $block = $block->addMinutes(30)) {
                $time = $block->format('H:i');
                $roll = crc32($stylist->slug.$date.$time) % 100;

                if ($takenUntil === null && $roll < 18) {
                    $takenUntil = $block->addMinutes(30 * (1 + $roll % 4));
                }

                $booked = $takenUntil !== null && $block->lessThan($takenUntil);

                if ($takenUntil !== null && ! $block->lessThan($takenUntil)) {
                    $takenUntil = null;
                }

                $rows[] = [
                    'stylist_id' => $stylist->id,
                    'slot_date' => $date,
                    'time_slot' => $time,
                    'is_booked' => $booked,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            StylistSlot::upsert($chunk, ['stylist_id', 'slot_date', 'time_slot'], ['is_booked']);
        }
    }
}
