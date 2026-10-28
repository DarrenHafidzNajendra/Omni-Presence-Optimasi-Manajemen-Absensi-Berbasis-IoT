<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $userStatistics = null;

        if (request()->user()?->role === UserRole::Admin->value) {
            $userCountsByRole = User::query()
                ->selectRaw('role, COUNT(*) as aggregate')
                ->whereIn('role', array_map(
                    fn (UserRole $role): string => $role->value,
                    UserRole::cases(),
                ))
                ->groupBy('role')
                ->pluck('aggregate', 'role');

            $userStatistics = [
                'total' => User::query()->count(),
                'admin' => (int) $userCountsByRole->get(UserRole::Admin->value, 0),
                'dosen' => (int) $userCountsByRole->get(UserRole::Dosen->value, 0),
                'mahasiswa' => (int) $userCountsByRole->get(UserRole::Mahasiswa->value, 0),
            ];
        }

        return view('dashboard', compact('userStatistics'));
    }
}
