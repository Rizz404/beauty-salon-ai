<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Conversation;
use App\Models\Salon;
use App\Models\Service;
use App\Models\Stylist;
use App\Models\StylistSlot;
use App\Modules\Appointment\Actions\ChangeAppointmentStatus;
use App\Modules\Appointment\Support\StylistAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AppointmentRequestTest extends TestCase
{
    use RefreshDatabase;

    private const BLOCKS = ['11:00', '11:30', '12:00', '12:30', '13:00', '13:30'];

    private Salon $salon;

    private Service $haircut;

    private Service $balayage;

    private Stylist $rina;

    private Stylist $kenji;

    private Stylist $sari;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();

        $this->salon = Salon::create([
            'name' => 'Demo', 'slug' => 'demo', 'public_status' => 'published', 'currency' => 'IDR', 'timezone' => 'Asia/Jakarta',
            'default_locale' => 'en', 'whatsapp' => '6281234567890', 'email' => 'hello@demo.test',
        ]);

        $this->haircut = $this->salon->services()->create(['name' => 'Haircut', 'slug' => 'haircut', 'category' => 'hair', 'price' => 150000, 'duration_minutes' => 60, 'deposit' => 0, 'is_active' => true]);
        $this->balayage = $this->salon->services()->create(['name' => 'Balayage', 'slug' => 'balayage', 'category' => 'hair', 'price' => 1450000, 'duration_minutes' => 120, 'deposit' => 300000, 'is_active' => true]);

        $this->rina = $this->salon->stylists()->create(['name' => 'Rina', 'slug' => 'rina', 'specialty' => 'hair', 'years_experience' => 9, 'is_active' => true]);
        $this->kenji = $this->salon->stylists()->create(['name' => 'Kenji', 'slug' => 'kenji', 'specialty' => 'hair', 'years_experience' => 6, 'is_active' => true]);
        $this->sari = $this->salon->stylists()->create(['name' => 'Sari', 'slug' => 'sari', 'specialty' => 'nails', 'years_experience' => 5, 'is_active' => true]);

        $this->date = CarbonImmutable::now($this->salon->timezone)->addDays(5)->toDateString();

        foreach ([$this->rina, $this->kenji, $this->sari] as $stylist) {
            foreach (self::BLOCKS as $time) {
                StylistSlot::create(['stylist_id' => $stylist->id, 'slot_date' => $this->date, 'time_slot' => $time, 'is_booked' => false]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'service_slug' => 'haircut', 'stylist_slug' => 'rina', 'appointment_date' => $this->date, 'time_slot' => '11:00',
            'guest_name' => 'Ayu', 'contact_type' => 'whatsapp', 'contact_value' => '+62 811 1234 5678', 'locale' => 'en',
            ...$overrides,
        ];
    }

    private function bookBlock(Stylist $stylist, string $time): void
    {
        StylistSlot::where('stylist_id', $stylist->id)->whereDate('slot_date', $this->date)->where('time_slot', $time)->update(['is_booked' => true]);
    }

    public function test_the_slots_endpoint_only_offers_start_times_where_the_whole_service_fits(): void
    {
        $payload = ['service_slug' => 'haircut', 'stylist_slug' => 'rina', 'appointment_date' => $this->date, 'locale' => 'en'];

        $this->postJson('/demo/appointment/slots', $payload)
            ->assertOk()
            ->assertJsonPath('duration_minutes', 60)
            ->assertJsonPath('slots.0.time_range', '11:00–12:00')
            ->assertJsonCount(5, 'slots');

        $this->bookBlock($this->rina, '12:00');

        $starts = collect($this->postJson('/demo/appointment/slots', $payload)->assertOk()->json('slots'))->pluck('time_slot')->all();
        $this->assertSame(['11:00', '12:30', '13:00'], $starts);

        $long = $this->postJson('/demo/appointment/slots', [...$payload, 'service_slug' => 'balayage'])->assertOk();
        $this->assertSame([], $long->json('slots'));
    }

    public function test_a_quote_reports_the_price_duration_and_deposit(): void
    {
        $this->postJson('/demo/appointment/quote', $this->payload(['service_slug' => 'balayage']))
            ->assertOk()
            ->assertJsonPath('available', true)
            ->assertJsonPath('price', 1450000)
            ->assertJsonPath('deposit', 300000)
            ->assertJsonPath('duration_minutes', 120)
            ->assertJsonPath('time_range', '11:00–13:00');
    }

    public function test_submitting_creates_a_pending_appointment_and_holds_the_stylists_time(): void
    {
        $this->postJson('/demo/appointment', $this->payload(['occasion' => 'party', 'special_request' => 'Bringing a reference photo']))
            ->assertCreated()
            ->assertJsonPath('status', Appointment::STATUS_PENDING)
            ->assertJsonStructure(['reference', 'handover' => ['whatsapp_url', 'phone_url', 'email_url']]);

        $appointment = Appointment::firstOrFail();
        $this->assertStringStartsWith('AP-', $appointment->reference);
        $this->assertSame('party', $appointment->occasion);
        $this->assertSame('Bringing a reference photo', $appointment->notes);
        $this->assertSame(60, $appointment->duration_minutes);
        $this->assertSame('11:00–12:00', $appointment->time_range);
        $this->assertSame([true, true, false], StylistSlot::where('stylist_id', $this->rina->id)->orderBy('time_slot')->limit(3)->pluck('is_booked')->all());
        $this->assertSame(0, StylistSlot::where('stylist_id', $this->kenji->id)->where('is_booked', true)->count());
    }

    public function test_a_taken_time_is_refused_with_alternatives(): void
    {
        $this->bookBlock($this->rina, '11:30');

        $response = $this->postJson('/demo/appointment', $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('time_slot');

        $alternatives = collect($response->json('alternatives'));
        $this->assertSame('12:30', $alternatives->firstWhere('type', 'slot')['time_slot']);
        $this->assertSame('kenji', $alternatives->firstWhere('type', 'stylist')['slug']);
        $this->assertSame(0, Appointment::count());
    }

    public function test_the_stylist_must_perform_the_service(): void
    {
        $this->postJson('/demo/appointment', $this->payload(['stylist_slug' => 'sari']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('stylist_slug');

        $this->assertSame(0, Appointment::count());
    }

    public function test_dates_times_and_names_are_validated(): void
    {
        $this->postJson('/demo/appointment', $this->payload(['appointment_date' => '2001-01-01']))->assertUnprocessable()->assertJsonValidationErrors('appointment_date');

        $far = CarbonImmutable::now($this->salon->timezone)->addDays(StylistAvailability::MAX_ADVANCE_DAYS + 5)->toDateString();
        $this->postJson('/demo/appointment', $this->payload(['appointment_date' => $far]))->assertUnprocessable()->assertJsonValidationErrors('appointment_date');

        $this->postJson('/demo/appointment', $this->payload(['time_slot' => '03:00']))->assertUnprocessable()->assertJsonValidationErrors('time_slot');
        $this->postJson('/demo/appointment', $this->payload(['service_slug' => 'nowhere']))->assertUnprocessable()->assertJsonValidationErrors('service_slug');
        $this->postJson('/demo/appointment', $this->payload(['stylist_slug' => 'nowhere']))->assertUnprocessable()->assertJsonValidationErrors('stylist_slug');
    }

    public function test_contact_details_are_checked_for_the_chosen_method(): void
    {
        $this->postJson('/demo/appointment', $this->payload(['contact_type' => 'email', 'contact_value' => 'not-an-email']))->assertUnprocessable()->assertJsonValidationErrors('contact_value');
        $this->postJson('/demo/appointment', $this->payload(['contact_type' => 'phone', 'contact_value' => 'abc']))->assertUnprocessable()->assertJsonValidationErrors('contact_value');
        $this->postJson('/demo/appointment', $this->payload(['contact_type' => 'email', 'contact_value' => 'ayu@example.com']))->assertCreated();

        $this->assertSame('ayu@example.com', Appointment::firstOrFail()->guest_email);
    }

    public function test_an_appointment_links_to_the_conversation_it_came_from(): void
    {
        $conversation = Conversation::create(['salon_id' => $this->salon->id, 'guest_token' => (string) Str::uuid(), 'locale' => 'en']);

        $this->postJson('/demo/appointment', $this->payload(['guest_token' => $conversation->guest_token]))->assertCreated();

        $this->assertSame($conversation->id, Appointment::firstOrFail()->conversation_id);
    }

    public function test_cancelling_gives_the_stylists_time_back_once(): void
    {
        $this->postJson('/demo/appointment', $this->payload(['service_slug' => 'balayage']))->assertCreated();
        $appointment = Appointment::firstOrFail();

        $this->assertSame(4, StylistSlot::where('stylist_id', $this->rina->id)->where('is_booked', true)->count());

        app(ChangeAppointmentStatus::class)->handle($appointment, Appointment::STATUS_CANCELLED);

        $this->assertSame(0, StylistSlot::where('stylist_id', $this->rina->id)->where('is_booked', true)->count());
        $this->assertSame(Appointment::STATUS_CANCELLED, $appointment->fresh()->status);
    }

    public function test_unpublished_salons_take_no_appointments(): void
    {
        $this->salon->update(['public_status' => 'draft']);

        $this->postJson('/demo/appointment/slots', $this->payload())->assertNotFound();
        $this->postJson('/demo/appointment/quote', $this->payload())->assertNotFound();
        $this->postJson('/demo/appointment', $this->payload())->assertNotFound();
    }
}
