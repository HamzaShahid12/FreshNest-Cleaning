<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\TemplatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BookingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_confirms_assigns_and_staff_completes_a_job(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->staff()->create();
        $booking = Booking::factory()->create();

        $this->actingAs($admin)->post("/admin/bookings/{$booking->reference}/status", ['status' => 'confirmed'])->assertSessionHas('success');
        $this->actingAs($admin)->post("/admin/bookings/{$booking->reference}/assign", ['employee_id' => $staff->id])->assertSessionHas('success');

        $booking->refresh();
        $this->assertSame('assigned', $booking->status);
        $this->assertSame($staff->id, $booking->employee_id);
        Notification::assertSentTo($booking->user, TemplatedNotification::class, fn ($n) => $n->event === 'booking_confirmed');
        Notification::assertSentTo($staff, TemplatedNotification::class, fn ($n) => $n->event === 'job_assigned');

        $this->actingAs($staff)->post("/staff/jobs/{$booking->reference}/status", ['status' => 'in_progress'])->assertSessionHas('success');
        $this->actingAs($staff)->post("/staff/jobs/{$booking->reference}/status", ['status' => 'completed'])->assertSessionHas('success');

        $this->assertSame('completed', $booking->fresh()->status);
        $this->assertNotNull($booking->fresh()->completed_at);
        Notification::assertSentTo($booking->user, TemplatedNotification::class, fn ($n) => $n->event === 'booking_completed');
        $this->assertDatabaseCount('booking_status_histories', 4);
    }

    public function test_invalid_status_transitions_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $booking = Booking::factory()->status('completed')->create();

        $this->actingAs($admin)->post("/admin/bookings/{$booking->reference}/status", ['status' => 'pending'])->assertSessionHasErrors('status');
        $this->assertSame('completed', $booking->fresh()->status);
    }

    public function test_cleaner_cannot_be_double_booked(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->staff()->create();

        $first = Booking::factory()->status('confirmed')->create();
        $second = Booking::factory()->status('confirmed')->create([
            'scheduled_at' => $first->scheduled_at->copy()->addHour(), 'ends_at' => $first->ends_at->copy()->addHour(),
        ]);

        $this->actingAs($admin)->post("/admin/bookings/{$first->reference}/assign", ['employee_id' => $staff->id])->assertSessionHas('success');
        $this->actingAs($admin)->post("/admin/bookings/{$second->reference}/assign", ['employee_id' => $staff->id])->assertSessionHasErrors('employee_id');
        $this->assertNull($second->fresh()->employee_id);
    }

    public function test_customer_can_cancel_outside_the_cancellation_window(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create(['user_id' => $customer->id]); // 5 days away

        $this->actingAs($customer)->post("/account/bookings/{$booking->reference}/cancel", ['reason' => 'Plans changed'])->assertSessionHas('success');
        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_customer_cannot_cancel_inside_the_cancellation_window(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create([
            'user_id' => $customer->id, 'scheduled_at' => now()->addHours(5), 'ends_at' => now()->addHours(7),
        ]);

        $this->actingAs($customer)->post("/account/bookings/{$booking->reference}/cancel")->assertSessionHas('error');
        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_cancellation_window_is_configurable(): void
    {
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create(['user_id' => $customer->id, 'scheduled_at' => now()->addHours(5), 'ends_at' => now()->addHours(7)]);
        Setting::put('cancellation_hours', '2');

        $this->actingAs($customer)->post("/account/bookings/{$booking->reference}/cancel")->assertSessionHas('success');
        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_customer_reschedules_to_an_available_slot(): void
    {
        User::factory()->staff()->create();
        $customer = User::factory()->customer()->create();
        $booking = Booking::factory()->create(['user_id' => $customer->id]);
        $newDate = now()->addDays(8)->format('Y-m-d');

        $this->actingAs($customer)->post("/account/bookings/{$booking->reference}/reschedule", ['date' => $newDate, 'time' => '13:00'])->assertSessionHas('success');

        $this->assertSame($newDate.' 13:00', $booking->fresh()->scheduled_at->format('Y-m-d H:i'));
        $this->assertSame($newDate.' 15:00', $booking->fresh()->ends_at->format('Y-m-d H:i'));
    }

    public function test_reschedule_into_a_full_slot_is_refused(): void
    {
        User::factory()->staff()->create();
        $customer = User::factory()->customer()->create();
        $date = now()->addDays(8);
        Booking::factory()->create(['scheduled_at' => $date->copy()->setTime(13, 0), 'ends_at' => $date->copy()->setTime(15, 0)]);
        $booking = Booking::factory()->create(['user_id' => $customer->id]);

        $this->actingAs($customer)->post("/account/bookings/{$booking->reference}/reschedule", ['date' => $date->format('Y-m-d'), 'time' => '13:00'])->assertSessionHasErrors('time');
    }

    public function test_review_only_after_completion_and_once(): void
    {
        $customer = User::factory()->customer()->create();
        $pending = Booking::factory()->create(['user_id' => $customer->id]);
        $done = Booking::factory()->status('completed')->create(['user_id' => $customer->id]);

        $this->actingAs($customer)->post("/account/bookings/{$pending->reference}/review", ['rating' => 5])->assertForbidden();

        $this->actingAs($customer)->post("/account/bookings/{$done->reference}/review", ['rating' => 5, 'comment' => 'Great'])->assertSessionHas('success');
        $this->actingAs($customer)->post("/account/bookings/{$done->reference}/review", ['rating' => 1])->assertSessionHas('error');

        $this->assertDatabaseHas('reviews', ['booking_id' => $done->id, 'rating' => 5, 'is_approved' => false]);
        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_staff_only_see_customer_address_once_assigned(): void
    {
        $staff = User::factory()->staff()->create();
        $job = Booking::factory()->status('assigned')->create(['employee_id' => $staff->id, 'street' => '77 Secret Lane']);

        $this->actingAs($staff)->get("/staff/jobs/{$job->reference}")->assertOk()->assertSee('77 Secret Lane', false);
    }

    public function test_booking_list_filters_and_search(): void
    {
        $admin = User::factory()->admin()->create();
        Booking::factory()->create(['contact_name' => 'Findable Person']);
        Booking::factory()->status('confirmed')->create(['contact_name' => 'Other Person']);

        $this->actingAs($admin)->get('/admin/bookings?search=Findable')->assertOk()->assertSee('Findable Person', false)->assertDontSee('Other Person', false);
        $this->actingAs($admin)->get('/admin/bookings?status=confirmed')->assertOk()->assertSee('Other Person', false)->assertDontSee('Findable Person', false);
    }
}
