<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function create(): View
    {
        return view('users.create', [
            'roles' => UserRole::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $role = $request->input('role');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'email' => [
                'exclude_unless:role,'.UserRole::Admin->value,
                'required',
                'email',
                'max:255',
                Rule::unique(User::class, 'email'),
            ],
            'nidn' => [
                'exclude_unless:role,'.UserRole::Dosen->value,
                'required',
                'string',
                'regex:/\A[0-9]{6}\z/',
                Rule::unique(User::class, 'nidn'),
            ],
            'nim' => [
                'exclude_unless:role,'.UserRole::Mahasiswa->value,
                'required',
                'string',
                'regex:/\A[0-9]{10}\z/',
                Rule::unique(User::class, 'nim'),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $role === UserRole::Admin->value ? $validated['email'] : null,
            'nidn' => $role === UserRole::Dosen->value ? $validated['nidn'] : null,
            'nim' => $role === UserRole::Mahasiswa->value ? $validated['nim'] : null,
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', Rule::enum(UserRole::class)],
        ]);

        $search = trim($filters['search'] ?? '');
        $selectedRole = $filters['role'] ?? null;

        $users = User::query()
            ->select(['id', 'name', 'email', 'nidn', 'nim', 'role'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('nidn', 'like', "%{$search}%")
                        ->orWhere('nim', 'like', "%{$search}%");
                });
            })
            ->when($selectedRole !== null, function (Builder $query) use ($selectedRole): void {
                $query->where('role', $selectedRole);
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        $roles = collect(UserRole::cases())
            ->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->name])
            ->all();

        return view('users.index', [
            'users' => $users,
            'search' => $search,
            'selectedRole' => $selectedRole,
            'roles' => $roles,
        ]);
    }
}
