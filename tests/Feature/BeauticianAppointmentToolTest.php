<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Conversation;
use App\Models\Salon;
use App\Models\StylistSlot;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\RunsBeauticianTools;
use Tests\TestCase;

class BeauticianAppointmentToolTest extends TestCase
{
    use RefreshDatabase, RunsBeauticianTools;

    private function salon(): Salon
    {
        return Salon::create(['name' => 'Demo', 'slug' => 'demo', 'public_status' => 'published', 'currency' => 'IDR', 'timezone' => 'Asia/Jakarta']);
    }

    private function conversation(Salon $salon): Conversation
    {
        return Conversation::create(['salon_id' => $salon->id, 'guest_token' => (string) Str::uuid(), 'locale' => 'en']);
    }

    /**
     * @param  list<string>  $times
     */
    private function openBlocks(int $stylistId, string $date, array $times): void
    {
        foreach ($times as $time) {
            StylistSlot::create(['stylist_id' => $stylistId, 'slot_date' => $date, 'time_slot' => $time, 'is_booked' => false]);
        }
    }

    public function test_the_ai_appointment_tool_creates_a_reference_and_holds_the_whole_service(): void
    {
        $salon = $this->salon();
        $salon->services()->create(['name' => 'Balayage', 'slug' => 'balayage', 'category' => 'hair', 'price' => 1450000, 'duration_minutes' => 120, 'deposit' => 300000, 'is_active' => true]);
        $stylist = $salon->stylists()->create(['name' => 'Rina', 'slug' => 'rina', 'specialty' => 'hair', 'is_active' => true]);
        $conversation = $this->conversation($salon);

        $date = CarbonImmutable::now($salon->timezone)->addDays(5)->toDateString();
        $this->openBlocks($stylist->id, $date, ['11:00', '11:30', '12:00', '12:30', '13:00', '13:30']);

        $input = [
            'service_slug' => 'balayage', 'stylist_slug' => 'rina', 'date' => $date, 'time_slot' => '11:00',
            'guest_name' => 'Ayu', 'guest_phone' => '+62 811 111 222',
        ];

        $quote = $this->runTool($salon, $conversation, 'check_availability', $input);
        $this->assertTrue($quote['ui']['available']);
        $this->assertSame(1450000.0, $quote['ui']['quote']['price']);
        $this->assertSame(300000.0, $quote['ui']['quote']['deposit']);
        $this->assertSame(120, $quote['ui']['quote']['duration_minutes']);

        $result = $this->runTool($salon, $conversation, 'create_appointment_request', $input);

        $appointment = Appointment::firstOrFail();
        $this->assertSame($appointment->reference, $result['ui']['appointment']['appointment_reference']);
        $this->assertSame('11:00', $appointment->time_slot);
        $this->assertSame(120, $appointment->duration_minutes);
        $this->assertSame(300000.0, (float) $appointment->deposit_total);
        $this->assertSame([true, true, true, true, false, false], StylistSlot::orderBy('time_slot')->pluck('is_booked')->all());

        $taken = $this->runTool($salon, $conversation, 'create_appointment_request', $input);
        $this->assertNull($taken['ui']);
        $this->assertSame(1, Appointment::count());

        $busy = $this->runTool($salon, $conversation, 'check_availability', $input);
        $this->assertFalse($busy['ui']['available']);
        $this->assertSame(1, Appointment::count());
    }

    public function test_the_tools_refuse_the_wrong_stylist_an_unknown_start_time_and_a_past_date(): void
    {
        $salon = $this->salon();
        $salon->services()->create(['name' => 'Gel Manicure', 'slug' => 'gel', 'category' => 'nails', 'price' => 220000, 'duration_minutes' => 60, 'is_active' => true]);
        $hair = $salon->stylists()->create(['name' => 'Rina', 'slug' => 'rina', 'specialty' => 'hair', 'is_active' => true]);
        $nails = $salon->stylists()->create(['name' => 'Sari', 'slug' => 'sari', 'specialty' => 'nails', 'is_active' => true]);
        $conversation = $this->conversation($salon);
        $date = CarbonImmutable::now($salon->timezone)->addDays(2)->toDateString();

        $this->openBlocks($hair->id, $date, ['15:00', '15:30']);
        $this->openBlocks($nails->id, $date, ['15:00', '15:30']);

        $base = ['service_slug' => 'gel', 'date' => $date, 'guest_name' => 'Ayu', 'guest_phone' => '+62 811 111 222'];

        $wrongStylist = $this->runTool($salon, $conversation, 'create_appointment_request', [...$base, 'stylist_slug' => 'rina', 'time_slot' => '15:00']);
        $this->assertNull($wrongStylist['ui']);
        $this->assertStringContainsString('does not perform', $wrongStylist['text']);

        $unknownTime = $this->runTool($salon, $conversation, 'check_availability', [...$base, 'stylist_slug' => 'sari', 'time_slot' => '03:00']);
        $this->assertStringContainsString('15:00', $unknownTime['text']);

        $past = $this->runTool($salon, $conversation, 'check_availability', [...$base, 'stylist_slug' => 'sari', 'date' => '2001-01-01', 'time_slot' => '15:00']);
        $this->assertStringContainsString('past', $past['text']);

        $this->assertSame(0, Appointment::count());
    }

