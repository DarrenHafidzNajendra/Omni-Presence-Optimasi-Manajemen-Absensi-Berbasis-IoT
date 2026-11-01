@extends('layouts.app')

@section('title', 'Detail Mata Kuliah')

@section('content')
    <div class="mx-auto max-w-5xl space-y-8">
        <section class="space-y-2">
            <p class="text-sm font-semibold tracking-wide text-blue-700">AKADEMIK</p>
            <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Detail Mata Kuliah</h1>
            <p class="max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                Informasi mata kuliah {{ $course->name }} yang terdaftar di Omni-Presence.
            </p>
        </section>

        @if (session('success'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        <section aria-label="Informasi mata kuliah" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <dl class="divide-y divide-slate-100">
                <div class="grid gap-2 px-5 py-4 sm:grid-cols-[12rem_minmax(0,1fr)] sm:gap-6 sm:px-8">
                    <dt class="text-sm font-medium text-slate-500">Kode</dt>
                    <dd class="text-sm font-semibold text-slate-900">{{ $course->code }}</dd>
                </div>
                <div class="grid gap-2 px-5 py-4 sm:grid-cols-[12rem_minmax(0,1fr)] sm:gap-6 sm:px-8">
                    <dt class="text-sm font-medium text-slate-500">Nama</dt>
                    <dd class="text-sm font-semibold text-slate-900">{{ $course->name }}</dd>
                </div>
                <div class="grid gap-2 px-5 py-4 sm:grid-cols-[12rem_minmax(0,1fr)] sm:gap-6 sm:px-8">
                    <dt class="text-sm font-medium text-slate-500">Deskripsi</dt>
                    <dd class="whitespace-pre-line break-words text-sm text-slate-900">{{ $course->description ?? 'Belum ditentukan' }}</dd>
                </div>
                <div class="grid gap-2 px-5 py-4 sm:grid-cols-[12rem_minmax(0,1fr)] sm:gap-6 sm:px-8">
                    <dt class="text-sm font-medium text-slate-500">Status</dt>
                    <dd>
                        <span @class([
                            'inline-flex rounded-full px-2.5 py-1 text-xs font-medium',
                            'bg-emerald-100 text-emerald-800' => $course->is_active,
                            'bg-red-100 text-red-800' => ! $course->is_active,
                        ])>
                            {{ $course->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </dd>
                </div>
            </dl>
        </section>

        {{-- Daftar Mahasiswa per Kelas (Collapsible / Grouped by Section) --}}
        <section aria-labelledby="course-students-heading" class="space-y-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 id="course-students-heading" class="text-lg font-semibold text-slate-950">Daftar Mahasiswa per Kelas</h2>
                    <p class="mt-0.5 text-sm text-slate-500">
                        Ringkasan jumlah mahasiswa ditampilkan per kelas asal. Klik atau pilih kelompok kelas untuk membuka daftar mahasiswa.
                    </p>
                </div>
                @if (($allSections ?? collect())->isNotEmpty())
                    <div class="text-xs font-medium text-slate-600">
                        Total terdaftar: <span class="font-bold text-slate-900">{{ ($allSections ?? collect())->sum('total_enrollments_count') }} Mahasiswa</span> pada <span class="font-bold text-slate-900">{{ ($allSections ?? collect())->count() }} Kelas</span>
                    </div>
                @endif
            </div>

            {{-- Ringkasan Cepat Setiap Kelas (Tab / Pill Pemilih Kelas) --}}
            @if (($allSections ?? collect())->isNotEmpty())
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" aria-label="Ringkasan kelas mata kuliah">
                    @foreach ($allSections as $summarySection)
                        @php
                            $isSectionSelected = ($selectedSectionId !== null && (int) $selectedSectionId === (int) $summarySection->id)
                                || ($openSectionId !== null && (int) $openSectionId === (int) $summarySection->id);
                        @endphp
                        <a
                            href="{{ route('courses.show', array_filter([
                                'course' => $course,
                                'open_section_id' => $isSectionSelected ? null : $summarySection->id,
                                'student_search' => $studentSearch ?? null,
                                'account_status' => $accountStatus ?? null,
                            ], fn ($value) => $value !== null && $value !== '')) }}"
                            data-class-summary="{{ $summarySection->name }}"
                            @class([
                                'flex items-center justify-between rounded-xl border px-4 py-3 text-left transition shadow-sm',
                                'border-blue-600 bg-blue-50/60 ring-2 ring-blue-500/20' => $isSectionSelected,
                                'border-slate-200 bg-white hover:border-blue-300 hover:bg-slate-50' => ! $isSectionSelected,
                            ])
                        >
                            <div>
                                <p class="text-sm font-bold text-slate-950">{{ $summarySection->name }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $summarySection->academicPeriod?->name ?? ($summarySection->academic_year.' · '.ucfirst($summarySection->semester)) }}
                                </p>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800">
                                {{ $summarySection->total_enrollments_count ?? 0 }} Mahasiswa Terdaftar
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Filter Pencarian, Kelas, dan Status Akun Mahasiswa --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <form method="GET" action="{{ route('courses.show', $course) }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
                    <div class="space-y-1.5">
                        <label for="student_search" class="block text-sm font-medium text-slate-700">Cari NIM atau nama</label>
                        <input
                            id="student_search"
                            name="student_search"
                            type="search"
                            value="{{ $studentSearch ?? '' }}"
                            placeholder="Masukkan NIM atau nama mahasiswa"
                            class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                        >
                    </div>

                    <div class="space-y-1.5">
                        <label for="course_section_id" class="block text-sm font-medium text-slate-700">Filter Kelas</label>
                        <select
                            id="course_section_id"
                            name="course_section_id"
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                        >
                            <option value="">Semua Kelas ({{ ($allSections ?? collect())->count() }})</option>
                            @foreach ($allSections ?? [] as $sectionOption)
                                <option value="{{ $sectionOption->id }}" @selected((int) ($selectedSectionId ?? 0) === (int) $sectionOption->id)>
                                    {{ $sectionOption->name }} ({{ $sectionOption->total_enrollments_count ?? 0 }} Mahasiswa Terdaftar)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label for="account_status" class="block text-sm font-medium text-slate-700">Status Akun Mahasiswa</label>
                        <select
                            id="account_status"
                            name="account_status"
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                        >
                            <option value="">Semua Status</option>
                            <option value="active" @selected(in_array($accountStatus ?? '', ['active', 'aktif', '1'], true))>Aktif</option>
                            <option value="inactive" @selected(in_array($accountStatus ?? '', ['inactive', 'nonaktif', '0'], true))>Nonaktif</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="submit"
                            class="inline-flex flex-1 items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/25"
                        >
                            Filter Mahasiswa
                        </button>
                        <a
                            href="{{ route('courses.show', $course) }}"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            @if (($filteredSections ?? collect())->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-8 text-center text-sm text-slate-500">
                    Belum ada kelas yang terdaftar pada mata kuliah ini.
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($filteredSections as $section)
                        @php
                            $paginatedEnrollments = $section->paginatedEnrollments;
                            $totalEnrolledCount = (int) ($section->total_enrollments_count ?? 0);
                            $filteredCount = (int) ($section->filtered_enrollments_count ?? $totalEnrolledCount);
                        @endphp
                        <details
                            class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                            data-class-group="{{ $section->name }}"
                            @if ($section->should_open) open @endif
                        >
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 bg-slate-50/80 px-5 py-4 transition hover:bg-slate-100/80 sm:px-6">
                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="text-base font-bold text-slate-950">{{ $section->name }}</span>
                                    <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800">
                                        {{ $totalEnrolledCount }} Mahasiswa Terdaftar
                                    </span>
                                    @if ($hasStudentFilter ?? false)
                                        <span class="inline-flex rounded-full bg-slate-200 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                                            {{ $filteredCount }} sesuai filter
                                        </span>
                                    @endif
                                    <span class="text-xs text-slate-500">
                                        {{ $section->academicPeriod?->name ?? ($section->academic_year.' · '.ucfirst($section->semester)) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <a
                                        href="{{ route('course-sections.show', $section) }}"
                                        class="text-xs font-semibold text-blue-700 hover:underline"
                                    >
                                        Detail Kelas
                                    </a>
                                    <span class="text-xs font-medium text-slate-500 group-open:hidden">Buka daftar</span>
                                    <span class="hidden text-xs font-medium text-slate-500 group-open:inline">Tutup daftar</span>
                                    <span class="text-xs font-medium text-slate-500 transition-transform group-open:rotate-180">&#9662;</span>
                                </div>
                            </summary>

                            <div class="border-t border-slate-200">
                                @if ($paginatedEnrollments->isEmpty())
                                    <p class="px-5 py-6 text-sm text-slate-500 sm:px-6">
                                        Tidak ada mahasiswa terdaftar yang sesuai kriteria pada {{ $section->name }}.
                                    </p>
                                @else
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-slate-200 text-left">
                                            <thead class="bg-slate-50">
                                                <tr>
                                                    <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:px-6">NIM</th>
                                                    <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Nama Mahasiswa</th>
                                                    <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Kelas</th>
                                                    <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Status Akun</th>
                                                    <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach ($paginatedEnrollments as $enrollment)
                                                    @php
                                                        $student = $enrollment->student;
                                                    @endphp
                                                    @if ($student)
                                                        <tr class="transition hover:bg-slate-50/70">
                                                            <td class="whitespace-nowrap px-5 py-3.5 text-sm font-medium text-slate-700 sm:px-6">{{ $student->nim ?? '-' }}</td>
                                                            <td class="whitespace-nowrap px-5 py-3.5 text-sm font-semibold text-slate-900">{{ $student->name }}</td>
                                                            <td class="whitespace-nowrap px-5 py-3.5 text-sm text-slate-600">{{ $section->name }}</td>
                                                            <td class="whitespace-nowrap px-5 py-3.5 text-sm">
                                                                <span @class([
                                                                    'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium',
                                                                    'bg-emerald-100 text-emerald-800' => $student->is_active,
                                                                    'bg-red-100 text-red-800' => ! $student->is_active,
                                                                ])>
                                                                    {{ $student->is_active ? 'Aktif' : 'Nonaktif' }}
                                                                </span>
                                                            </td>
                                                            <td class="whitespace-nowrap px-5 py-3.5 text-sm">
                                                                <div class="flex flex-wrap items-center gap-2">
                                                                    <a
                                                                        href="{{ route('users.show', $student) }}"
                                                                        class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                                                    >
                                                                        Detail
                                                                    </a>
                                                                    <form
                                                                        method="POST"
                                                                        action="{{ route('users.status', $student) }}"
                                                                        onsubmit="{{ $student->is_active ? "return confirm('Nonaktifkan akun mahasiswa ini?')" : '' }}"
                                                                    >
                                                                        @csrf
                                                                        @method('PATCH')
                                                                        <input type="hidden" name="is_active" value="{{ $student->is_active ? '0' : '1' }}">
                                                                        <button
                                                                            type="submit"
                                                                            class="inline-flex items-center justify-center rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition {{ $student->is_active ? 'border-red-300 text-red-700 hover:bg-red-50' : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' }}"
                                                                        >
                                                                            {{ $student->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    @if ($paginatedEnrollments->hasPages())
                                        <div class="border-t border-slate-200 px-5 py-4 sm:px-6" data-class-pagination="{{ $section->name }}">
                                            {{ $paginatedEnrollments->links() }}
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </details>
                    @endforeach
                </div>
            @endif
        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a
                href="{{ route('courses.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-500/15"
            >
                Kembali ke Daftar
            </a>
            <div class="flex flex-col gap-3 sm:flex-row">
                <a
                    href="{{ route('courses.edit', $course) }}"
                    class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/25"
                >
                    Edit Mata Kuliah
                </a>
                <form
                    method="POST"
                    action="{{ route('courses.status', $course) }}"
                    onsubmit="return confirm('{{ $course->is_active ? 'Nonaktifkan' : 'Aktifkan' }} mata kuliah ini?')"
                >
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="is_active" value="{{ $course->is_active ? '0' : '1' }}">
                    <button
                        type="submit"
                        @class([
                            'inline-flex items-center justify-center rounded-lg border px-3 py-2 text-sm font-semibold transition focus:outline-none focus:ring-4',
                            'border-red-300 text-red-700 hover:bg-red-50 focus:ring-red-500/15' => $course->is_active,
                            'border-emerald-300 text-emerald-700 hover:bg-emerald-50 focus:ring-emerald-500/15' => ! $course->is_active,
                        ])
                    >
                        {{ $course->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
