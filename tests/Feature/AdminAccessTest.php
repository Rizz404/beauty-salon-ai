<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\HandoverRequest;
use App\Models\Salon;
use App\Models\StylistSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    private Salon $otherSalon;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->salon = Salon::create(['name' => 'Demo', 'slug' => 'demo', 'public_status' => 'published']);
        $this->otherSalon = Salon::create(['name' => 'Other', 'slug' => 'other', 'public_status' => 'published']);

        $this->staff = User::factory()->create();
        $this->salon->users()->attach($this->staff->id, ['role' => 'owner', 'status' => 'active']);
    }

    private function appointmentFor(Salon $salon, string $reference, string $time = '11:00'): Appointment
    {
        $service = $salon->services()->create(['category' => 'hair', 'name' => 'Cut '.$reference, 'slug' => Str::slug('cut '.$reference), 'price' => 100000, 'duration_minutes' => 60]);
        $stylist = $salon->stylists()->create(['name' => 'Stylist '.$reference, 'slug' => Str::slug('stylist '.$reference), 'specialty' => 'hair', 'is_active' => true]);

        return Appointment::create([
            'reference' => $reference, 'salon_id' => $salon->id, 'service_id' => $service->id, 'stylist_id' => $stylist->id, 'guest_name' => 'Guest '.$reference,
            'appointment_date' => now()->addDays(3)->toDateString(), 'time_slot' => $time, 'duration_minutes' => 60,
        ]);
    }

    private function foreignHandover(): HandoverRequest
    {
        $conversation = Conversation::create(['salon_id' => $this->otherSalon->id, 'guest_token' => (string) Str::uuid(), 'locale' => 'en']);

        return HandoverRequest::create(['conversation_id' => $conversation->id, 'reason' => 'complaint', 'summary' => 'Foreign complaint']);
    }

    public function test_visitors_are_sent_to_the_login_page_for_every_admin_screen(): void
    {
        foreach (['admin.dashboard', 'admin.services.index', 'admin.stylists.index', 'admin.knowledge-items.index', 'admin.appointments.index', 'admin.handovers.index'] as $route) {
            $this->get(route($route))->assertRedirect(route('admin.login'));
        }
    }

    public function test_staff_only_see_appointments_of_their_own_salon(): void
    {
        $this->appointmentFor($this->salon, 'AP-MINE', '13:00');
        $this->appointmentFor($this->otherSalon, 'AP-FOREIGN');

        $this->actingAs($this->staff)->get(route('admin.appointments.index'))
            ->assertOk()
            ->assertSee('AP-MINE')
            ->assertDontSee('AP-FOREIGN');
    }

    public function test_staff_cannot_change_another_salons_appointment(): void
    {
        $appointment = $this->appointmentFor($this->otherSalon, 'AP-FOREIGN');

        $this->actingAs($this->staff)
            ->patch(route('admin.appointments.status', $appointment), ['status' => 'confirmed'])
            ->assertNotFound();

        $this->assertSame(Appointment::STATUS_PENDING, $appointment->fresh()->status);
    }

    public function test_cancelling_an_appointment_releases_the_stylists_time(): void
    {
        $appointment = $this->appointmentFor($this->salon, 'AP-MINE', '13:00');
        $date = $appointment->appointment_date->toDateString();

        foreach (['13:00', '13:30', '14:00'] as $time) {
            StylistSlot::create(['stylist_id' => $appointment->stylist_id, 'slot_date' => $date, 'time_slot' => $time, 'is_booked' => $time !== '14:00']);
        }

        $this->actingAs($this->staff)
            ->patch(route('admin.appointments.status', $appointment), ['status' => 'cancelled'])
            ->assertRedirect();

        $this->assertSame(Appointment::STATUS_CANCELLED, $appointment->fresh()->status);
        $this->assertSame(0, StylistSlot::where('is_booked', true)->count());
    }

    public function test_staff_cannot_open_or_answer_another_salons_handover(): void
    {
        $handover = $this->foreignHandover();

        $this->actingAs($this->staff)->get(route('admin.handovers.show', $handover))->assertNotFound();
        $this->actingAs($this->staff)->post(route('admin.handovers.reply', $handover), ['message' => 'Hi'])->assertNotFound();
        $this->actingAs($this->staff)->post(route('admin.handovers.resolve', $handover))->assertNotFound();
    }

    public function test_resolving_a_handover_hands_the_conversation_back_to_the_beautician(): void
    {
        $conversation = Conversation::create(['salon_id' => $this->salon->id, 'guest_token' => (string) Str::uuid(), 'locale' => 'en', 'status' => Conversation::STATUS_HANDED_OVER]);
        $handover = HandoverRequest::create(['conversation_id' => $conversation->id, 'reason' => 'bridal_package', 'summary' => 'Wedding party of 6']);

        $this->actingAs($this->staff)->post(route('admin.handovers.reply', $handover), ['message' => 'Happy to help!'])->assertRedirect();
        $this->assertSame('Happy to help!', ConversationMessage::where('role', ConversationMessage::ROLE_STAFF)->firstOrFail()->content);

        $this->actingAs($this->staff)->post(route('admin.handovers.resolve', $handover))->assertRedirect(route('admin.handovers.index'));

        $this->assertSame(HandoverRequest::STATUS_RESOLVED, $handover->fresh()->status);
        $this->assertSame(Conversation::STATUS_ACTIVE, $conversation->fresh()->status);
    }

    public function test_staff_manage_the_services_of_their_own_salon_only(): void
    {
        $this->actingAs($this->staff)->post(route('admin.services.store'), [
            'category' => 'hair', 'name' => 'Gloss Treatment', 'price' => 340000, 'duration_minutes' => 50, 'deposit' => 0,
            'tags' => 'Halal, Vegan', 'cautions' => 'fragrance', 'is_active' => '1',
        ])->assertRedirect(route('admin.services.index'));

        $service = $this->salon->services()->firstOrFail();
        $this->assertSame('gloss-treatment', $service->slug);
        $this->assertSame(['halal', 'vegan'], $service->tags);
        $this->assertSame(['fragrance'], $service->cautions);
        $this->assertSame(60, $service->duration_minutes);
        $this->assertTrue($service->is_active);

        $foreign = $this->otherSalon->services()->create(['category' => 'hair', 'name' => 'Foreign', 'slug' => 'foreign', 'price' => 1, 'duration_minutes' => 30]);

        $this->actingAs($this->staff)->get(route('admin.services.edit', $foreign))->assertNotFound();
        $this->actingAs($this->staff)->delete(route('admin.services.destroy', $foreign))->assertNotFound();
        $this->assertNotNull($foreign->fresh());
    }

    public function test_a_service_must_last_at_least_one_block(): void
    {
        $this->actingAs($this->staff)->post(route('admin.services.store'), [
            'category' => 'hair', 'name' => 'Too Short', 'price' => 1000, 'duration_minutes' => 10,
        ])->assertSessionHasErrors('duration_minutes');
    }

    public function test_stylist_specialty_is_validated(): void
    {
        $this->actingAs($this->staff)->post(route('admin.stylists.store'), [
            'name' => 'Patio', 'specialty' => 'juggling', 'years_experience' => 3,
        ])->assertSessionHasErrors('specialty');

        $this->actingAs($this->staff)->post(route('admin.stylists.store'), [
            'name' => 'Patio', 'specialty' => 'nails', 'years_experience' => 3, 'skills' => 'Gel Nails, nail_art', 'is_active' => '1',
        ])->assertRedirect(route('admin.stylists.index'));

        $stylist = $this->salon->stylists()->firstOrFail();
        $this->assertSame('nails', $stylist->specialty);
        $this->assertSame(['gel_nails', 'nail_art'], $stylist->skills);
    }

    public function test_the_dashboard_counts_only_the_staffs_own_salon(): void
    {
        $this->foreignHandover();
        $this->appointmentFor($this->otherSalon, 'AP-FOREIGN');

        $this->actingAs($this->staff)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('stats', fn (array $stats) => $stats['open_handovers'] === 0 && $stats['pending_appointments'] === 0);
    }

    public function test_an_account_without_a_salon_is_turned_away(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))->assertForbidden();
    }
}
