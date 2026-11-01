<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class FirstPasswordChangeController extends Controller
{
    /**
     * Show the first password change form.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.first-password-change', [
            'user' => $user,
        ]);
    }

    /**
     * Update the user password on first login.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed', Password::defaults()],
        ]);

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ])->save();

        return redirect()->route('dashboard')
            ->with('success', 'Kata sandi berhasil diperbarui. Selamat datang di Omni-Presence!');
    }
}
