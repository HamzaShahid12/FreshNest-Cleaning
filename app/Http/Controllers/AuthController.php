<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Home;
use App\Support\Notify;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    /* ----------------------------- Login ----------------------------- */

    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login', ['portal' => 'customer']);
    }

    public function showStaffLogin(): Response
    {
        return Inertia::render('Auth/Login', ['portal' => 'staff']);
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'portal' => ['nullable', 'in:customer,staff'],
        ]);

        $key = Str::lower($data['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages(['email' => "Too many attempts. Try again in {$seconds} seconds."]);
        }

        $ok = Auth::attempt(
            ['email' => $data['email'], 'password' => $data['password'], 'is_active' => true],
            $request->boolean('remember')
        );

        if (! $ok) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        $user = $request->user();

        // The staff portal is only for staff/admin accounts.
        if (($data['portal'] ?? 'customer') === 'staff' && $user->isCustomer()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw ValidationException::withMessages(['email' => 'This sign-in is for staff only. Customers sign in at /login.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(Home::pathFor($user));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /* --------------------------- Registration -------------------------- */

    public function showRegister(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $user = User::create($data);
        $user->forceFill(['role' => 'customer', 'is_active' => true])->save();

        $user->sendVerificationSafely();
        Notify::user($user, 'welcome', ['name' => $user->name]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account.dashboard')->with('success', 'Welcome to FreshNest! Please verify your email address.');
    }

    /* ------------------------ Forgot / reset password ------------------ */

    public function showForgot(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));

        // Same response whether or not the account exists (no user enumeration).
        return back()->with('success', 'If that email is registered, a reset link is on its way.');
    }

    public function showReset(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'must_change_password' => false])
                ->setRememberToken(Str::random(60));
            $user->save();
            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return redirect()->route('login')->with('success', 'Your password has been reset. You can sign in now.');
    }

    /* --------------------------- Verification ------------------------- */

    public function verifyNotice(Request $request): Response|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect(Home::pathFor($request->user()))
            : Inertia::render('Auth/VerifyEmail');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail() && $request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect(Home::pathFor($request->user()))->with('success', 'Email verified. Thank you!');
    }

    public function resendVerification(Request $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return back()->with('success', 'Verification link sent.');
    }

    /* ---------------------- Forced password change -------------------- */

    public function showForcePassword(): Response
    {
        return Inertia::render('Auth/ForcePassword');
    }

    public function forcePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', PasswordRule::min(10)->letters()->mixedCase()->numbers()],
        ]);

        $request->user()->forceFill(['password' => $data['password'], 'must_change_password' => false])->save();
        AuditLog::record('user.password_changed', $request->user(), 'Password changed after forced reset');

        return redirect(Home::pathFor($request->user()))->with('success', 'Password updated.');
    }
}
