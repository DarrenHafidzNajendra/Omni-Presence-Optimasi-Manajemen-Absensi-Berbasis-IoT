<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $search = trim($validated['search'] ?? '');

        $courses = Course::query()
            ->select(['id', 'code', 'name', 'description', 'is_active'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('code')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('courses.index', [
            'courses' => $courses,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('courses.create');
    }

    public function show(Request $request, Course $course): View
    {
        $validated = $request->validate([
            'student_search' => ['nullable', 'string', 'max:255'],
            'account_status' => ['nullable', Rule::in(['active', 'inactive', 'aktif', 'nonaktif', '1', '0'])],
            'course_section_id' => ['nullable', 'integer'],
            'open_section_id' => ['nullable', 'integer'],
        ]);

        $studentSearch = trim($validated['student_search'] ?? '');
        $accountStatus = $validated['account_status'] ?? null;
        $selectedSectionId = isset($validated['course_section_id']) ? (int) $validated['course_section_id'] : null;
        $openSectionId = isset($validated['open_section_id']) ? (int) $validated['open_section_id'] : null;
        $hasStudentFilter = $studentSearch !== '' || ($accountStatus !== null && $accountStatus !== '');

        $course->load([
            'sections' => fn ($query) => $query
                ->with(['academicPeriod:id,name,is_active'])
                ->withCount([
                    'enrollments as total_enrollments_count' => fn (Builder $enrollmentQuery) => $enrollmentQuery
                        ->whereHas('student')
                        ->select(DB::raw('count(distinct student_id)')),
                ])
                ->orderBy('name')
                ->orderBy('id'),
        ]);

        $filteredSections = $selectedSectionId !== null
            ? $course->sections->where('id', $selectedSectionId)->values()
            : $course->sections;

        foreach ($filteredSections as $section) {
            $pageName = 'section_'.$section->id.'_page';

            $paginatedEnrollments = Enrollment::query()
                ->where('course_section_id', $section->id)
                ->whereIn('id', function ($subquery) use ($section): void {
                    $subquery->selectRaw('MIN(id)')
                        ->from('enrollments')
                        ->where('course_section_id', $section->id)
                        ->groupBy('student_id');
                })
                ->whereHas('student', function (Builder $studentQuery) use ($studentSearch, $accountStatus): void {
                    if ($studentSearch !== '') {
                        $studentQuery->where(function (Builder $q) use ($studentSearch): void {
                            $q->where('name', 'like', "%{$studentSearch}%")
                                ->orWhere('nim', 'like', "%{$studentSearch}%");
                        });
                    }

                    if ($accountStatus !== null && $accountStatus !== '') {
                        $isActive = in_array($accountStatus, ['active', 'aktif', '1'], true);
                        $studentQuery->where('is_active', $isActive);
                    }
                })
                ->with([
                    'student' => fn ($q) => $q
                        ->select(['id', 'name', 'nim', 'is_active', 'role'])
                        ->withCount('attendanceRecords'),
                ])
                ->orderBy('id')
                ->paginate(10, ['*'], $pageName)
                ->withQueryString();

            $section->setRelation('paginatedEnrollments', $paginatedEnrollments);
            $section->setRelation('enrollments', $paginatedEnrollments->getCollection());
            $section->filtered_enrollments_count = $paginatedEnrollments->total();
            $section->should_open = ($selectedSectionId !== null && $selectedSectionId === (int) $section->id)
                || ($openSectionId !== null && $openSectionId === (int) $section->id)
                || $request->filled($pageName)
                || ($hasStudentFilter && $paginatedEnrollments->total() > 0);
        }

        return view('courses.show', [
            'course' => $course,
            'allSections' => $course->sections,
            'filteredSections' => $filteredSections,
            'studentSearch' => $studentSearch,
            'accountStatus' => $accountStatus,
            'selectedSectionId' => $selectedSectionId,
            'openSectionId' => $openSectionId,
            'hasStudentFilter' => $hasStudentFilter,
        ]);
    }

    public function edit(Course $course): View
    {
        return view('courses.edit', [
            'course' => $course,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Course::class, 'code'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        Course::create($validated);

        return redirect()
            ->route('courses.index')
            ->with('success', 'Mata kuliah berhasil ditambahkan.');
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Course::class, 'code')->ignore($course),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $course->update($validated);

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Mata kuliah berhasil diperbarui.');
    }

    public function updateStatus(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $course->update([
            'is_active' => $validated['is_active'],
        ]);

        return redirect()
            ->route('courses.show', $course)
            ->with(
                'success',
                $course->is_active
                    ? 'Mata kuliah berhasil diaktifkan.'
                    : 'Mata kuliah berhasil dinonaktifkan.',
            );
    }
}
