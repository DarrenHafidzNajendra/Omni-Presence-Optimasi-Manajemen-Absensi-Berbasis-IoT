<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseSection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourseSectionController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $search = trim($validated['search'] ?? '');

        $courseSections = CourseSection::query()
            ->select(['id', 'course_id', 'name', 'academic_year', 'semester', 'is_active'])
            ->with(['course:id,code,name'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhereHas('course', function (Builder $query) use ($search): void {
                            $query->where('code', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('academic_year')
            ->orderBy('semester')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('course-sections.index', [
            'courseSections' => $courseSections,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('course-sections.create', [
            'courses' => Course::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->orderBy('id')
                ->get(['id', 'code', 'name']),
        ]);
    }

    public function show(CourseSection $courseSection): View
    {
        $courseSection->load('course:id,code,name');

        return view('course-sections.show', [
            'courseSection' => $courseSection,
        ]);
    }

    public function edit(CourseSection $courseSection): View
    {
        $courseSection->load('course:id,code,name');

        return view('course-sections.edit', [
            'courseSection' => $courseSection,
            'courses' => Course::query()
                ->where(function (Builder $query) use ($courseSection): void {
                    $query->where('is_active', true)
                        ->orWhere('id', $courseSection->course_id);
                })
                ->orderBy('code')
                ->orderBy('id')
                ->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => [
                'required',
                'integer',
                Rule::exists(Course::class, 'id')->where('is_active', true),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(CourseSection::class, 'name')
                    ->where('course_id', $request->input('course_id'))
                    ->where('academic_year', $request->input('academic_year'))
                    ->where('semester', $request->input('semester')),
            ],
            'academic_year' => ['required', 'string', 'max:255'],
            'semester' => ['required', 'string', Rule::in(['ganjil', 'genap'])],
        ]);

        CourseSection::create($validated);

        return redirect()
            ->route('course-sections.index')
            ->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function update(Request $request, CourseSection $courseSection): RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => [
                'required',
                'integer',
                Rule::exists(Course::class, 'id')->where(function (QueryBuilder $query) use ($courseSection): void {
                    $query->where('is_active', true)
                        ->orWhere('id', $courseSection->course_id);
                }),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(CourseSection::class, 'name')
                    ->ignore($courseSection)
                    ->where('course_id', $request->input('course_id'))
                    ->where('academic_year', $request->input('academic_year'))
                    ->where('semester', $request->input('semester')),
            ],
            'academic_year' => ['required', 'string', 'max:255'],
            'semester' => ['required', 'string', Rule::in(['ganjil', 'genap'])],
        ]);

        $courseSection->update($validated);

        return redirect()
            ->route('course-sections.show', $courseSection)
            ->with('success', 'Kelas berhasil diperbarui.');
    }

    public function updateStatus(Request $request, CourseSection $courseSection): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $courseSection->update([
            'is_active' => $validated['is_active'],
        ]);

        return redirect()
            ->route('course-sections.show', $courseSection)
            ->with(
                'success',
                $courseSection->is_active
                    ? 'Kelas berhasil diaktifkan.'
                    : 'Kelas berhasil dinonaktifkan.',
            );
    }
}
