<?php

namespace Tests\Feature;

use App\Models\Salon;
use App\Models\Service;
use App\Models\Stylist;
use App\Models\StylistSlot;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalonStageTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    private Service $haircut;

    private Stylist $rina;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->salon = Salon::create([
            'name' => 'Demo Salon', 'slug' => 'demo', 'public_status' => 'published', 'city' => 'Jakarta', 'country' => 'Indonesia', 'currency' => 'IDR',
            'default_locale' => 'id', 'opening_time' => '09:00', 'closing_time' => '20:00', 'phone' => '+62 21 1234', 'whatsapp' => '6281234567890',
            'description' => 'Salon butik di Senopati.',
        ]);

        $this->haircut = $this->salon->services()->create([
            'name' => 'Potong Rambut', 'slug' => 'haircut', 'category' => 'hair', 'price' => 150000, 'duration_minutes' => 60, 'deposit' => 0,
            'description' => 'Potongan presisi.',
            'translations' => ['en' => ['name' => 'Haircut', 'description' => 'A precision cut.']], 'tags' => ['signature', 'halal'], 'cautions' => ['fragrance'],
            'image_url' => 'images/services/haircut.svg', 'is_active' => true, 'sort_order' => 0,
        ]);
        $this->salon->services()->create([
            'name' => 'Gel Manicure', 'slug' => 'gel', 'category' => 'nails', 'price' => 200000, 'duration_minutes' => 90, 'deposit' => 50000,
            'is_unavailable' => true, 'is_active' => true, 'sort_order' => 1,
        ]);
        $this->salon->services()->create(['name' => 'Retired Cut', 'slug' => 'retired', 'category' => 'hair', 'price' => 1000, 'duration_minutes' => 30, 'is_active' => false]);

        $this->rina = $this->salon->stylists()->create([
            'name' => 'Rina', 'slug' => 'rina', 'title' => 'Senior colorist', 'specialty' => 'hair', 'years_experience' => 9, 'skills' => ['balayage'],
            'description' => 'Ahli warna rambut.', 'translations' => ['en' => ['title' => 'Senior colorist', 'description' => 'A hair colour expert.']], 'is_active' => true,
        ]);
        $this->rina->images()->create(['image_url' => 'images/stylists/rina-1.svg', 'alt_text' => 'Karya Rina', 'sort_order' => 0]);
        StylistSlot::create([
            'stylist_id' => $this->rina->id, 'slot_date' => CarbonImmutable::now('Asia/Jakarta')->addDays(3)->toDateString(), 'time_slot' => '13:00', 'is_booked' => false,
        ]);

        $this->salon->knowledgeItems()->create(['category' => 'facilities', 'title' => 'Wi-Fi gratis', 'body' => 'Wi-Fi cepat untuk semua tamu.', 'tags' => ['wifi'], 'is_active' => true]);
        $this->salon->knowledgeItems()->create(['category' => 'policies', 'title' => 'Kebijakan pembatalan', 'body' => 'Batal gratis 24 jam sebelumnya.', 'is_active' => true]);
        $this->salon->knowledgeItems()->create(['category' => 'faq', 'title' => 'Ada musala?', 'body' => 'Ada, di sebelah toilet.', 'is_active' => true]);
    }

    public function test_the_opening_screen_lists_published_salons(): void
    {
        Salon::create(['name' => 'Draft Salon', 'slug' => 'draft']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Demo Salon')
            ->assertDontSee('Draft Salon')
            ->assertSee(route('salon.services', ['salonSlug' => 'demo', 'lang' => 'id']), false);
    }

    public function test_the_lobby_greets_guests_in_the_requested_language(): void
    {
        $this->get('/demo')->assertOk()->assertSee('Demo Salon')->assertSee('Selamat datang di');
        $this->get('/demo?lang=en')->assertOk()->assertSee('Welcome to')->assertSee('lang="en"', false);
        $this->get('/demo?lang=ja')->assertOk()->assertSee('ようこそ');
        $this->get('/demo?lang=xx')->assertOk()->assertSee('Selamat datang di');
    }

    public function test_every_scene_renders_and_carries_the_beautician_chat(): void
    {
        $routes = [
            'salon.show', 'salon.services', 'salon.stylists', 'salon.facilities', 'salon.info', 'salon.staff', 'salon.appointment',
        ];

        foreach ($routes as $route) {
            $this->get(route($route, ['salonSlug' => 'demo']))
                ->assertOk()
                ->assertSee('id="beautician-app"', false)
                ->assertSee('data-scene=', false);
        }
    }

    public function test_unpublished_and_unknown_salons_are_not_found(): void
    {
        Salon::create(['name' => 'Draft', 'slug' => 'draft']);

        $this->get('/draft')->assertNotFound();
        $this->get('/draft/services')->assertNotFound();
        $this->get('/nowhere')->assertNotFound();
    }

    public function test_the_services_scene_narrates_from_stored_data_and_lists_active_services(): void
    {
        $this->get('/demo/services?lang=en')
            ->assertOk()
            ->assertSee('We have 2 treatments, from IDR 150.000')
            ->assertSee('Haircut')
            ->assertSee('Gel Manicure')
            ->assertDontSee('Retired Cut');
    }

    public function test_a_service_shows_its_price_duration_cautions_and_tags(): void
    {
        $this->get('/demo/services/haircut?lang=en')
            ->assertOk()
            ->assertSee('Haircut')
            ->assertSee('IDR 150.000')
            ->assertSee('A precision cut.')
            ->assertSee('Fragrance')
            ->assertSee('Signature')
            ->assertSee('It takes about 1 h.')
            ->assertSee('Good to know: fragrance.')
            ->assertSee('images/services/haircut.svg', false)
            ->assertSee('service=haircut', false);

        $this->get('/demo/services/gel?lang=en')
            ->assertOk()
            ->assertSee('Unavailable today')
            ->assertSee('It takes about 1 h 30 min.')
            ->assertSee('A deposit of IDR 50.000 holds your booking.');

        $this->get('/demo/services/retired')->assertNotFound();
        $this->get('/demo/services/nothing-here')->assertNotFound();
    }

    public function test_the_stylist_scenes_show_specialty_experience_and_skills(): void
    {
        $this->get('/demo/stylists?lang=en')->assertOk()->assertSee('Rina')->assertSee('Meet Rina.');

        $this->get('/demo/stylists/rina?lang=en')
            ->assertOk()
            ->assertSee('Rina')
            ->assertSee('Senior colorist')
            ->assertSee('Hair stylist')
            ->assertSee('Specialty: Hair stylist.')
            ->assertSee('9 years of experience.')
            ->assertSee('Known for balayage.')
            ->assertSee('stylist=rina', false);

        $this->get('/demo/stylists/nowhere')->assertNotFound();
    }

    public function test_existing_service_artwork_displays_the_photo_without_changing_the_stored_path(): void
    {
        $this->haircut->update(['image_url' => 'images/services/haircut-blow-dry.svg']);

        $this->get('/demo/services/haircut')
            ->assertOk()
            ->assertSee('images/services/haircut-blow-dry.webp', false)
            ->assertDontSee('images/services/haircut-blow-dry.svg', false);

        $this->assertSame('images/services/haircut-blow-dry.svg', $this->haircut->fresh()->image_url);
    }

    public function test_external_service_images_keep_their_original_url(): void
    {
        $this->haircut->update(['image_url' => 'https://example.test/images/services/haircut-blow-dry.svg']);

        $this->get('/demo/services/haircut')
            ->assertOk()
            ->assertSee('https://example.test/images/services/haircut-blow-dry.svg', false);
    }

    public function test_the_facility_scenes_show_stored_amenities(): void
    {
        $facility = $this->salon->knowledgeItems()->where('category', 'facilities')->firstOrFail();

        $this->get('/demo/facilities')->assertOk()->assertSee('Wi-Fi gratis')->assertDontSee('Kebijakan pembatalan');
        $this->get('/demo/facilities/'.$facility->id)->assertOk()->assertSee('Wi-Fi cepat untuk semua tamu.');
        $this->get('/demo/facilities/99999')->assertNotFound();
    }

    public function test_the_info_scene_shows_opening_hours_policies_and_faq(): void
    {
        $this->get('/demo/info?lang=en')
            ->assertOk()
            ->assertSee('09:00')
            ->assertSee('20:00')
            ->assertSee('We are open from 09:00 to 20:00.')
            ->assertSee('Kebijakan pembatalan')
            ->assertSee('Ada musala?')
            ->assertSee('Salon butik di Senopati.');
    }

    public function test_the_staff_scene_offers_whatsapp_and_phone(): void
    {
        $this->get('/demo/staff')
            ->assertOk()
            ->assertSee('https://wa.me/6281234567890', false)
            ->assertSee('tel:+62211234', false);
    }

    public function test_the_appointment_scene_offers_services_and_stylists(): void
    {
        $this->get('/demo/appointment?service=haircut&stylist=rina&lang=en')
            ->assertOk()
            ->assertSee('data-wizard', false)
            ->assertSee('data-preselect-service="haircut"', false)
            ->assertSee('data-preselect-stylist="rina"', false)
            ->assertSee('Haircut')
            ->assertSee('Rina');
    }
}
