<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\CourseSection;
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
                'regex:/\A[0-9]{10}\z/',
                Rule::unique(User::class, 'nidn'),
            ],
            'nim' => [
                'exclude_unless:role,'.UserRole::Mahasiswa->value,
                'required',
                'string',
                'regex:/\A[0-9]{10}\z/',
                Rule::unique(User::class, 'nim'),
            ],
            'password' => [
                'exclude_unless:role,'.UserRole::Admin->value,
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $defaultPassword = match ($role) {
            UserRole::Mahasiswa->value => $validated['nim'],
            UserRole::Dosen->value => $validated['nidn'],
            default => $validated['password'],
        };

        $mustChangePassword = match ($role) {
            UserRole::Mahasiswa->value, UserRole::Dosen->value => true,
            default => false,
        };

        User::create([
            'name' => $validated['name'],
            'email' => $role === UserRole::Admin->value ? $validated['email'] : null,
            'nidn' => $role === UserRole::Dosen->value ? $validated['nidn'] : null,
            'nim' => $role === UserRole::Mahasiswa->value ? $validated['nim'] : null,
            'role' => $validated['role'],
            'password' => Hash::make($defaultPassword),
            'must_change_password' => $mustChangePassword,
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('users.edit', [
            'user' => $user,
            'roles' => UserRole::cases(),
        ]);
    }

    public function show(User $user): View
    {
        $user->load(['enrollments.courseSection.course']);

        return view('users.show', [
            'user' => $user,
            'roleLabel' => UserRole::tryFrom((string) $user->role)?->name ?? 'Belum ditentukan',
        ]);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->is($user)) {
            return back()->withErrors([
                'delete' => 'Anda tidak dapat menghapus akun sendiri.',
            ]);
        }

        if ($user->role !== UserRole::Mahasiswa->value) {
            return back()->withErrors([
                'delete' => 'Hanya data mahasiswa tanpa riwayat absensi yang dapat dihapus.',
            ]);
        }

        if ($user->attendanceRecords()->exists()) {
            return back()->withErrors([
                'delete' => 'Data mahasiswa tidak dapat dihapus karena sudah memiliki riwayat presensi. Silakan nonaktifkan akun mahasiswa.',
            ]);
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Data mahasiswa berhasil dihapus.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $isActive = (bool) $validated['is_active'];

        if (! $isActive && $request->user()?->is($user)) {
            return back()->withErrors([
                'is_active' => 'Anda tidak dapat menonaktifkan akun sendiri.',
            ]);
        }

        $user->is_active = $isActive;
        $user->save();

        return redirect()
            ->route('users.index')
            ->with('success', $isActive
                ? 'Akun pengguna berhasil diaktifkan.'
                : 'Akun pengguna berhasil dinonaktifkan.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'email' => [
                'exclude_unless:role,'.UserRole::Admin->value,
                'required',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($user),
            ],
            'nidn' => [
                'exclude_unless:role,'.UserRole::Dosen->value,
                'required',
                'string',
                'regex:/\A[0-9]{10}\z/',
                Rule::unique(User::class, 'nidn')->ignore($user),
            ],
            'nim' => [
                'exclude_unless:role,'.UserRole::Mahasiswa->value,
                'required',
                'string',
                'regex:/\A[0-9]{10}\z/',
                Rule::unique(User::class, 'nim')->ignore($user),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $role = $validated['role'];

        $user->fill([
            'name' => $validated['name'],
            'email' => $role === UserRole::Admin->value ? $validated['email'] : null,
            'nidn' => $role === UserRole::Dosen->value ? $validated['nidn'] : null,
            'nim' => $role === UserRole::Mahasiswa->value ? $validated['nim'] : null,
            'role' => $validated['role'],
        ]);

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()
            ->route('users.index')
            ->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'aktif', 'nonaktif', '1', '0'])],
            'course_section_id' => ['nullable', 'integer'],
        ]);

        $search = trim($filters['search'] ?? '');
        $selectedRole = $filters['role'] ?? null;
        $selectedStatus = $filters['status'] ?? null;
        $selectedCourseSectionId = isset($filters['course_section_id']) ? (int) $filters['course_section_id'] : null;

        $users = User::query()
            ->select(['id', 'name', 'email', 'nidn', 'nim', 'role', 'is_active'])
            ->with([
                'enrollments' => fn ($query) => $query
                    ->with('courseSection.course:id,code,name')
                    ->orderBy('id'),
            ])
            ->withCount('attendanceRecords')
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
            ->when($selectedStatus !== null && $selectedStatus !== '', function (Builder $query) use ($selectedStatus): void {
                $isActive = in_array($selectedStatus, ['active', 'aktif', '1'], true);
                $query->where('is_active', $isActive);
            })
            ->when($selectedCourseSectionId !== null, function (Builder $query) use ($selectedCourseSectionId): void {
                $query->whereHas('enrollments', function (Builder $enrollmentQuery) use ($selectedCourseSectionId): void {
                    $enrollmentQuery->where('course_section_id', $selectedCourseSectionId);
                });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        $roles = collect(UserRole::cases())
            ->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->name])
            ->all();

        $courseSections = CourseSection::query()
            ->with('course:id,code,name')
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'course_id', 'name']);

        return view('users.index', [
            'users' => $users,
            'search' => $search,
            'selectedRole' => $selectedRole,
            'selectedStatus' => $selectedStatus,
            'selectedCourseSectionId' => $selectedCourseSectionId,
            'courseSections' => $courseSections,
            'roles' => $roles,
        ]);
    }
}
