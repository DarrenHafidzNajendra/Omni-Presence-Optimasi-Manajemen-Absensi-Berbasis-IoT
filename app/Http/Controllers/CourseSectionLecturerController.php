<?php

namespace App\Http\Controllers;

use App\Models\CourseSection;
use App\Models\CourseSectionLecturer;
use App\Models\User;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseSectionLecturerController extends Controller
{
    public function store(Request $request, CourseSection $courseSection): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists(User::class, 'id')->where(function (QueryBuilder $query): void {
                    $query->where('role', 'dosen')
                        ->where('is_active', true);
                }),
            ],
            'teaching_type' => [
                'required',
                Rule::in(['teori', 'praktikum']),
                Rule::unique(CourseSectionLecturer::class, 'teaching_type')
                    ->where('course_section_id', $courseSection->id)
                    ->where('user_id', $request->input('user_id')),
            ],
        ]);

        $courseSection->lecturers()->create($validated);

        return redirect()
            ->route('course-sections.show', $courseSection)
            ->with('success', 'Dosen berhasil ditugaskan ke kelas.');
    }
}
