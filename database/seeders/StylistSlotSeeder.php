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
    /**
     * @var array<string, array{start: string, end: string, break: string, day_off: int}>
     */
    private const SCHEDULES = [
        'rina-pratama' => ['start' => '10:00', 'end' => '19:00', 'break' => '13:00', 'day_off' => 1],
        'kenji-mori' => ['start' => '09:00', 'end' => '18:00', 'break' => '12:00', 'day_off' => 2],
        'sari-wulandari' => ['start' => '10:00', 'end' => '19:00', 'break' => '13:00', 'day_off' => 3],
        'maya-lestari' => ['start' => '09:00', 'end' => '18:00', 'break' => '12:00', 'day_off' => 4],
        'dewi-anggraini' => ['start' => '09:00', 'end' => '17:00', 'break' => '12:00', 'day_off' => 1],
        'ayu-ratna' => ['start' => '11:00', 'end' => '20:00', 'break' => '14:00', 'day_off' => 2],
    ];

    public function run(): void
    {
        $salon = Salon::where('name', SalonSeeder::NAME)->firstOrFail();

        foreach ($salon->stylists()->where('is_active', true)->get() as $stylist) {
            $this->seedSchedule($salon, $stylist);
        }
    }

    /**
     * Adds unbooked blocks inside each sample stylist's shift, excluding their
     * lunch break and weekly day off. Obsolete free blocks are removed inside
     * the booking window; existing bookings are never overwritten.
     */
    private function seedSchedule(Salon $salon, Stylist $stylist): void
    {
        $today = CarbonImmutable::now($salon->timezone)->startOfDay();
        $schedule = self::SCHEDULES[$stylist->slug] ?? [
            'start' => substr((string) $salon->opening_time, 0, 5),
            'end' => substr((string) $salon->closing_time, 0, 5),
            'break' => null,
            'day_off' => null,
        ];
        $rows = [];

        for ($day = 0; $day <= StylistAvailability::MAX_ADVANCE_DAYS; $day++) {
            $date = $today->addDays($day);

            if ((int) $date->format('N') === $schedule['day_off']) {
                continue;
            }

            $open = $date->setTimeFromTimeString($schedule['start']);
            $close = $date->setTimeFromTimeString($schedule['end']);
            $salonOpen = $date->setTimeFromTimeString($salon->opening_time);
            $salonClose = $date->setTimeFromTimeString($salon->closing_time);
            $open = $open->greaterThan($salonOpen) ? $open : $salonOpen;
            $close = $close->lessThan($salonClose) ? $close : $salonClose;
            $breakStart = $schedule['break'] !== null ? $date->setTimeFromTimeString($schedule['break']) : null;

            for ($block = $open; $block->lessThan($close); $block = $block->addMinutes(30)) {
                if ($block->addMinutes(30)->greaterThan($close)) {
                    break;
                }

                if ($breakStart !== null && $block->lessThan($breakStart->addHour()) && $block->addMinutes(30)->greaterThan($breakStart)) {
                    continue;
                }

                $rows[] = [
                    'stylist_id' => $stylist->id,
                    'slot_date' => $date->toDateString(),
                    'time_slot' => $block->format('H:i'),
                    'is_booked' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        $availableBlocks = array_fill_keys(array_map(
            static fn (array $row): string => $row['slot_date'].' '.$row['time_slot'],
            $rows
        ), true);

        $obsoleteSlotIds = StylistSlot::where('stylist_id', $stylist->id)
            ->whereBetween('slot_date', [$today->toDateString(), $today->addDays(StylistAvailability::MAX_ADVANCE_DAYS)->toDateString()])
            ->where('is_booked', false)
            ->get(['id', 'slot_date', 'time_slot'])
            ->reject(fn (StylistSlot $slot): bool => isset($availableBlocks[$slot->slot_date->toDateString().' '.$slot->time_slot]))
            ->modelKeys();

        foreach (array_chunk($obsoleteSlotIds, 200) as $slotIds) {
            StylistSlot::whereIn('id', $slotIds)->delete();
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            StylistSlot::insertOrIgnore($chunk);
        }
    }
}
