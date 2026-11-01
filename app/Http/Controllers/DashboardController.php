<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\CourseSection;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = request()->user();
        $userStatistics = null;
        $attendanceStatistics = null;
        $recentActivities = collect();
        $activeSessions = collect();
        $myCourseSections = collect();
        $studentEnrollments = collect();
        $availableCourseSections = collect();

        // 1. User & Academic Statistics (Admin only)
        $academicStatistics = null;
        if ($user?->role === UserRole::Admin->value) {
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

            $academicStatistics = [
                'course_sections' => CourseSection::query()->where('is_active', true)->count(),
                'open_sessions' => AttendanceSession::query()->where('status', 'terbuka')->count(),
            ];
        }

        // 2. Attendance Statistics (Admin & Dosen)
        $today = Carbon::today('Asia/Jakarta');
        $attendanceBaseQuery = AttendanceRecord::query();

        if ($user?->role === UserRole::Dosen->value) {
            $attendanceBaseQuery->whereHas('attendanceSession.courseSection.lecturers', function ($q) use ($user): void {
                $q->where('user_id', $user->id);
            });
        }

        $recordsToday = (clone $attendanceBaseQuery)
            ->where(function ($query) use ($today): void {
                $query->whereDate('checked_in_at', $today)
                    ->orWhereHas('attendanceSession', function ($sessionQuery) use ($today): void {
                        $sessionQuery->whereDate('starts_at', $today);
                    });
            })
            ->get();

        if ($recordsToday->isNotEmpty()) {
            $attendanceStatistics = [
                'scope' => 'today',
                'total' => $recordsToday->count(),
                'hadir' => $recordsToday->where('status', 'hadir')->count(),
                'terlambat' => $recordsToday->where('status', 'terlambat')->count(),
                'izin' => $recordsToday->where('status', 'izin')->count(),
                'sakit' => $recordsToday->where('status', 'sakit')->count(),
            ];
        } else {
            $allRecords = (clone $attendanceBaseQuery)->get();
            if ($allRecords->isNotEmpty()) {
                $attendanceStatistics = [
                    'scope' => 'cumulative',
                    'total' => $allRecords->count(),
                    'hadir' => $allRecords->where('status', 'hadir')->count(),
                    'terlambat' => $allRecords->where('status', 'terlambat')->count(),
                    'izin' => $allRecords->where('status', 'izin')->count(),
                    'sakit' => $allRecords->where('status', 'sakit')->count(),
                ];
            }
        }

        // 3. Recent Activities (Admin & Dosen)
        $activitiesQuery = AttendanceRecord::query()
            ->with(['student', 'attendanceSession.courseSection.course'])
            ->latest('checked_in_at')
            ->latest('id')
            ->limit(5);

        if ($user?->role === UserRole::Dosen->value) {
            $activitiesQuery->whereHas('attendanceSession.courseSection.lecturers', function ($q) use ($user): void {
                $q->where('user_id', $user->id);
            });
        }

        $recentActivities = $activitiesQuery->get();

        // 4. Active & Upcoming Sessions Today (Admin & Dosen)
        $sessionsQuery = AttendanceSession::query()
            ->with(['courseSection.course'])
            ->whereDate('starts_at', $today)
            ->orderBy('starts_at');

        if ($user?->role === UserRole::Dosen->value) {
            $sessionsQuery->whereHas('courseSection.lecturers', function ($q) use ($user): void {
                $q->where('user_id', $user->id);
            });
        }

        $activeSessions = $sessionsQuery->get();

        // 5. My Course Sections (Dosen only)
        if ($user?->role === UserRole::Dosen->value) {
            $myCourseSections = CourseSection::query()
                ->where('is_active', true)
                ->whereHas('lecturers', function ($q) use ($user): void {
                    $q->where('user_id', $user->id);
                })
                ->with(['course', 'schedules'])
                ->withCount([
                    'enrollments as active_students_count' => function ($q): void {
                        $q->where('status', 'aktif');
                    },
                ])
                ->get();
        }

        // 6. Student Self-Enrollment & Enrolled Sections (Mahasiswa only)
        if ($user?->role === UserRole::Mahasiswa->value) {
            $studentEnrollments = $user->enrollments()
                ->with(['courseSection.course', 'courseSection.schedules', 'courseSection.academicPeriod'])
                ->orderByDesc('id')
                ->get();

            $enrolledSectionIds = $studentEnrollments->pluck('course_section_id')->all();

            $availableCourseSections = CourseSection::query()
                ->where('is_active', true)
                ->whereHas('course', function ($q): void {
                    $q->where('is_active', true);
                })
                ->where(function ($q): void {
                    $q->whereNull('academic_period_id')
                        ->orWhereHas('academicPeriod', function ($periodQuery): void {
                            $periodQuery->where('is_active', true);
                        });
                })
                ->whereNotIn('id', $enrolledSectionIds)
                ->with(['course:id,code,name', 'schedules', 'academicPeriod:id,name,is_active'])
                ->orderBy('name')
                ->get();
        }

        return view('dashboard', [
            'userStatistics' => $userStatistics,
            'academicStatistics' => $academicStatistics,
            'attendanceStatistics' => $attendanceStatistics,
            'recentActivities' => $recentActivities,
            'activeSessions' => $activeSessions,
            'myCourseSections' => $myCourseSections,
            'studentEnrollments' => $studentEnrollments,
            'availableCourseSections' => $availableCourseSections,
        ]);
    }
}
