<?php

namespace App\Http\Controllers;

use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EnrollmentController extends Controller
{
    public function index(CourseSection $courseSection): RedirectResponse
    {
        return redirect()->route('course-sections.show', $courseSection);
    }

    public function store(Request $request, CourseSection $courseSection): RedirectResponse
    {
        if (! $courseSection->is_active) {
            return back()
                ->withErrors(['student_id' => 'Mahasiswa tidak dapat didaftarkan ke kelas nonaktif.'])
                ->withInput();
        }

        $validated = $request->validate([
            'student_id' => [
                'required',
                'integer',
                Rule::exists(User::class, 'id')->where(function (QueryBuilder $query): void {
                    $query->where('role', 'mahasiswa')
                        ->where('is_active', true);
                }),
                Rule::unique(Enrollment::class, 'student_id')
                    ->where('course_section_id', $courseSection->id),
            ],
        ]);

        $courseSection->enrollments()->create([
            'student_id' => $validated['student_id'],
            'status' => 'aktif',
        ]);

        return redirect()
            ->route('course-sections.show', $courseSection)
            ->with('success', 'Mahasiswa berhasil didaftarkan ke kelas.');
    }

    public function updateStatus(Request $request, CourseSection $courseSection, Enrollment $enrollment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
        ]);

        $enrollment->update([
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('course-sections.show', $courseSection)
            ->with('success', 'Status enrollment berhasil diperbarui.');
    }

    public function destroy(CourseSection $courseSection, Enrollment $enrollment): RedirectResponse
    {
        $enrollment->delete();

        return redirect()
            ->route('course-sections.show', $courseSection)
            ->with('success', 'Enrollment berhasil dihapus.');
    }
}
