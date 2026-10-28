<?php

namespace App\Http\Controllers;

use App\Models\AcademicPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AcademicPeriodController extends Controller
{
    public function index(): View
    {
        return view('academic-periods.index', [
            'academicPeriods' => AcademicPeriod::query()
                ->orderByDesc('starts_on')
                ->orderBy('id')
                ->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('academic-periods.create', [
            'academicPeriod' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedAcademicPeriod($request);

        AcademicPeriod::create($validated);

        return redirect()
            ->route('academic-periods.index')
            ->with('success', 'Periode akademik berhasil ditambahkan.');
    }

    public function show(AcademicPeriod $academicPeriod): View
    {
        return view('academic-periods.show', compact('academicPeriod'));
    }

    public function edit(AcademicPeriod $academicPeriod): View
    {
        return view('academic-periods.edit', compact('academicPeriod'));
    }

    public function update(Request $request, AcademicPeriod $academicPeriod): RedirectResponse
    {
        $academicPeriod->update($this->validatedAcademicPeriod($request));

        return redirect()
            ->route('academic-periods.show', $academicPeriod)
            ->with('success', 'Periode akademik berhasil diperbarui.');
    }

    public function updateStatus(Request $request, AcademicPeriod $academicPeriod): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $academicPeriod->update([
            'is_active' => $validated['is_active'],
        ]);

        return redirect()
            ->route('academic-periods.show', $academicPeriod)
            ->with(
                'success',
                $academicPeriod->is_active
                    ? 'Periode akademik berhasil diaktifkan.'
                    : 'Periode akademik berhasil dinonaktifkan.',
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedAcademicPeriod(Request $request): array
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'academic_year' => ['required', 'string', 'max:255'],
            'semester' => ['required', Rule::in(['ganjil', 'genap'])],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['required', 'date_format:Y-m-d'],
            'ats_starts_on' => ['required', 'date_format:Y-m-d'],
            'ats_ends_on' => ['required', 'date_format:Y-m-d'],
            'uas_starts_on' => ['required', 'date_format:Y-m-d'],
            'uas_ends_on' => ['required', 'date_format:Y-m-d'],
            'replacement_deadline' => ['required', 'date_format:Y-m-d'],
            'is_active' => ['boolean'],
        ]);

        $validator->after(function (ValidatorContract $validator) use ($data): void {
            $dateFields = [
                'starts_on',
                'ends_on',
                'ats_starts_on',
                'ats_ends_on',
                'uas_starts_on',
                'uas_ends_on',
                'replacement_deadline',
            ];

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $dates = collect($dateFields)->mapWithKeys(
                fn (string $field): array => [$field => CarbonImmutable::createFromFormat('Y-m-d', $data[$field])],
            );

            if ($dates['ends_on']->isBefore($dates['starts_on'])) {
                $validator->errors()->add('ends_on', 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.');
            }

            foreach (['ats_starts_on', 'ats_ends_on', 'uas_starts_on', 'uas_ends_on', 'replacement_deadline'] as $field) {
                if ($dates[$field]->isBefore($dates['starts_on']) || $dates[$field]->isAfter($dates['ends_on'])) {
                    $validator->errors()->add($field, 'Tanggal harus berada dalam rentang periode akademik.');
                }
            }

            if ($dates['ats_ends_on']->isBefore($dates['ats_starts_on'])) {
                $validator->errors()->add('ats_ends_on', 'Tanggal selesai ATS harus setelah atau sama dengan tanggal mulai ATS.');
            }

            if ($dates['uas_ends_on']->isBefore($dates['uas_starts_on'])) {
                $validator->errors()->add('uas_ends_on', 'Tanggal selesai UAS harus setelah atau sama dengan tanggal mulai UAS.');
            }

            if ($dates['uas_starts_on']->isBefore($dates['ats_ends_on'])) {
                $validator->errors()->add('uas_starts_on', 'Tanggal mulai UAS tidak boleh mendahului selesai ATS.');
            }
        });

        return $validator->validate();
    }
}
