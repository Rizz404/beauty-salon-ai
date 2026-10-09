<?php

namespace Database\Factories;

use App\Models\Stylist;
use App\Models\StylistSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StylistSlot>
 */
class StylistSlotFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stylist_id' => Stylist::factory(),
            'slot_date' => now()->addDays(3)->toDateString(),
            'time_slot' => '11:00',
            'is_booked' => false,
        ];
    }

    public function booked(): static
    {
        return $this->state(fn () => ['is_booked' => true]);
    }
}
