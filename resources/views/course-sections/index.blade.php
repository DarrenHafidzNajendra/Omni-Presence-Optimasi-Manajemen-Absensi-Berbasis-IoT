@extends('layouts.app')

@section('title', auth()->user()->role === 'dosen' ? 'Kelas Saya' : 'Daftar Kelas')

@section('content')
    <div class="space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1.5">
                <div class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                    <svg class="size-3.5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75 2.25 12l4.179 2.25m0-4.5 5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0 4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0-5.571 3-5.571-3" />
                    </svg>
                    <span>AKADEMIK</span>
                </div>
                <div class="flex flex-wrap items-baseline gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                        {{ auth()->user()->role === 'dosen' ? 'Kelas Saya' : 'Daftar Kelas' }}
                    </h1>
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                        {{ number_format($courseSections->total()) }} kelas
                    </span>
                </div>
                <p class="max-w-2xl text-sm leading-6 text-slate-600">
                    {{ auth()->user()->role === 'dosen' ? 'Daftar kelas perkuliahan yang ditugaskan kepada Anda.' : 'Kelola kelas untuk mata kuliah yang tersedia di Omni-Presence.' }}
                </p>
            </div>
            @if (auth()->user()->role === 'admin')
                <a
                    href="{{ route('course-sections.create') }}"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 hover:shadow focus:outline-none focus:ring-4 focus:ring-blue-500/25 active:scale-[0.98]"
                >
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Tambah Kelas
                </a>
            @endif
        </section>

        @if (session('success'))
            <div role="status" class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-2xs">
                <svg class="size-5 shrink-0 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <div class="flex-1">{{ session('success') }}</div>
            </div>
        @endif

        <section aria-label="Pencarian dan filter kelas" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs sm:p-5">
            <form method="GET" action="{{ route('course-sections.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="space-y-1.5">
                        <label for="academic_period_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">Periode Akademik</label>
                        <select id="academic_period_id" name="academic_period_id" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/15 transition">
                            <option value="all" @selected(($selectedAcademicPeriodId ?? 'all') === 'all')>Semua Periode Akademik</option>
                            @foreach ($academicPeriods ?? [] as $period)
                                <option value="{{ $period->id }}" @selected((string) ($selectedAcademicPeriodId ?? '') === (string) $period->id)>
                                    {{ $period->name }}{{ $period->is_active ? ' (Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label for="course_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">Mata Kuliah</label>
                        <select id="course_id" name="course_id" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/15 transition">
                            <option value="">Semua Mata Kuliah</option>
                            @foreach ($courses ?? [] as $courseOption)
                                <option value="{{ $courseOption->id }}" @selected((int) ($selectedCourseId ?? 0) === (int) $courseOption->id)>
                                    {{ $courseOption->code }} - {{ $courseOption->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label for="section_name" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">Kelas</label>
                        <input id="section_name" name="section_name" type="text" value="{{ $sectionName ?? '' }}" placeholder="Contoh: TI-3A" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/15 transition">
                    </div>

                    @if (auth()->user()->role === 'admin')
                        <div class="space-y-1.5">
                            <label for="lecturer_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">Dosen Pengampu</label>
                            <select id="lecturer_id" name="lecturer_id" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/15 transition">
                                <option value="">Semua Dosen</option>
                                @foreach ($lecturers ?? [] as $lecturerOption)
                                    <option value="{{ $lecturerOption->id }}" @selected((int) ($selectedLecturerId ?? 0) === (int) $lecturerOption->id)>
                                        {{ $lecturerOption->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="flex-1 space-y-1.5">
                        <label for="search" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">Cari nama kelas atau mata kuliah</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                </svg>
                            </div>
                            <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Masukkan nama kelas, kode, atau nama mata kuliah" class="block w-full rounded-xl border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/15 transition">
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('course-sections.index', ['academic_period_id' => 'all']) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-900">
                            Reset
                        </a>
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/25 active:scale-[0.98]">
                            Terapkan Filter
                        </button>
                    </div>
                </div>
            </form>
        </section>

        <section aria-label="Hasil daftar kelas" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
            @if ($courseSections->isEmpty())
                <div class="px-6 py-14 text-center">
                    <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75 2.25 12l4.179 2.25m0-4.5 5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0 4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0-5.571 3-5.571-3" />
                        </svg>
                    </div>
                    <h2 class="mt-4 text-base font-semibold text-slate-900">
                        {{ auth()->user()->role === 'dosen' ? 'Anda belum ditugaskan ke kelas mana pun.' : 'Kelas tidak ditemukan' }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ auth()->user()->role === 'dosen' ? 'Silakan hubungi Administrator jika penugasan mengajar Anda belum tercatat.' : 'Coba ubah filter pencarian atau tambahkan kelas baru.' }}
                    </p>
                    @if (auth()->user()->role === 'admin')
                        <a href="{{ route('course-sections.index', ['academic_period_id' => 'all']) }}" class="mt-5 inline-flex items-center gap-1.5 rounded-xl border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50 hover:text-slate-900">
                            <svg class="size-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                            </svg>
                            Lihat semua kelas
                        </a>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left">
                        <thead class="bg-slate-50/75">
                            <tr>
                                <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-slate-500">Kelas</th>
                                <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-slate-500">Mata Kuliah</th>
                                <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-slate-500">Dosen Pengampu</th>
                                <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-slate-500">Hari</th>
                                <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-slate-500">Jam Mulai &amp; Selesai</th>
                                <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-slate-500">Ruangan / Mode</th>
                                <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-slate-500">Periode</th>
                                <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($courseSections as $courseSection)
                                <tr class="transition hover:bg-slate-50/80">
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="text-sm font-bold text-slate-900">{{ $courseSection->name }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="inline-flex items-center rounded-md border border-blue-100 bg-blue-50/80 px-2 py-0.5 font-mono text-xs font-semibold text-blue-700">
                                            {{ $courseSection->course->code }}
                                        </span>
                                        <span class="mt-0.5 block text-xs text-slate-600">{{ $courseSection->course->name }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-xs text-slate-700">
                                        @forelse ($courseSection->lecturers as $assignment)
                                            <div class="font-medium text-slate-900">{{ $assignment->user?->name ?? '-' }}</div>
                                        @empty
                                            <span class="text-slate-400 italic">Belum ditugaskan</span>
                                        @endforelse
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-700">
                                        @forelse ($courseSection->schedules as $schedule)
                                            <div class="font-medium text-slate-900">{{ $schedule->dayName() }}</div>
                                        @empty
                                            <span class="text-slate-400 italic">Belum ada jadwal</span>
                                        @endforelse
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-700">
                                        @forelse ($courseSection->schedules as $schedule)
                                            <div class="font-mono">{{ substr((string) $schedule->starts_at, 0, 5) }} - {{ substr((string) $schedule->ends_at, 0, 5) }} <span class="font-sans text-slate-500">({{ ucfirst($schedule->session_type) }})</span></div>
                                        @empty
                                            <span class="text-slate-400">-</span>
                                        @endforelse
                                    </td>
                                    <td class="px-5 py-4 text-xs text-slate-700">
                                        @forelse ($courseSection->schedules as $schedule)
                                            <div>
                                                <span class="font-medium capitalize text-slate-900">{{ $schedule->delivery_mode }}</span>
                                                &middot; {{ $schedule->delivery_mode === 'online' ? ($schedule->meeting_url ?? 'Online') : ($schedule->location ?? '-') }}
                                            </div>
                                        @empty
                                            <span class="text-slate-400">-</span>
                                        @endforelse
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-700">
                                        <span class="block font-medium text-slate-900">{{ $courseSection->academicPeriod?->name ?? 'Belum ditentukan' }}</span>
                                        <span class="block text-slate-500">{{ $courseSection->academic_year }} &middot; <span class="capitalize">{{ $courseSection->semester }}</span></span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        @if ($courseSection->is_active)
                                            <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200/70 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                                <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                                Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                                <span class="size-1.5 rounded-full bg-slate-400"></span>
                                                Nonaktif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        <div class="inline-flex items-center justify-end gap-1.5">
                                            <a
                                                href="{{ route('course-sections.attendance-sessions.index', $courseSection) }}"
                                                class="inline-flex items-center gap-1 rounded-lg border border-blue-200 bg-blue-50/80 px-2.5 py-1.5 text-xs font-semibold text-blue-700 shadow-2xs transition hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                            >
                                                {{ auth()->user()->role === 'dosen' ? 'Kelola Absensi' : 'Absensi' }}
                                            </a>
                                            <a
                                                href="{{ route('course-sections.attendance-recap', $courseSection) }}"
                                                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-slate-500/20"
                                            >
                                                Rekap
                                            </a>
                                            @if (auth()->user()->role === 'admin')
                                                <a
                                                    href="{{ route('course-sections.schedules.index', $courseSection) }}"
                                                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-slate-500/20"
                                                >
                                                    Jadwal
                                                </a>
                                                <a
                                                    href="{{ route('course-sections.show', $courseSection) }}"
                                                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-slate-500/20"
                                                >
                                                    Detail
                                                </a>
                                                <a
                                                    href="{{ route('course-sections.edit', $courseSection) }}"
                                                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-slate-500/20"
                                                >
                                                    Edit
                                                </a>
                                                <form
                                                    class="inline-block"
                                                    method="POST"
                                                    action="{{ route('course-sections.status', $courseSection) }}"
                                                    onsubmit="{{ $courseSection->is_active ? "return confirm('Nonaktifkan kelas ini?')" : '' }}"
                                                >
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="is_active" value="{{ $courseSection->is_active ? '0' : '1' }}">
                                                    <button
                                                        type="submit"
                                                        class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs font-semibold shadow-2xs transition focus:outline-none focus:ring-2 {{ $courseSection->is_active ? 'border-red-200 bg-red-50/60 text-red-600 hover:bg-red-100 hover:text-red-700 focus:ring-red-500/20' : 'border-emerald-200 bg-emerald-50/60 text-emerald-700 hover:bg-emerald-100 focus:ring-emerald-500/20' }}"
                                                    >
                                                        {{ $courseSection->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($courseSections->hasPages())
                    <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-200 px-5 py-3.5 sm:flex-row">
                        <p class="text-xs text-slate-500">
                            Menampilkan <span class="font-semibold text-slate-800">{{ $courseSections->firstItem() ?? 0 }}</span> &ndash; <span class="font-semibold text-slate-800">{{ $courseSections->lastItem() ?? 0 }}</span> dari <span class="font-semibold text-slate-800">{{ $courseSections->total() }}</span> kelas
                        </p>
                        <div>
                            {{ $courseSections->links() }}
                        </div>
                    </div>
                @endif
            @endif
        </section>
    </div>
@endsection
