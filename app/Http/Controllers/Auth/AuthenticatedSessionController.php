<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login', [
            'roles' => UserRole::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'role' => ['required', Rule::enum(UserRole::class)],
            'identity' => [
                'required',
                'string',
                'max:255',
                Rule::when($request->input('role') === UserRole::Admin->value, ['email']),
            ],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $role = UserRole::from($credentials['role']);
        $identityColumn = match ($role) {
            UserRole::Admin => 'email',
            UserRole::Dosen => 'nidn',
            UserRole::Mahasiswa => 'nim',
        };

        if (! Auth::attempt(
            [
                $identityColumn => $credentials['identity'],
                'role' => $role->value,
                'password' => $credentials['password'],
            ],
            $request->boolean('remember'),
        )) {
            return back()
                ->withErrors(['identity' => 'Peran, identitas, atau kata sandi tidak valid.'])
                ->onlyInput('role', 'identity');
        }

        $userRole = UserRole::tryFrom((string) $request->user()?->role);

        if ($userRole !== $role) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['identity' => 'Peran, identitas, atau kata sandi tidak valid.'])
                ->onlyInput('role', 'identity');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