    public function test_the_stylist_search_lists_only_stylists_free_for_the_whole_service(): void
    {
        $salon = $this->salon();
        $salon->services()->create(['name' => 'Haircut', 'slug' => 'haircut', 'category' => 'hair', 'price' => 150000, 'duration_minutes' => 60, 'is_active' => true]);
        $rina = $salon->stylists()->create(['name' => 'Rina', 'slug' => 'rina', 'specialty' => 'hair', 'sort_order' => 0, 'is_active' => true]);
        $kenji = $salon->stylists()->create(['name' => 'Kenji', 'slug' => 'kenji', 'specialty' => 'hair', 'sort_order' => 1, 'is_active' => true]);
        $salon->stylists()->create(['name' => 'Sari', 'slug' => 'sari', 'specialty' => 'nails', 'sort_order' => 2, 'is_active' => true]);
        $conversation = $this->conversation($salon);
        $date = CarbonImmutable::now($salon->timezone)->addDays(4)->toDateString();

        $this->openBlocks($rina->id, $date, ['11:00', '11:30', '12:00']);
        $this->openBlocks($kenji->id, $date, ['11:00']);

        $result = $this->runTool($salon, $conversation, 'search_stylists', ['service_slug' => 'haircut', 'date' => $date]);

        $this->assertSame('stylist_results', $result['ui']['type']);
        $this->assertSame(['rina'], collect($result['ui']['stylists'])->pluck('stylist_slug')->all());
        $this->assertSame(['11:00', '11:30'], collect($result['ui']['stylists'][0]['available_slots'])->pluck('time_slot')->all());

        $none = $this->runTool($salon, $conversation, 'search_stylists', ['service_slug' => 'haircut', 'date' => $date, 'time_slot' => '12:00']);
        $this->assertNull($none['ui']);
    }

    public function test_service_search_filters_by_need_budget_and_time_and_hides_unpublished_services(): void
    {
        $salon = $this->salon();
        $salon->services()->create(['name' => 'Scalp Spa', 'slug' => 'scalp-spa', 'category' => 'hair', 'price' => 320000, 'duration_minutes' => 60, 'tags' => ['vegan'], 'is_active' => true]);
        $salon->services()->create(['name' => 'Balayage', 'slug' => 'balayage', 'category' => 'hair', 'price' => 1450000, 'duration_minutes' => 180, 'tags' => ['signature'], 'is_active' => true]);
        $salon->services()->create(['name' => 'Secret Treatment', 'slug' => 'secret', 'category' => 'hair', 'price' => 10000, 'duration_minutes' => 30, 'tags' => ['vegan'], 'is_active' => false]);
        $conversation = $this->conversation($salon);

        $vegan = $this->runTool($salon, $conversation, 'search_services', ['need' => 'vegan']);
        $this->assertSame(['scalp-spa'], collect($vegan['ui']['items'])->pluck('service_slug')->all());

        $cheap = $this->runTool($salon, $conversation, 'search_services', ['category' => 'hair', 'max_price' => 500000]);
        $this->assertSame(['scalp-spa'], collect($cheap['ui']['items'])->pluck('service_slug')->all());

        $quick = $this->runTool($salon, $conversation, 'search_services', ['max_minutes' => 90]);
        $this->assertSame(['scalp-spa'], collect($quick['ui']['items'])->pluck('service_slug')->all());

        $none = $this->runTool($salon, $conversation, 'search_services', ['query' => 'sushi']);
        $this->assertNull($none['ui']);
    }
}
