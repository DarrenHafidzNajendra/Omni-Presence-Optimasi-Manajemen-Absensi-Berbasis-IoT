<?php

namespace App\Http\Controllers;

use App\Models\ClassSchedule;
use App\Models\CourseSection;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClassScheduleController extends Controller
{
    private const DAYS = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];

    public function index(CourseSection $courseSection): View
    {
        return view('class-schedules.index', [
            'courseSection' => $courseSection->load('course:id,code,name'),
            'schedules' => $courseSection->schedules()
                ->orderBy('day_of_week')
                ->orderBy('starts_at')
                ->orderBy('id')
                ->get(),
            'days' => self::DAYS,
        ]);
    }

    public function create(CourseSection $courseSection): View
    {
        return view('class-schedules.create', [
            'courseSection' => $courseSection->load('course:id,code,name'),
            'days' => self::DAYS,
            'schedule' => null,
        ]);
    }

    public function store(Request $request, CourseSection $courseSection): RedirectResponse
    {
        $courseSection->schedules()->create($this->validatedSchedule($request, null, $courseSection));

        return redirect()
            ->route('course-sections.schedules.index', $courseSection)
            ->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function edit(CourseSection $courseSection, ClassSchedule $schedule): View
    {
        return view('class-schedules.edit', [
            'courseSection' => $courseSection->load('course:id,code,name'),
            'schedule' => $schedule,
            'days' => self::DAYS,
        ]);
    }

    public function update(Request $request, CourseSection $courseSection, ClassSchedule $schedule): RedirectResponse
    {
        $schedule->update($this->validatedSchedule($request, $schedule, $courseSection));

        return redirect()
            ->route('course-sections.schedules.index', $courseSection)
            ->with('success', 'Jadwal berhasil diperbarui.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedSchedule(Request $request, ?ClassSchedule $schedule = null, ?CourseSection $courseSection = null): array
    {
        $data = $request->all();
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $validator = Validator::make($data, [
            'session_type' => ['required', Rule::in(['teori', 'praktikum'])],
            'delivery_mode' => ['required', Rule::in(['online', 'onsite'])],
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i'],
            'timezone' => ['required', 'timezone'],
            'location' => ['nullable', 'string', 'max:255', 'required_if:delivery_mode,onsite'],
            'meeting_url' => ['nullable', 'url', 'max:255', 'required_if:delivery_mode,online'],
            'valid_from' => ['nullable', 'date_format:Y-m-d'],
            'valid_until' => ['nullable', 'date_format:Y-m-d'],
            'is_active' => ['boolean'],
        ]);

        $validator->after(function (ValidatorContract $validator) use ($data, $schedule, $courseSection): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($data['ends_at'] <= $data['starts_at']) {
                $validator->errors()->add('ends_at', 'Jam selesai harus setelah jam mulai.');
            }

            if (
                filled($data['valid_from'] ?? null)
                && filled($data['valid_until'] ?? null)
                && CarbonImmutable::createFromFormat('Y-m-d', $data['valid_until'])
                    ->isBefore(CarbonImmutable::createFromFormat('Y-m-d', $data['valid_from']))
            ) {
                $validator->errors()->add('valid_until', 'Tanggal selesai tidak boleh lebih awal daripada tanggal mulai.');
            }

            if ($validator->errors()->isNotEmpty() || ! $data['is_active']) {
                return;
            }

            $conflictData = $data;
            $conflictData['course_section_id'] = $courseSection?->getKey() ?? $schedule?->course_section_id;

            $this->validateConflicts($validator, $conflictData, $schedule);
        });

        $validated = $validator->validate();
        $validated['location'] = $validated['delivery_mode'] === 'onsite'
            ? ($validated['location'] ?? null)
            : null;
        $validated['meeting_url'] = $validated['delivery_mode'] === 'online'
            ? ($validated['meeting_url'] ?? null)
            : null;

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateConflicts(ValidatorContract $validator, array $data, ?ClassSchedule $schedule): void
    {
        $courseSectionId = $data['course_section_id'];
        $candidates = ClassSchedule::query()
            ->where('is_active', true)
            ->where('day_of_week', $data['day_of_week'])
            ->when($schedule !== null, fn (Builder $query) => $query->where(
                'id',
                '!=',
                $schedule->getKey(),
            ))
            ->get([
                'id',
                'course_section_id',
                'delivery_mode',
                'location',
                'starts_at',
                'ends_at',
                'valid_from',
                'valid_until',
            ]);

        foreach ($candidates as $candidate) {
            $sameSection = (int) $candidate->course_section_id === (int) $courseSectionId;
            $sameRoom = $data['delivery_mode'] === 'onsite'
                && $candidate->delivery_mode === 'onsite'
                && $candidate->location === $data['location'];

            if (! $sameSection && ! $sameRoom) {
                continue;
            }

            if (
                $this->timesOverlap($data['starts_at'], $data['ends_at'], $candidate->starts_at, $candidate->ends_at)
                && $this->datesOverlap($data['valid_from'] ?? null, $data['valid_until'] ?? null, $candidate->valid_from, $candidate->valid_until)
            ) {
                $validator->errors()->add(
                    'starts_at',
                    $sameSection
                        ? 'Jadwal bertabrakan dengan jadwal lain pada kelas ini.'
                        : 'Jadwal bertabrakan dengan jadwal aktif di ruangan yang sama.',
                );

                return;
            }
        }
    }

    private function timesOverlap(string $startsAt, string $endsAt, string $candidateStartsAt, string $candidateEndsAt): bool
    {
        $newStart = CarbonImmutable::createFromFormat('H:i', $startsAt);
        $newEnd = CarbonImmutable::createFromFormat('H:i', $endsAt);
        $candidateStart = CarbonImmutable::createFromFormat('H:i', substr($candidateStartsAt, 0, 5));
        $candidateEnd = CarbonImmutable::createFromFormat('H:i', substr($candidateEndsAt, 0, 5));

        return $newStart < $candidateEnd && $newEnd > $candidateStart;
    }

    private function datesOverlap(?string $startsAt, ?string $endsAt, mixed $candidateStartsAt, mixed $candidateEndsAt): bool
    {
        $newStart = $startsAt !== null ? CarbonImmutable::parse($startsAt) : null;
        $newEnd = $endsAt !== null ? CarbonImmutable::parse($endsAt) : null;
        $candidateStart = $candidateStartsAt !== null ? CarbonImmutable::parse((string) $candidateStartsAt) : null;
        $candidateEnd = $candidateEndsAt !== null ? CarbonImmutable::parse((string) $candidateEndsAt) : null;

        return ($newEnd === null || $candidateStart === null || $newEnd >= $candidateStart)
            && ($candidateEnd === null || $newStart === null || $candidateEnd >= $newStart);
    }
}
