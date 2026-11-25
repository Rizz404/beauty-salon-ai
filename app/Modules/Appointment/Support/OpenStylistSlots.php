<?php

namespace App\Modules\Appointment\Support;

use App\Models\Stylist;
use App\Models\StylistSlot;
use Carbon\CarbonImmutable;

/**
 * Keeps the booking window open: adds the missing days up to
 * StylistAvailability::MAX_ADVANCE_DAYS for one stylist. Each new day repeats
 * the blocks of the same weekday one week earlier, so shifts, breaks and the
 * weekly day off carry forward. Existing blocks, booked or not, are never
 * changed.
 */
final class OpenStylistSlots
{
    /**
     * @return int The number of blocks added.
     */
    public function handle(Stylist $stylist): int
    {
        $latestDate = StylistSlot::query()->where('stylist_id', $stylist->id)->max('slot_date');

        if ($latestDate === null) {
            return 0;
        }

        $latest = CarbonImmutable::parse($latestDate)->startOfDay();
        $today = CarbonImmutable::now($stylist->salon->timezone)->startOfDay();
        $horizon = $today->addDays(StylistAvailability::MAX_ADVANCE_DAYS);

        $blocksByDate = StylistSlot::query()
            ->where('stylist_id', $stylist->id)
            ->whereDate('slot_date', '>', $latest->subDays(7)->toDateString())
            ->orderBy('time_slot')
            ->get(['slot_date', 'time_slot'])
            ->groupBy(fn (StylistSlot $slot) => $slot->slot_date->toDateString())
            ->map(fn ($slots) => $slots->pluck('time_slot')->all())
            ->all();

        $rows = [];
        for ($date = $latest->addDay(); $date->lessThanOrEqualTo($horizon); $date = $date->addDay()) {
            $blocks = $blocksByDate[$date->subDays(7)->toDateString()] ?? [];
            $blocksByDate[$date->toDateString()] = $blocks;

            if ($date->lessThan($today)) {
                continue;
            }

            foreach ($blocks as $block) {
                $rows[] = [
                    'stylist_id' => $stylist->id,
                    'slot_date' => $date->toDateString(),
                    'time_slot' => $block,
                    'is_booked' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        $inserted = 0;
        foreach (array_chunk($rows, 200) as $chunk) {
            $inserted += StylistSlot::query()->insertOrIgnore($chunk);
        }

        return $inserted;
    }
}
