<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_and_is_always_a_customer(): void
    {
        $response = $this->post('/register', [
            'name' => 'Jane Doe', 'email' => 'jane@example.test', 'password' => 'Secret123', 'password_confirmation' => 'Secret123',
            'role' => 'super_admin', // must be ignored (mass-assignment protection)
        ]);

        $response->assertRedirect('/account');
        $user = User::where('email', 'jane@example.test')->first();
        $this->assertSame('customer', $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_validates_input(): void
    {
        User::factory()->customer()->create(['email' => 'taken@example.test']);

        $this->post('/register', ['name' => '', 'email' => 'taken@example.test', 'password' => 'short', 'password_confirmation' => 'nope'])
            ->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_login_and_logout(): void
    {
        $user = User::factory()->customer()->create(['email' => 'c@example.test']);

        $this->post('/login', ['email' => 'c@example.test', 'password' => 'Password123'])->assertRedirect('/account');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_wrong_password_and_inactive_accounts_cannot_log_in(): void
    {
        $user = User::factory()->customer()->create(['email' => 'c@example.test']);
        $this->post('/login', ['email' => 'c@example.test', 'password' => 'wrong'])->assertSessionHasErrors('email');

        $user->forceFill(['is_active' => false])->save();
        $this->post('/login', ['email' => 'c@example.test', 'password' => 'Password123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->customer()->create(['email' => 'c@example.test']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'c@example.test', 'password' => 'bad']);
        }
        $this->post('/login', ['email' => 'c@example.test', 'password' => 'Password123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_customers_cannot_use_the_staff_login(): void
    {
        User::factory()->customer()->create(['email' => 'c@example.test']);

        $this->post('/login', ['email' => 'c@example.test', 'password' => 'Password123', 'portal' => 'staff'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_staff_and_admins_land_in_their_own_areas(): void
    {
        User::factory()->staff()->create(['email' => 's@example.test']);
        User::factory()->admin()->create(['email' => 'a@example.test']);

        $this->post('/login', ['email' => 's@example.test', 'password' => 'Password123', 'portal' => 'staff'])->assertRedirect('/staff');
        $this->post('/logout');
        $this->post('/login', ['email' => 'a@example.test', 'password' => 'Password123', 'portal' => 'staff'])->assertRedirect('/admin');
    }

    public function test_password_reset_flow(): void
    {
        Notification::fake();
        $user = User::factory()->customer()->create(['email' => 'c@example.test']);

        $this->post('/forgot-password', ['email' => 'c@example.test'])->assertSessionHas('success');
        // Unknown e-mail gives the same answer (no account enumeration).
        $this->post('/forgot-password', ['email' => 'nobody@example.test'])->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPassword::class, function ($n) use ($user) {
            $this->post('/reset-password', [
                'token' => $n->token, 'email' => $user->email, 'password' => 'NewSecret123', 'password_confirmation' => 'NewSecret123',
            ])->assertRedirect('/login');

            return true;
        });

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewSecret123', $user->fresh()->password));
    }

    public function test_forced_password_change_blocks_other_pages(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $admin->forceFill(['must_change_password' => true])->save();

        $this->actingAs($admin)->get('/admin')->assertRedirect(route('password.force'));

        $this->actingAs($admin)->post('/password/change', [
            'current_password' => 'Password123', 'password' => 'BrandNewPass123', 'password_confirmation' => 'BrandNewPass123',
        ])->assertRedirect('/admin');

        $this->assertFalse($admin->fresh()->must_change_password);
        $this->actingAs($admin->fresh())->get('/admin')->assertOk();
    }
}
