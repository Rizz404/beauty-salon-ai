<?php

namespace Tests\Feature;

use App\Models\Salon;
use App\Models\Service;
use App\Models\Stylist;
use App\Models\StylistImage;
use App\Models\StylistSlot;
use App\Modules\Appointment\Support\StylistAvailability;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SalonDemoSeeder;
use Database\Seeders\SalonSeeder;
use Database\Seeders\StylistImageSeeder;
use Database\Seeders\StylistSeeder;
use Database\Seeders\StylistSlotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalonDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_explicit_demo_seeder_publishes_a_complete_salon_in_production_without_login_accounts(): void
    {
        $this->withoutVite();
        $this->travelTo(CarbonImmutable::parse('2026-10-12 08:00:00', 'Asia/Jakarta'));
        $this->app->instance('env', 'production');

        $this->artisan('db:seed', ['--class' => SalonDemoSeeder::class, '--force' => true, '--no-interaction' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('salons', ['name' => 'Beauty Salon AI', 'slug' => 'beauty-salon-ai', 'public_status' => 'published']);
        $this->assertDatabaseCount('services', 16);
        $this->assertDatabaseCount('stylists', 6);
        $this->assertDatabaseCount('stylist_images', 12);
        $this->assertDatabaseCount('salon_knowledge_items', 16);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('salon_users', 0);
        $this->assertDatabaseCount('appointments', 0);
        $this->assertDatabaseMissing('stylist_slots', ['is_booked' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Masuk ke Beauty Salon AI')
            ->assertDontSee('Salon sedang bersiap.');

        $this->get('/beauty-salon-ai/services')
            ->assertOk()
            ->assertSee('Potong Rambut &amp; Blow Dry', false)
            ->assertSee('IDR 250.000')
            ->assertSee('images/services/haircut-blow-dry.webp', false);

        $this->get('/beauty-salon-ai/stylists/rina-pratama')
            ->assertOk()
            ->assertSee('images/stylists/rina-pratama-1.webp', false)
            ->assertSee('images/stylists/rina-pratama-2.webp', false);
    }

    public function test_reseeding_upgrades_legacy_portfolio_images_without_duplicates_or_changing_custom_photos(): void
    {
        $this->seed([SalonSeeder::class, StylistSeeder::class]);
        $rina = Stylist::where('slug', 'rina-pratama')->firstOrFail();
        $legacyImage = StylistImage::factory()->for($rina)->create(['image_url' => 'images/stylists/rina-pratama-1.svg']);
        $currentImage = StylistImage::factory()->for($rina)->create(['image_url' => 'images/stylists/rina-pratama-2.webp']);
        $customImage = StylistImage::factory()->for($rina)->create([
            'image_url' => 'https://example.test/custom-photo.webp',
            'alt_text' => 'Foto tambahan Rina',
            'sort_order' => 9,
        ]);

        $this->seed(StylistImageSeeder::class);
        $this->seed(StylistImageSeeder::class);

        $this->assertDatabaseCount('stylist_images', 13);
        $this->assertDatabaseHas('stylist_images', ['id' => $legacyImage->id, 'image_url' => 'images/stylists/rina-pratama-1.webp', 'sort_order' => 0]);
        $this->assertDatabaseHas('stylist_images', ['id' => $currentImage->id, 'image_url' => 'images/stylists/rina-pratama-2.webp', 'sort_order' => 1]);
        $this->assertDatabaseHas('stylist_images', [
            'id' => $customImage->id,
            'image_url' => 'https://example.test/custom-photo.webp',
            'alt_text' => 'Foto tambahan Rina',
            'sort_order' => 9,
        ]);
        $this->assertDatabaseMissing('stylist_images', ['image_url' => 'images/stylists/rina-pratama-1.svg']);
    }

    public function test_default_production_seeding_explains_how_to_load_the_sample_without_creating_data(): void
    {
        $this->app->instance('env', 'production');

        $this->artisan('db:seed', ['--force' => true, '--no-interaction' => true])
            ->expectsOutputToContain('php artisan db:seed --class=SalonDemoSeeder --force')
            ->assertSuccessful();

        $this->assertDatabaseCount('salons', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_default_local_seeding_keeps_the_demo_owner_connected_to_the_salon(): void
    {
        $this->app->instance('env', 'local');
        $this->travelTo(CarbonImmutable::parse('2026-10-12 08:00:00', 'Asia/Jakarta'));

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('salons', 1);
        $this->assertDatabaseCount('services', 16);
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('salon_users', ['role' => 'owner', 'status' => 'active']);
    }

    public function test_reseeding_keeps_the_salon_url_catalogue_and_existing_bookings(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-12 08:00:00', 'Asia/Jakarta'));
        $this->seed(SalonDemoSeeder::class);
        $salon = Salon::where('name', SalonSeeder::NAME)->firstOrFail();
        $rina = $salon->stylists()->where('slug', 'rina-pratama')->firstOrFail();
        $slot = StylistSlot::where('stylist_id', $rina->id)
            ->whereDate('slot_date', '2026-10-13')->where('time_slot', '10:00')->firstOrFail();
        $slot->update(['is_booked' => true]);
        $slotCount = StylistSlot::count();

        $this->seed(SalonDemoSeeder::class);

        $this->assertSame('beauty-salon-ai', $salon->fresh()->slug);
        $this->assertDatabaseCount('salons', 1);
        $this->assertDatabaseCount('services', 16);
        $this->assertDatabaseCount('stylists', 6);
        $this->assertDatabaseCount('stylist_images', 12);
        $this->assertDatabaseCount('salon_knowledge_items', 16);
        $this->assertDatabaseCount('stylist_slots', $slotCount);
        $this->assertDatabaseHas('stylist_slots', ['id' => $slot->id, 'is_booked' => true]);
    }

    public function test_an_existing_draft_sample_salon_is_published_without_changing_its_slug(): void
    {
        $salon = Salon::factory()->draft()->create(['name' => SalonSeeder::NAME, 'slug' => 'salon-senopati']);

        $this->seed(SalonSeeder::class);

        $this->assertDatabaseCount('salons', 1);
        $this->assertDatabaseHas('salons', ['id' => $salon->id, 'slug' => 'salon-senopati', 'public_status' => 'published']);
    }

    public function test_sample_availability_respects_shifts_lunch_days_off_and_the_booking_window(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-12 08:00:00', 'Asia/Jakarta'));
        $salon = Salon::factory()->create(['name' => SalonSeeder::NAME]);
        $rina = Stylist::factory()->for($salon)->create(['slug' => 'rina-pratama']);
        $kenji = Stylist::factory()->for($salon)->create(['slug' => 'kenji-mori']);
        $service = Service::factory()->for($salon)->lasting(180)->create(['category' => 'hair']);

        $this->seed(StylistSlotSeeder::class);

        $availability = new StylistAvailability;
        $tuesday = CarbonImmutable::parse('2026-10-13', 'Asia/Jakarta');
        $this->assertSame(['10:00', '14:00', '14:30', '15:00', '15:30', '16:00'], $availability->availableStarts($rina, $service, $tuesday));
        $this->assertSame([], $availability->availableStarts($kenji, $service, $tuesday));
        $this->assertDatabaseMissing('stylist_slots', ['stylist_id' => $rina->id, 'slot_date' => '2026-10-12']);
        $this->assertDatabaseMissing('stylist_slots', ['stylist_id' => $rina->id, 'slot_date' => '2026-12-12']);
        $this->assertDatabaseHas('stylist_slots', ['stylist_id' => $rina->id, 'slot_date' => '2026-12-11', 'time_slot' => '10:00']);
    }

    public function test_sample_shifts_fit_inside_salon_hours_and_inactive_stylists_get_no_new_slots(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-12 08:00:00', 'Asia/Jakarta'));
        $salon = Salon::factory()->create(['name' => SalonSeeder::NAME, 'opening_time' => '11:00', 'closing_time' => '16:15']);
        $rina = Stylist::factory()->for($salon)->create(['slug' => 'rina-pratama']);
        $inactiveStylist = Stylist::factory()->for($salon)->create(['is_active' => false]);
        $service = Service::factory()->for($salon)->lasting(30)->create(['category' => 'hair']);

        $this->seed(StylistSlotSeeder::class);

        $availability = new StylistAvailability;
        $tuesday = CarbonImmutable::parse('2026-10-13', 'Asia/Jakarta');
        $this->assertSame(['11:00', '11:30', '12:00', '12:30', '14:00', '14:30', '15:00', '15:30'], $availability->availableStarts($rina, $service, $tuesday));
        $this->assertDatabaseMissing('stylist_slots', ['stylist_id' => $inactiveStylist->id]);
    }

    public function test_a_stylist_without_a_sample_roster_uses_the_salon_opening_hours(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-12 08:00:00', 'Asia/Jakarta'));
        $salon = Salon::factory()->create(['name' => SalonSeeder::NAME, 'opening_time' => '11:00', 'closing_time' => '12:00']);
        $stylist = Stylist::factory()->for($salon)->create(['slug' => 'additional-stylist']);
        $service = Service::factory()->for($salon)->lasting(30)->create(['category' => 'hair']);

        $this->seed(StylistSlotSeeder::class);

        $this->assertSame(['11:00', '11:30'], (new StylistAvailability)->availableStarts($stylist, $service, CarbonImmutable::parse('2026-10-13', 'Asia/Jakarta')));
    }

    public function test_reseeding_removes_obsolete_free_blocks_but_keeps_bookings_and_dates_outside_the_window(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-12 08:00:00', 'Asia/Jakarta'));
        $salon = Salon::factory()->create(['name' => SalonSeeder::NAME]);
        $rina = Stylist::factory()->for($salon)->create(['slug' => 'rina-pratama']);
        $oldFreeBlock = StylistSlot::factory()->for($rina)->create(['slot_date' => '2026-10-13', 'time_slot' => '09:00']);
        $dayOffBlock = StylistSlot::factory()->for($rina)->create(['slot_date' => '2026-10-12', 'time_slot' => '11:00']);
        $bookedBlock = StylistSlot::factory()->for($rina)->booked()->create(['slot_date' => '2026-10-13', 'time_slot' => '09:30']);
        $pastBlock = StylistSlot::factory()->for($rina)->create(['slot_date' => '2026-10-11', 'time_slot' => '09:00']);
        $laterBlock = StylistSlot::factory()->for($rina)->create(['slot_date' => '2026-12-12', 'time_slot' => '09:00']);

        $this->seed(StylistSlotSeeder::class);

        $this->assertModelMissing($oldFreeBlock);
        $this->assertModelMissing($dayOffBlock);
        $this->assertDatabaseHas('stylist_slots', ['id' => $bookedBlock->id, 'is_booked' => true]);
        $this->assertModelExists($pastBlock);
        $this->assertModelExists($laterBlock);
    }
}
