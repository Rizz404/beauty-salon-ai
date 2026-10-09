<?php

namespace App\Modules\Appointment\Support;

use App\Models\Salon;
use App\Models\Service;
use App\Models\Stylist;
use App\Models\StylistSlot;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * The single place where stylist availability and appointment quotes are
 * computed. Both the AI Beautician tools and the guest-facing booking wizard
 * go through here, so they can never disagree.
 *
 * A stylist's day is cut into 30-minute blocks (stylist_slots). A service
 * takes as many consecutive blocks as its duration needs, so a start time is
 * only offered when every block it covers is still free.
 */
class StylistAvailability
{
    /**
     * How far ahead an appointment can be requested.
     */
    public const MAX_ADVANCE_DAYS = 60;

    /**
     * Whether the stylist's specialty covers the service.
     */
    public function performs(Stylist $stylist, Service $service): bool
    {
        return $stylist->salon_id === $service->salon_id && $stylist->specialty === $service->category;
    }

    /**
     * The stylists who can perform the service, in the salon's own order.
     *
     * @return Collection<int, Stylist>
     */
    public function stylistsFor(Service $service): Collection
    {
        return Stylist::query()
            ->where('salon_id', $service->salon_id)
            ->where('is_active', true)
            ->where('specialty', $service->category)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * The block start times the salon takes appointments for, earliest first.
     *
     * @return list<string>
     */
    public function startTimesFor(Salon $salon): array
    {
        return StylistSlot::query()
            ->whereIn('stylist_id', $salon->stylists()->where('is_active', true)->select('id'))
            ->distinct()
            ->orderBy('time_slot')
            ->pluck('time_slot')
            ->all();
    }

    /**
     * "11:00–12:30" for an appointment of 90 minutes that starts at 11:00.
     */
    public static function range(string $start, int $minutes): string
    {
        $from = CarbonImmutable::createFromFormat('H:i', $start);

        return $from->format('H:i').'–'.$from->addMinutes($minutes)->format('H:i');
    }

    /**
     * The block start times a service starting at $start would occupy.
     *
     * @return list<string>
     */
    public static function blocks(string $start, int $count): array
    {
        $from = CarbonImmutable::createFromFormat('H:i', $start);

        return collect(range(0, $count - 1))
            ->map(fn (int $step) => $from->addMinutes($step * Service::BLOCK_MINUTES)->format('H:i'))
            ->all();
    }

    /**
     * Whether the start time has already passed in the salon's own time zone.
     */
    public function isSlotInPast(Salon $salon, CarbonImmutable $date, string $slot): bool
    {
        $start = CarbonImmutable::parse($date->toDateString().' '.$slot, $salon->timezone);

        return $start->lessThanOrEqualTo(CarbonImmutable::now($salon->timezone));
    }

    /**
     * Checks one start time. Returns null unless every block the service needs
     * is free, which is the one place availability truth comes from, never the
     * model.
     *
     * @return array{date: string, time_slot: string, time_range: string, duration_minutes: int, price: float, deposit: float}|null
     */
    public function quote(Stylist $stylist, Service $service, CarbonImmutable $date, string $slot, bool $lockForUpdate = false): ?array
    {
        if (! $this->performs($stylist, $service)) {
            return null;
        }

        $blocks = self::blocks($slot, $service->blockCount());

        $query = StylistSlot::where('stylist_id', $stylist->id)
            ->whereDate('slot_date', $date->toDateString())
            ->whereIn('time_slot', $blocks);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $rows = $query->get();

        if ($rows->count() !== count($blocks) || $rows->contains(fn (StylistSlot $row) => ! $row->isAvailable())) {
            return null;
        }

        return [
            'date' => $date->toDateString(),
            'time_slot' => $slot,
            'time_range' => self::range($slot, $service->duration_minutes),
            'duration_minutes' => $service->duration_minutes,
            'price' => (float) $service->price,
            'deposit' => (float) $service->deposit,
        ];
    }

    /**
     * Every start time of the day at which the service still fits and which
     * has not begun yet.
     *
     * @return list<string>
     */
    public function availableStarts(Stylist $stylist, Service $service, CarbonImmutable $date): array
    {
        $salon = $stylist->salon;

        $free = StylistSlot::where('stylist_id', $stylist->id)
            ->whereDate('slot_date', $date->toDateString())
            ->where('is_booked', false)
            ->pluck('time_slot')
            ->flip();

        return $free->keys()
            ->sort()
            ->filter(fn (string $slot) => ! $this->isSlotInPast($salon, $date, $slot))
            ->filter(fn (string $slot) => collect(self::blocks($slot, $service->blockCount()))->every(fn (string $block) => $free->has($block)))
            ->values()
            ->all();
    }

    /**
     * Marks the blocks of an appointment as taken or free again.
     *
     * @param  list<string>  $blocks
     */
    public function setBooked(Stylist|int $stylist, CarbonImmutable|string $date, array $blocks, bool $booked): void
    {
        StylistSlot::where('stylist_id', $stylist instanceof Stylist ? $stylist->id : $stylist)
            ->whereDate('slot_date', $date instanceof CarbonImmutable ? $date->toDateString() : $date)
            ->whereIn('time_slot', $blocks)
            ->update(['is_booked' => $booked]);
    }
}
