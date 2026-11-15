<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;
use Inertia\Response;

/** Profile page for staff and admin users, plus the profile photo endpoints used by every role. */
class ProfileController extends Controller
{
    private function data(Request $request): array
    {
        $u = $request->user();

        return ['name' => $u->name, 'email' => $u->email, 'phone' => $u->phone, 'avatar_url' => $u->avatarUrl(), 'role_label' => $u->roleLabel()];
    }

    public function staff(Request $request): Response
    {
        return Inertia::render('Staff/Profile', ['profile' => $this->data($request)]);
    }

    public function admin(Request $request): Response
    {
        return Inertia::render('Admin/Profile', ['profile' => $this->data($request)]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);
        $request->user()->update($data);

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', PasswordRule::min(10)->letters()->mixedCase()->numbers()],
        ]);
        $request->user()->forceFill(['password' => $data['password'], 'must_change_password' => false])->save();
        AuditLog::record('user.password_changed', $request->user(), 'Password changed from profile');

        return back()->with('success', 'Password updated.');
    }

    public function avatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'avatar.max' => 'The photo is too large (maximum 2 MB).',
            'avatar.mimes' => 'Please upload a JPG, PNG or WebP photo.',
            'avatar.image' => 'The file must be an image.',
        ]);

        $user = $request->user();
        $old = $user->avatar;
        $path = $request->file('avatar')->store('avatars', 'public'); // random name
        $user->forceFill(['avatar' => $path])->save();
        if ($old) {
            Storage::disk('public')->delete($old);
        }

        return back()->with('success', 'Profile photo updated.');
    }

    public function removeAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->forceFill(['avatar' => null])->save();
        }

        return back()->with('success', 'Profile photo removed.');
    }
}
