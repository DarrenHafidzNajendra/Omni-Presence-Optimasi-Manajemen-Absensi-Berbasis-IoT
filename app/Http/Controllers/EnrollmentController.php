<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\CourseSection;
use App\Models\Enrollment;
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
        abort(403, 'Pendaftaran mata kuliah manual oleh Admin telah dinonaktifkan. Mahasiswa harus melakukan enrollment mandiri melalui akun masing-masing.');
    }

    public function enrollSelf(Request $request, CourseSection $courseSection): RedirectResponse
    {
        $student = $request->user();

        if (! $student || $student->role !== UserRole::Mahasiswa->value || ! $student->is_active) {
            abort(403, 'Hanya mahasiswa aktif yang dapat melakukan enrollment mandiri.');
        }

        if (! $courseSection->is_active) {
            return back()
                ->withErrors(['enrollment' => 'Anda tidak dapat mendaftar ke kelas yang sedang nonaktif.']);
        }

        if ($courseSection->enrollments()->where('student_id', $student->id)->exists()) {
            return back()
                ->withErrors(['enrollment' => 'Anda sudah terdaftar pada kelas ini.']);
        }

        $courseSection->enrollments()->create([
            'student_id' => $student->id,
            'status' => 'aktif',
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Anda berhasil mendaftar ke kelas '.$courseSection->name.'.');
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
