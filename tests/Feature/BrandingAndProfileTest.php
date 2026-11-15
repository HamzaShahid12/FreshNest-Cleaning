<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandingAndProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_default_banners_are_shared_with_every_page(): void
    {
        $this->get('/')->assertOk()->assertInertia(fn ($page) => $page
            ->where('brand.logo', null)
            ->where('brand.banners.home', '/images/banners/home.svg')
            ->where('brand.banners.about', '/images/banners/about.svg'));
    }

    public function test_admin_can_upload_and_reset_the_logo_and_a_banner(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/branding/logo', ['image' => UploadedFile::fake()->image('logo.png', 300, 100)])->assertSessionHasNoErrors();
        $logo = Setting::get('brand_logo');
        $this->assertNotEmpty($logo);
        Storage::disk('public')->assertExists($logo);

        $this->actingAs($admin)->post('/admin/branding/home', ['image' => UploadedFile::fake()->image('hero.jpg', 1600, 600)])->assertSessionHasNoErrors();
        $banner = Setting::get('brand_banner_home');
        Storage::disk('public')->assertExists($banner);

        $this->get('/')->assertInertia(fn ($page) => $page
            ->where('brand.logo', '/storage/'.$logo)
            ->where('brand.banners.home', '/storage/'.$banner));

        // Replacing deletes the old file.
        $this->actingAs($admin)->post('/admin/branding/home', ['image' => UploadedFile::fake()->image('hero2.jpg', 1600, 600)]);
        Storage::disk('public')->assertMissing($banner);

        $this->actingAs($admin)->delete('/admin/branding/home')->assertRedirect();
        $this->assertSame('', (string) Setting::get('brand_banner_home'));
        $this->get('/')->assertInertia(fn ($page) => $page->where('brand.banners.home', '/images/banners/home.svg'));
    }

    public function test_branding_rejects_svg_non_images_oversized_files_and_unknown_keys(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/branding/logo', ['image' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml')])->assertSessionHasErrors('image');
        $this->actingAs($admin)->post('/admin/branding/logo', ['image' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')])->assertSessionHasErrors('image');
        $this->actingAs($admin)->post('/admin/branding/home', ['image' => UploadedFile::fake()->create('huge.jpg', 5000, 'image/jpeg')])->assertSessionHasErrors('image');
        $this->actingAs($admin)->post('/admin/branding/not_a_page', ['image' => UploadedFile::fake()->image('a.png')])->assertNotFound();
    }

    public function test_only_admins_can_change_branding(): void
    {
        $file = ['image' => UploadedFile::fake()->image('logo.png')];

        $this->post('/admin/branding/logo', $file)->assertRedirect('/login');
        $this->actingAs(User::factory()->customer()->create())->post('/admin/branding/logo', $file)->assertForbidden();
        $this->actingAs(User::factory()->manager()->create())->post('/admin/branding/logo', $file)->assertForbidden();
        $this->actingAs(User::factory()->manager()->create())->get('/admin/branding')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/admin/branding')->assertOk();
    }

    public function test_every_role_can_upload_replace_and_remove_a_profile_photo(): void
    {
        foreach (['customer', 'staff', 'manager', 'admin'] as $role) {
            $user = User::factory()->{$role}()->create();

            $this->actingAs($user)->post('/profile/avatar', ['avatar' => UploadedFile::fake()->image('me.jpg', 300, 300)])->assertSessionHasNoErrors();
            $first = $user->fresh()->avatar;
            Storage::disk('public')->assertExists($first);
            $this->assertSame('/storage/'.$first, $user->fresh()->avatarUrl());

            $this->actingAs($user)->post('/profile/avatar', ['avatar' => UploadedFile::fake()->image('me2.png', 300, 300)]);
            Storage::disk('public')->assertMissing($first);

            $this->actingAs($user)->delete('/profile/avatar')->assertRedirect();
            $this->assertNull($user->fresh()->avatar);
        }
    }

    public function test_avatar_is_shared_to_the_header_and_validated(): void
    {
        $this->post('/profile/avatar', ['avatar' => UploadedFile::fake()->image('me.jpg')])->assertRedirect('/login');

        $user = User::factory()->customer()->create();
        $this->actingAs($user)->post('/profile/avatar', ['avatar' => UploadedFile::fake()->image('me.jpg')]);

        $this->actingAs($user->fresh())->get('/account')->assertInertia(fn ($page) => $page->where('auth.user.avatar_url', '/storage/'.$user->fresh()->avatar));

        $this->actingAs($user)->post('/profile/avatar', ['avatar' => UploadedFile::fake()->create('x.svg', 5, 'image/svg+xml')])->assertSessionHasErrors('avatar');
        $this->actingAs($user)->post('/profile/avatar', ['avatar' => UploadedFile::fake()->create('big.jpg', 3000, 'image/jpeg')])->assertSessionHasErrors('avatar');
    }

    public function test_staff_and_admin_profile_pages_and_updates(): void
    {
        $staff = User::factory()->staff()->create();
        $this->actingAs($staff)->get('/staff/profile')->assertOk();
        $this->actingAs($staff)->put('/profile', ['name' => 'New Name', 'phone' => '555 0101'])->assertSessionHasNoErrors();
        $this->assertSame('New Name', $staff->fresh()->name);

        $this->actingAs(User::factory()->admin()->create())->get('/admin/profile')->assertOk();
        $this->actingAs(User::factory()->customer()->create())->get('/admin/profile')->assertForbidden();
        $this->actingAs(User::factory()->customer()->create())->get('/staff/profile')->assertForbidden();
    }

    public function test_admin_can_set_the_business_location_on_the_map(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put('/admin/settings', ['business_address' => 'Mall Road, Lahore, Pakistan', 'business_lat' => '31.5204', 'business_lng' => '74.3587'])
            ->assertSessionHasNoErrors();

        $this->get('/contact')->assertOk()->assertInertia(fn ($page) => $page->where('site.lat', 31.5204)->where('site.lng', 74.3587));

        $this->actingAs($admin)->put('/admin/settings', ['business_lat' => '120', 'business_lng' => '74'])->assertSessionHasErrors('business_lat');
        $this->actingAs($admin)->put('/admin/settings', ['business_lat' => '', 'business_lng' => ''])->assertSessionHasNoErrors();
        $this->get('/contact')->assertInertia(fn ($page) => $page->where('site.lat', null));
    }
}
