<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/account')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/staff/login');
        $this->get('/staff')->assertRedirect('/staff/login');
    }

    public function test_customer_cannot_open_admin_or_staff_areas(): void
    {
        $c = User::factory()->customer()->create();

        $this->actingAs($c)->get('/admin')->assertForbidden();
        $this->actingAs($c)->get('/admin/bookings')->assertForbidden();
        $this->actingAs($c)->get('/staff')->assertForbidden();
    }

    public function test_staff_cannot_open_admin_or_customer_areas(): void
    {
        $s = User::factory()->staff()->create();

        $this->actingAs($s)->get('/admin')->assertForbidden();
        $this->actingAs($s)->get('/account')->assertForbidden();
        $this->actingAs($s)->get('/staff')->assertOk();
    }

    public function test_manager_has_limited_admin_access(): void
    {
        $m = User::factory()->manager()->create();

        $this->actingAs($m)->get('/admin')->assertOk();
        $this->actingAs($m)->get('/admin/bookings')->assertOk();
        $this->actingAs($m)->get('/admin/settings')->assertForbidden();
        $this->actingAs($m)->get('/admin/audit-logs')->assertForbidden();
        $this->actingAs($m)->get('/admin/m/users')->assertForbidden();
        $this->actingAs($m)->get('/admin/m/services')->assertForbidden();
        $this->actingAs($m)->get('/admin/m/faqs')->assertOk();
    }

    public function test_only_super_admin_manages_users_and_roles(): void
    {
        $admin = User::factory()->admin()->create();
        $super = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get('/admin/m/users')->assertForbidden();
        $this->actingAs($admin)->get('/admin/settings')->assertOk();
        $this->actingAs($super)->get('/admin/m/users')->assertOk();
    }

    public function test_customer_cannot_view_another_customers_booking(): void
    {
        $owner = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        $booking = Booking::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)->get("/account/bookings/{$booking->reference}")->assertOk();
        $this->actingAs($other)->get("/account/bookings/{$booking->reference}")->assertForbidden();
        $this->actingAs($other)->post("/account/bookings/{$booking->reference}/cancel")->assertForbidden();
        $this->actingAs($other)->get("/book/confirmation/{$booking->reference}")->assertForbidden();
    }

    public function test_staff_only_sees_their_own_jobs(): void
    {
        $mine = User::factory()->staff()->create();
        $other = User::factory()->staff()->create();
        $job = Booking::factory()->status('assigned')->create(['employee_id' => $mine->id]);

        $this->actingAs($mine)->get("/staff/jobs/{$job->reference}")->assertOk();
        $this->actingAs($other)->get("/staff/jobs/{$job->reference}")->assertForbidden();
        $this->actingAs($other)->post("/staff/jobs/{$job->reference}/status", ['status' => 'in_progress'])->assertForbidden();
    }

    public function test_customer_cannot_edit_another_customers_address(): void
    {
        $a = User::factory()->customer()->create();
        $b = User::factory()->customer()->create();
        $addr = $a->addresses()->create(['street' => '1 Main', 'city' => 'Austin', 'state' => 'TX', 'zip' => '78701']);

        $this->actingAs($b)->delete("/account/addresses/{$addr->id}")->assertForbidden();
        $this->assertDatabaseHas('addresses', ['id' => $addr->id]);
    }

    public function test_inactive_user_loses_access_immediately(): void
    {
        $c = User::factory()->customer()->create();
        $c->forceFill(['is_active' => false])->save();

        $this->actingAs($c)->get('/account')->assertForbidden();
    }
}
