<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Coupon;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_edit_and_archive_a_service_with_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/m/services', [
            'name' => 'Garage Cleaning', 'base_price' => 99.5, 'pricing_method' => 'flat', 'duration_minutes' => 90,
            'included' => "Sweep\nDust", 'faqs' => 'How long?|About 90 minutes.', 'is_active' => true,
            'featured_image' => UploadedFile::fake()->image('garage.jpg'),
        ])->assertRedirect('/admin/m/services');

        $service = Service::where('slug', 'garage-cleaning')->firstOrFail();
        $this->assertSame(['Sweep', 'Dust'], $service->included);
        $this->assertTrue(Storage::disk('public')->exists($service->featured_image));

        $this->actingAs($admin)->put("/admin/m/services/{$service->id}", [
            'name' => 'Garage Cleaning', 'base_price' => 110, 'pricing_method' => 'flat', 'duration_minutes' => 90, 'is_active' => false,
        ])->assertRedirect();
        $this->assertEquals(110, (float) $service->fresh()->base_price);
        $this->assertFalse($service->fresh()->is_active);

        $this->get('/services/garage-cleaning')->assertNotFound();
    }

    public function test_non_image_uploads_are_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/m/services', [
            'name' => 'Bad', 'base_price' => 10, 'pricing_method' => 'flat', 'duration_minutes' => 30,
            'featured_image' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
        ])->assertSessionHasErrors('featured_image');
    }

    public function test_price_changes_in_admin_affect_new_quotes(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create(['base_price' => 80]);

        $this->actingAs($admin)->put("/admin/m/services/{$service->id}", [
            'name' => $service->name, 'base_price' => 95, 'pricing_method' => 'size_based', 'duration_minutes' => 120, 'is_active' => true,
        ]);

        $json = $this->postJson('/api/booking/quote', ['service_id' => $service->id, 'property_type' => 'apartment', 'frequency' => 'one_time'])->json();
        $this->assertEquals(95, $json['total']);
    }

    public function test_pricing_rules_are_editable_via_settings(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create(['base_price' => 80]);

        $this->actingAs($admin)->put('/admin/settings', ['bedroom_rate' => '25', 'tax_rate' => '10'])->assertSessionHas('success');

        $json = $this->postJson('/api/booking/quote', ['service_id' => $service->id, 'property_type' => 'apartment', 'bedrooms' => 2, 'frequency' => 'one_time'])->json();
        $this->assertEquals(105, $json['subtotal']);
        $this->assertEquals(10.5, $json['tax']);
        $this->assertEquals(115.5, $json['total']);
        $this->assertSame('25', Setting::get('bedroom_rate'));
    }

    public function test_coupon_crud_uppercases_code_and_caps_percent(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/m/coupons', ['code' => 'spring15', 'type' => 'percent', 'value' => 15, 'is_active' => true])->assertRedirect();
        $this->assertDatabaseHas('coupons', ['code' => 'SPRING15']);

        $this->actingAs($admin)->post('/admin/m/coupons', ['code' => 'WOW', 'type' => 'percent', 'value' => 150, 'is_active' => true])->assertSessionHasErrors('value');
        $this->assertSame(1, Coupon::count());
    }

    public function test_super_admin_creates_users_but_cannot_remove_the_last_super_admin(): void
    {
        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)->post('/admin/m/users', [
            'name' => 'New Manager', 'email' => 'mgr@example.test', 'role' => 'manager', 'password' => 'LongPassword123', 'is_active' => true,
        ])->assertRedirect('/admin/m/users');
        $mgr = User::where('email', 'mgr@example.test')->firstOrFail();
        $this->assertSame('manager', $mgr->role);
        $this->assertTrue($mgr->must_change_password);

        $this->actingAs($super)->put("/admin/m/users/{$super->id}", [
            'name' => $super->name, 'email' => $super->email, 'role' => 'admin', 'is_active' => true,
        ])->assertStatus(422);
        $this->assertSame('super_admin', $super->fresh()->role);
    }

    public function test_employee_management(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create();

        $this->actingAs($admin)->post('/admin/employees', [
            'name' => 'Cleaner One', 'email' => 'cleaner@example.test', 'password' => 'StrongPass123', 'is_active' => true,
            'working_days' => [1, 2, 3], 'work_start' => '08:00', 'work_end' => '16:00', 'skills' => [$service->id], 'is_available' => true,
        ])->assertRedirect();

        $user = User::where('email', 'cleaner@example.test')->firstOrFail();
        $this->assertSame('staff', $user->role);
        $this->assertSame([1, 2, 3], $user->employeeProfile->working_days);
        $this->actingAs($admin)->get("/admin/employees/{$user->id}")->assertOk();
    }

    public function test_customer_management(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/customers', ['name' => 'Walk In', 'email' => 'walkin@example.test'])->assertRedirect();
        $customer = User::where('email', 'walkin@example.test')->firstOrFail();
        $this->assertSame('customer', $customer->role);

        $this->actingAs($admin)->get('/admin/customers?search=Walk')->assertOk()->assertSee('Walk In', false);
        $this->actingAs($admin)->get("/admin/customers/{$customer->id}")->assertOk();
    }

    public function test_reports_and_csv_export(): void
    {
        $admin = User::factory()->admin()->create();
        \App\Models\Booking::factory()->create(['contact_name' => '=HYPERLINK("evil")']);

        $this->actingAs($admin)->get('/admin/reports?type=bookings&from='.now()->subYear()->format('Y-m-d').'&to='.now()->addYear()->format('Y-m-d'))->assertOk();

        $response = $this->actingAs($admin)->get('/admin/reports/export?type=bookings&from='.now()->subYear()->format('Y-m-d').'&to='.now()->addYear()->format('Y-m-d'));
        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Reference', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv); // formula injection neutralised
    }

    public function test_dashboard_calendar_and_audit_log_load(): void
    {
        $admin = User::factory()->admin()->create();
        AuditLog::record('test.event', null, 'hello');

        foreach (['/admin', '/admin/calendar', '/admin/audit-logs', '/admin/settings', '/admin/m/payments', '/admin/m/invoices', '/admin/m/reviews', '/admin/m/enquiries', '/admin/m/blog', '/admin/m/areas'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_unknown_admin_module_is_404(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/m/nonsense')->assertNotFound();
    }
}
