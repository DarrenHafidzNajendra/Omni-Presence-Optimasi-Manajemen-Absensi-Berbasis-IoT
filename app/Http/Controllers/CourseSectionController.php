<?php

namespace App\Http\Controllers;

use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\User;
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
            'academic_period_id' => ['nullable', 'string', 'max:32'],
            'course_id' => ['nullable', 'integer'],
            'lecturer_id' => ['nullable', 'integer'],
            'course_section_id' => ['nullable', 'integer'],
            'section_name' => ['nullable', 'string', 'max:255'],
        ]);

        $search = trim($validated['search'] ?? '');
        $courseId = isset($validated['course_id']) ? (int) $validated['course_id'] : null;
        $lecturerId = isset($validated['lecturer_id']) ? (int) $validated['lecturer_id'] : null;
        $courseSectionId = isset($validated['course_section_id']) ? (int) $validated['course_section_id'] : null;
        $sectionName = trim($validated['section_name'] ?? '');
        $user = $request->user();

        $academicPeriods = AcademicPeriod::query()
            ->orderByDesc('is_active')
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->get(['id', 'name', 'academic_year', 'semester', 'is_active']);

        $activeAcademicPeriod = $academicPeriods->firstWhere('is_active', true);

        $rawPeriodFilter = $request->query('academic_period_id');
        if ($rawPeriodFilter === null) {
            if (
                $activeAcademicPeriod !== null
                && CourseSection::query()->where('academic_period_id', $activeAcademicPeriod->id)->exists()
                && ! CourseSection::query()->whereNull('academic_period_id')->exists()
            ) {
                $selectedAcademicPeriodId = (string) $activeAcademicPeriod->id;
            } else {
                $selectedAcademicPeriodId = $activeAcademicPeriod !== null && CourseSection::query()->where('academic_period_id', $activeAcademicPeriod->id)->exists()
                    ? (string) $activeAcademicPeriod->id
                    : 'all';
            }
        } elseif ($rawPeriodFilter === '' || $rawPeriodFilter === 'all') {
            $selectedAcademicPeriodId = 'all';
        } else {
            $selectedAcademicPeriodId = (string) (int) $rawPeriodFilter;
        }

        $courseSections = CourseSection::query()
            ->select(['id', 'course_id', 'academic_period_id', 'name', 'academic_year', 'semester', 'is_active'])
            ->with([
                'course:id,code,name',
                'academicPeriod:id,name,is_active',
                'lecturers.user:id,name,nidn',
                'schedules' => fn ($query) => $query
                    ->orderBy('day_of_week')
                    ->orderBy('starts_at')
                    ->orderBy('id'),
            ])
            ->when($user && $user->role === 'dosen', function (Builder $query) use ($user): void {
                $query->whereHas('lecturers', function (Builder $lecturerQuery) use ($user): void {
                    $lecturerQuery->where('user_id', $user->id);
                });
            })
            ->when($selectedAcademicPeriodId !== 'all', function (Builder $query) use ($selectedAcademicPeriodId, $rawPeriodFilter): void {
                $periodId = (int) $selectedAcademicPeriodId;
                if ($rawPeriodFilter === null) {
                    $query->where(function (Builder $q) use ($periodId): void {
                        $q->where('academic_period_id', $periodId)
                            ->orWhereNull('academic_period_id');
                    });
                } else {
                    $query->where('academic_period_id', $periodId);
                }
            })
            ->when($courseId !== null, function (Builder $query) use ($courseId): void {
                $query->where('course_id', $courseId);
            })
            ->when($lecturerId !== null, function (Builder $query) use ($lecturerId): void {
                $query->whereHas('lecturers', function (Builder $lecturerQuery) use ($lecturerId): void {
                    $lecturerQuery->where('user_id', $lecturerId);
                });
            })
            ->when($courseSectionId !== null, function (Builder $query) use ($courseSectionId): void {
                $query->whereKey($courseSectionId);
            })
            ->when($sectionName !== '', function (Builder $query) use ($sectionName): void {
                $query->where('name', $sectionName);
            })
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

        $courses = Course::query()
            ->orderBy('code')
            ->orderBy('id')
            ->get(['id', 'code', 'name']);

        $lecturers = User::query()
            ->where('role', 'dosen')
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'nidn']);

        return view('course-sections.index', [
            'courseSections' => $courseSections,
            'search' => $search,
            'sectionName' => $sectionName,
            'academicPeriods' => $academicPeriods,
            'selectedAcademicPeriodId' => $selectedAcademicPeriodId,
            'courses' => $courses,
            'selectedCourseId' => $courseId,
            'lecturers' => $lecturers,
            'selectedLecturerId' => $lecturerId,
            'selectedCourseSectionId' => $courseSectionId,
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
            'academicPeriods' => AcademicPeriod::query()
                ->where('is_active', true)
                ->orderByDesc('starts_on')
                ->orderBy('id')
                ->get(['id', 'name', 'academic_year', 'semester']),
        ]);
    }

    public function show(Request $request, CourseSection $courseSection): View
    {
        $validated = $request->validate([
            'student_search' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $studentSearch = trim((string) ($validated['student_search'] ?? $validated['search'] ?? ''));

        $courseSection->load([
            'course:id,code,name',
            'academicPeriod:id,name,academic_year,semester,is_active',
            'lecturers' => fn ($query) => $query
                ->with('user:id,name,nidn,is_active')
                ->orderBy('teaching_type')
                ->orderBy('id'),
            'schedules' => fn ($query) => $query
                ->orderBy('day_of_week')
                ->orderBy('starts_at')
                ->orderBy('id'),
        ]);

        $totalEnrolledCount = Enrollment::query()
            ->where('course_section_id', $courseSection->id)
            ->whereHas('student')
            ->distinct()
            ->count('student_id');

        $enrollments = Enrollment::query()
            ->where('course_section_id', $courseSection->id)
            ->whereIn('id', function ($subquery) use ($courseSection): void {
                $subquery->selectRaw('MIN(id)')
                    ->from('enrollments')
                    ->where('course_section_id', $courseSection->id)
                    ->groupBy('student_id');
            })
            ->whereHas('student', function (Builder $studentQuery) use ($studentSearch): void {
                if ($studentSearch !== '') {
                    $studentQuery->where(function (Builder $q) use ($studentSearch): void {
                        $q->where('name', 'like', "%{$studentSearch}%")
                            ->orWhere('nim', 'like', "%{$studentSearch}%");
                    });
                }
            })
            ->with('student:id,name,nim,is_active')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        $courseSection->setRelation('enrollments', $enrollments);

        return view('course-sections.show', [
            'courseSection' => $courseSection,
            'enrollments' => $enrollments,
            'totalEnrolledCount' => $totalEnrolledCount,
            'studentSearch' => $studentSearch,
            'lecturerOptions' => User::query()
                ->where('role', 'dosen')
                ->where('is_active', true)
                ->orderBy('name')
                ->orderBy('id')
                ->get(['id', 'name', 'nidn']),
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
            'academicPeriods' => AcademicPeriod::query()
                ->where(function (Builder $query) use ($courseSection): void {
                    $query->where('is_active', true)
                        ->orWhere('id', $courseSection->academic_period_id);
                })
                ->orderByDesc('starts_on')
                ->orderBy('id')
                ->get(['id', 'name', 'academic_year', 'semester', 'is_active']),
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
            'academic_period_id' => [
                'required',
                'integer',
                Rule::exists(AcademicPeriod::class, 'id')->where('is_active', true),
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
            'academic_period_id' => [
                'required',
                'integer',
                Rule::exists(AcademicPeriod::class, 'id')->where(function (QueryBuilder $query) use ($courseSection): void {
                    $query->where('is_active', true)
                        ->orWhere('id', $courseSection->academic_period_id);
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
