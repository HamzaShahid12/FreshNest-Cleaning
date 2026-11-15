<?php

namespace Tests\Feature;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render(): void
    {
        Service::factory()->create(['slug' => 'standard-house-cleaning', 'is_featured' => true]);

        foreach (['/', '/about', '/services', '/pricing', '/gallery', '/reviews', '/areas', '/contact', '/faq', '/blog', '/legal/privacy', '/legal/terms', '/legal/cancellation', '/book', '/login', '/register', '/staff/login'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/services/standard-house-cleaning')->assertOk();
    }

    public function test_unknown_pages_return_404_and_unpublished_services_are_hidden(): void
    {
        Service::factory()->create(['slug' => 'hidden', 'is_active' => false]);

        $this->get('/services/hidden')->assertNotFound();
        $this->get('/legal/nope')->assertNotFound();
    }

    public function test_sitemap_and_robots(): void
    {
        Service::factory()->create(['slug' => 'standard-house-cleaning']);

        $this->get('/sitemap.xml')->assertOk()->assertSee('/services/standard-house-cleaning', false);
    }

    public function test_contact_form_stores_enquiry_and_honeypot_blocks_bots(): void
    {
        $this->post('/contact', ['name' => 'Ann', 'email' => 'ann@example.test', 'subject' => 'Hello', 'message' => 'I would like a quote please.'])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('contact_enquiries', ['email' => 'ann@example.test']);

        $this->post('/contact', ['name' => 'Bot', 'email' => 'bot@example.test', 'subject' => 'x', 'message' => 'spam spam spam', 'website' => 'http://spam'])
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('contact_enquiries', ['email' => 'bot@example.test']);
    }

    public function test_newsletter_subscription_is_unique(): void
    {
        $this->post('/newsletter', ['email' => 'Fan@Example.test']);
        $this->post('/newsletter', ['email' => 'fan@example.test']);

        $this->assertDatabaseCount('newsletter_subscribers', 1);
    }

    public function test_area_check_accepts_a_city_or_a_postal_code(): void
    {
        \App\Models\ServiceArea::create(['name' => 'Austin', 'slug' => 'austin', 'zip_codes' => '78701', 'is_active' => true]);
        \App\Models\ServiceArea::create(['name' => 'Lahore', 'slug' => 'lahore', 'is_active' => true]);

        $this->getJson('/api/check-area?q=lahore')->assertOk()->assertJson(['served' => true]);
        $this->getJson('/api/check-area?q=78701')->assertOk()->assertJson(['served' => true]);
        $this->getJson('/api/check-area?q=Paris')->assertOk()->assertJson(['served' => false]);
        $this->getJson('/api/check-area?q=a')->assertStatus(422);
    }

    public function test_zip_check_uses_service_areas(): void
    {
        \App\Models\ServiceArea::create(['name' => 'Austin', 'slug' => 'austin', 'state' => 'TX', 'zip_codes' => '78701,78702', 'is_active' => true]);

        $this->getJson('/api/check-zip?zip=78701')->assertOk()->assertJson(['served' => true]);
        $this->getJson('/api/check-zip?zip=90210')->assertOk()->assertJson(['served' => false]);
        $this->getJson('/api/check-zip?zip=abc')->assertStatus(422);
    }
}
