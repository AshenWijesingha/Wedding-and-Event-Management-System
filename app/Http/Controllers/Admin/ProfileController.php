<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile/Edit', [
            'profile' => [
                'id'              => $user->id,
                'name'            => $user->name,
                'email'           => $user->email,
                'phone'           => $user->phone,
                'avatar'          => $user->avatar_url,
                'role'            => $user->role,
                'email_verified'  => ! is_null($user->email_verified_at),
            ],
            'status' => $request->session()->get('status'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'   => 'required|string|max:255',
            'email'  => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone'  => 'nullable|string|max:50',
            'avatar' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:2048',
            'remove_avatar' => 'nullable|boolean',
        ]);

        $user->name  = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;

        if ($request->boolean('remove_avatar')) {
            $this->deleteStoredAvatar($user->avatar);
            $user->avatar = null;
        }

        if ($request->hasFile('avatar')) {
            $this->deleteStoredAvatar($user->avatar);
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        if ($user->email !== $user->getOriginal('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => $request->password,
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    /**
     * Re-send the email verification link to the current user.
     */
    public function sendVerification(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return back()->with('success', 'Your email is already verified.');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'A fresh verification link has been sent to your email.');
    }

    /**
     * Delete a previously uploaded avatar file (ignores external URLs).
     */
    private function deleteStoredAvatar(?string $avatar): void
    {
        if ($avatar && ! Str::startsWith($avatar, ['http://', 'https://', '//'])) {
            Storage::disk('public')->delete($avatar);
        }
    }
}
