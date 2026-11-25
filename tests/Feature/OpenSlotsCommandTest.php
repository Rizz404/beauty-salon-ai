<?php

namespace Tests\Feature;

use App\Models\Salon;
use App\Models\Stylist;
use App\Models\StylistSlot;
use App\Modules\Appointment\Support\StylistAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenSlotsCommandTest extends TestCase
{
    use RefreshDatabase;

    private Stylist $stylist;

    private CarbonImmutable $today;

    protected function setUp(): void
    {
        parent::setUp();

        $salon = Salon::create(['name' => 'Demo', 'slug' => 'demo', 'public_status' => 'published']);
        $this->stylist = $salon->stylists()->create(['name' => 'Rina', 'slug' => 'rina', 'specialty' => 'hair', 'is_active' => true]);
        $this->today = CarbonImmutable::now($salon->timezone)->startOfDay();
    }

    /**
     * Seven days of blocks ending today + 6, with the day off on today + 3.
     */
    private function seedWeek(): void
    {
        for ($day = 0; $day < 7; $day++) {
            if ($day === 3) {
                continue;
            }

            foreach (['10:00', '10:30'] as $block) {
                StylistSlot::create(['stylist_id' => $this->stylist->id, 'slot_date' => $this->today->addDays($day)->toDateString(), 'time_slot' => $block, 'is_booked' => $day === 1]);
            }
        }
    }

    private function blocksOn(CarbonImmutable $date): array
    {
        return StylistSlot::query()->where('stylist_id', $this->stylist->id)->whereDate('slot_date', $date->toDateString())->orderBy('time_slot')->pluck('time_slot')->all();
    }

    public function test_it_repeats_the_weekly_pattern_up_to_the_window_and_keeps_bookings(): void
    {
        $this->seedWeek();

        $this->artisan('salon:open-slots')->assertSuccessful();

        $horizon = $this->today->addDays(StylistAvailability::MAX_ADVANCE_DAYS);
        $this->assertSame($horizon->toDateString(), CarbonImmutable::parse(StylistSlot::query()->max('slot_date'))->toDateString());
        $this->assertSame(['10:00', '10:30'], $this->blocksOn($this->today->addDays(7)));
        $this->assertSame([], $this->blocksOn($this->today->addDays(10)), 'The weekly day off carries forward.');
        $this->assertSame([], $this->blocksOn($this->today->addDays(17)));
        $this->assertSame(2, StylistSlot::query()->whereDate('slot_date', $this->today->addDays(1)->toDateString())->where('is_booked', true)->count());
        $this->assertSame(0, StylistSlot::query()->whereDate('slot_date', '>', $this->today->addDays(6)->toDateString())->where('is_booked', true)->count());
    }

    public function test_running_it_twice_adds_nothing_the_second_time(): void
    {
        $this->seedWeek();
        $this->artisan('salon:open-slots')->assertSuccessful();
        $count = StylistSlot::count();

        $this->artisan('salon:open-slots')->expectsOutputToContain('Opened 0 new stylist blocks')->assertSuccessful();
        $this->assertSame($count, StylistSlot::count());
    }

    public function test_inactive_stylists_and_stylists_without_blocks_are_left_alone(): void
    {
        $this->seedWeek();
        $this->stylist->update(['is_active' => false]);
        $count = StylistSlot::count();

        $this->artisan('salon:open-slots')->assertSuccessful();

        $this->assertSame($count, StylistSlot::count());
    }

    public function test_it_is_scheduled_daily(): void
    {
        $events = collect(app(Schedule::class)->events())->filter(fn ($event) => str_contains($event->command, 'salon:open-slots'));

        $this->assertCount(1, $events);
        $this->assertSame('0 1 * * *', $events->first()->expression);
    }
}
