<?php

namespace Tests\Unit;

use App\Models\Service;
use App\Models\Stylist;
use App\Modules\Appointment\Support\StylistAvailability;
use Tests\TestCase;

class StylistAvailabilityTest extends TestCase
{
    public function test_an_appointment_range_follows_the_service_duration(): void
    {
        $this->assertSame('11:00–12:00', StylistAvailability::range('11:00', 60));
        $this->assertSame('11:00–12:30', StylistAvailability::range('11:00', 90));
        $this->assertSame('18:30–19:00', StylistAvailability::range('18:30', 30));
    }

    public function test_a_service_covers_consecutive_half_hour_blocks(): void
    {
        $this->assertSame(['11:00'], StylistAvailability::blocks('11:00', 1));
        $this->assertSame(['11:00', '11:30', '12:00'], StylistAvailability::blocks('11:00', 3));
        $this->assertSame(['18:30', '19:00'], StylistAvailability::blocks('18:30', 2));
    }

    public function test_the_block_count_rounds_the_duration_up(): void
    {
        $this->assertSame(1, (new Service(['duration_minutes' => 30]))->blockCount());
        $this->assertSame(2, (new Service(['duration_minutes' => 45]))->blockCount());
        $this->assertSame(2, (new Service(['duration_minutes' => 60]))->blockCount());
        $this->assertSame(6, (new Service(['duration_minutes' => 180]))->blockCount());
    }

    public function test_a_stylist_only_performs_services_in_their_specialty_at_their_salon(): void
    {
        $availability = new StylistAvailability;
        $stylist = new Stylist(['salon_id' => 1, 'specialty' => 'hair']);

        $this->assertTrue($availability->performs($stylist, new Service(['salon_id' => 1, 'category' => 'hair'])));
        $this->assertFalse($availability->performs($stylist, new Service(['salon_id' => 1, 'category' => 'nails'])));
        $this->assertFalse($availability->performs($stylist, new Service(['salon_id' => 2, 'category' => 'hair'])));
    }
}
