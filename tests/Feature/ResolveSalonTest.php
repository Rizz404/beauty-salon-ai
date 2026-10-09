<?php

namespace Tests\Feature;

use App\Models\Salon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ResolveSalonTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unknown_salon_is_not_found_on_pages_and_on_json_endpoints(): void
    {
        $this->get('/nowhere')->assertNotFound();
        $this->postJson('/nowhere/beautician/start')->assertNotFound();
        $this->postJson('/nowhere/appointment/quote')->assertNotFound();
    }

    public function test_a_draft_salon_is_not_public(): void
    {
        $salon = Salon::factory()->draft()->create(['slug' => 'soon']);

        $this->get('/'.$salon->slug)->assertNotFound();
        $this->postJson('/'.$salon->slug.'/beautician/start')->assertNotFound();
    }

    public function test_the_page_language_comes_from_the_query_or_falls_back_to_the_salon_default(): void
    {
        Salon::factory()->create(['slug' => 'demo', 'default_locale' => 'id']);

        $this->get('/demo?lang=en')->assertOk()->assertSee('lang="en"', false);
        $this->get('/demo?lang=xx')->assertOk()->assertSee('lang="id"', false);
    }

    public function test_a_chat_starts_in_the_requested_supported_language(): void
    {
        Salon::factory()->create(['slug' => 'demo', 'default_locale' => 'id']);

        $this->postJson('/demo/beautician/start', ['locale' => 'ja'])->assertOk()->assertJsonPath('locale', 'ja');
        $this->postJson('/demo/beautician/start', ['locale' => 'xx'])->assertOk()->assertJsonPath('locale', 'id');
    }

    public function test_every_response_carries_a_request_id_and_a_valid_incoming_one_is_reused(): void
    {
        Salon::factory()->create(['slug' => 'demo']);

        $generated = $this->get('/demo')->headers->get('X-Request-Id');
        $this->assertTrue(Str::isUuid($generated));

        $incoming = (string) Str::uuid();
        $this->get('/demo', ['X-Request-Id' => $incoming])->assertHeader('X-Request-Id', $incoming);

        $this->get('/demo', ['X-Request-Id' => 'not-a-uuid'])->assertHeader('X-Request-Id');
        $this->assertNotSame('not-a-uuid', $this->get('/demo', ['X-Request-Id' => 'not-a-uuid'])->headers->get('X-Request-Id'));
    }
}
