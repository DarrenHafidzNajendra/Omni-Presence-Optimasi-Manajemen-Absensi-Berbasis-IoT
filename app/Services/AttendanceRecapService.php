<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\CourseSection;
use Illuminate\Support\Collection;

class AttendanceRecapService
{
    /**
     * Compute cumulative attendance recap for a course section.
     *
     * @return array{
     *     courseSection: CourseSection,
     *     totalQualifyingSessions: int,
     *     totalActiveStudents: int,
     *     overallPercentage: float|null,
     *     studentRecaps: Collection<int, array{
     *         student_id: int,
     *         name: string,
     *         nim: string|null,
     *         total_sessions: int,
     *         hadir: int,
     *         terlambat: int,
     *         izin: int,
     *         sakit: int,
     *         tidak_hadir: int,
     *         belum_dicatat: int,
     *         present_count: int,
     *         percentage: float|null
     *     }>
     * }
     */
    public function getRecap(CourseSection $courseSection): array
    {
        // 1. Ambil seluruh sesi pada kelas
        $allSessions = $courseSection->attendanceSessions()->get();

        // Cari ID sesi asli yang telah digantikan oleh sesi pengganti (apapun status penggantinya)
        $replacedOriginalSessionIds = $allSessions
            ->whereNotNull('original_session_id')
            ->pluck('original_session_id')
            ->unique()
            ->all();

        // Sesi yang diperhitungkan (qualifying sessions):
        // - status 'ditutup' (sudah terlaksana)
        // - bukan sesi yang dibatalkan
        // - bukan sesi asli yang digantikan oleh sesi pengganti (agar tidak dihitung ganda)
        $qualifyingSessions = $allSessions->filter(function ($session) use ($replacedOriginalSessionIds): bool {
            if ($session->status !== 'ditutup') {
                return false;
            }

            if ($session->cancelled_at !== null) {
                return false;
            }

            if (in_array($session->id, $replacedOriginalSessionIds, true)) {
                return false;
            }

            return true;
        })->values();

        $qualifyingSessionIds = $qualifyingSessions->pluck('id')->all();
        $totalQualifyingSessions = count($qualifyingSessionIds);

        // 2. Ambil mahasiswa aktif terdaftar pada kelas
        $enrollments = $courseSection->enrollments()
            ->where('status', 'aktif')
            ->whereHas('student', function ($query): void {
                $query->where('is_active', true);
            })
            ->with('student')
            ->get();

        $totalActiveStudents = $enrollments->count();

        // 3. Ambil seluruh catatan presensi untuk sesi yang diperhitungkan
        $records = $totalQualifyingSessions > 0
            ? AttendanceRecord::query()
                ->whereIn('attendance_session_id', $qualifyingSessionIds)
                ->get()
                ->groupBy('student_id')
            : collect();

        // 4. Hitung rekap kumulatif per mahasiswa
        $totalPresentAll = 0;
        $totalPossibleAll = 0;

        $studentRecaps = $enrollments->map(function ($enrollment) use ($records, $totalQualifyingSessions, &$totalPresentAll, &$totalPossibleAll): array {
            $student = $enrollment->student;
            $studentRecords = $records->get($student->id, collect());

            $hadir = 0;
            $terlambat = 0;
            $izin = 0;
            $sakit = 0;
            $tidakHadir = 0;

            foreach ($studentRecords as $rec) {
                match ($rec->status) {
                    'hadir' => $hadir++,
                    'terlambat' => $terlambat++,
                    'izin' => $izin++,
                    'sakit' => $sakit++,
                    'tidak_hadir' => $tidakHadir++,
                    default => null,
                };
            }

            $recordedCount = $hadir + $terlambat + $izin + $sakit + $tidakHadir;
            $belumDicatat = max(0, $totalQualifyingSessions - $recordedCount);
            $presentCount = $hadir + $terlambat;

            $percentage = $totalQualifyingSessions > 0
                ? round(($presentCount / $totalQualifyingSessions) * 100, 1)
                : null;

            $totalPresentAll += $presentCount;
            $totalPossibleAll += $totalQualifyingSessions;

            return [
                'student_id' => $student->id,
                'name' => $student->name,
                'nim' => $student->nim,
                'total_sessions' => $totalQualifyingSessions,
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'izin' => $izin,
                'sakit' => $sakit,
                'tidak_hadir' => $tidakHadir,
                'belum_dicatat' => $belumDicatat,
                'present_count' => $presentCount,
                'percentage' => $percentage,
            ];
        });

        $overallPercentage = $totalPossibleAll > 0
            ? round(($totalPresentAll / $totalPossibleAll) * 100, 1)
            : null;

        return [
            'courseSection' => $courseSection,
            'totalQualifyingSessions' => $totalQualifyingSessions,
            'totalActiveStudents' => $totalActiveStudents,
            'overallPercentage' => $overallPercentage,
            'studentRecaps' => $studentRecaps,
        ];
    }
}
