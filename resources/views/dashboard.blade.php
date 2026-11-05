@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="min-w-0 space-y-6">
        {{-- Header Section --}}
        <section class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0 space-y-1.5">
                <div class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                    <svg class="size-3.5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    <span>RINGKASAN OPERASIONAL</span>
                </div>
                <h1 class="break-words text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Dashboard kehadiran</h1>
                <p class="max-w-2xl break-words text-sm text-slate-600">
                    Pantau ringkasan absensi dan kesiapan perangkat dari satu tempat.
                </p>
            </div>
            <div class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-medium text-slate-600 shadow-2xs">
                <svg class="size-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                <span>{{ now()->format('d/m/Y') }}</span>
            </div>
        </section>

        @if (session('success'))
            <div role="status" class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-2xs">
                <svg class="size-5 shrink-0 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <div class="flex-1">{{ session('success') }}</div>
            </div>
        @endif

        @error('enrollment')
            <div role="alert" class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 shadow-2xs">
                <svg class="size-5 shrink-0 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                <div class="flex-1">{{ $message }}</div>
            </div>
        @enderror

        {{-- Admin: Statistik Pengguna --}}
        @if ($userStatistics !== null)
            <section aria-labelledby="users-heading" class="min-w-0 space-y-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 id="users-heading" class="text-base font-bold text-slate-950 sm:text-lg">Statistik pengguna</h2>
                        <p class="mt-0.5 break-words text-xs text-slate-500 sm:text-sm">
                            Ringkasan akun sistem &middot; {{ $academicStatistics['course_sections'] ?? 0 }} kelas aktif &middot; {{ $academicStatistics['open_sessions'] ?? 0 }} sesi terbuka.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <a href="{{ route('users.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50">Kelola Pengguna</a>
                        <a href="{{ route('courses.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50">Mata Kuliah</a>
                        <a href="{{ route('academic-periods.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50">Periode Akademik</a>
                        <a href="{{ route('course-sections.index') }}" class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs transition hover:bg-blue-700">Kelola Kelas & Presensi</a>
                    </div>
                </div>

                <div class="grid min-w-0 grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                    @foreach ([
                        ['key' => 'total', 'label' => 'Total pengguna', 'tone' => 'bg-blue-500'],
                        ['key' => 'admin', 'label' => 'Admin', 'tone' => 'bg-violet-500'],
                        ['key' => 'dosen', 'label' => 'Dosen', 'tone' => 'bg-emerald-500'],
                        ['key' => 'mahasiswa', 'label' => 'Mahasiswa', 'tone' => 'bg-amber-500'],
                    ] as $statistic)
                        <article class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs transition hover:border-slate-300">
                            <div class="flex items-center gap-2">
                                <span class="size-2.5 rounded-full {{ $statistic['tone'] }}" aria-hidden="true"></span>
                                <h3 class="break-words text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $statistic['label'] }}</h3>
                            </div>
                            <p class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl" data-statistic="{{ $statistic['key'] }}">{{ number_format($userStatistics[$statistic['key']]) }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Statistik Kehadiran --}}
        <section aria-labelledby="attendance-heading" class="min-w-0 space-y-3">
            <div>
                <h2 id="attendance-heading" class="text-base font-bold text-slate-950 sm:text-lg">Statistik kehadiran</h2>
                <p class="mt-0.5 break-words text-xs text-slate-500 sm:text-sm">
                    @if ($attendanceStatistics !== null && ($attendanceStatistics['scope'] ?? 'today') === 'today')
                        Ringkasan presensi yang tercatat pada sesi hari ini.
                    @elseif ($attendanceStatistics !== null)
                        Belum ada sesi pada hari ini; menampilkan ringkasan presensi kumulatif yang tercatat di sistem.
                    @else
                        Ringkasan akan muncul setelah data absensi tersedia.
                    @endif
                </p>
            </div>

            <div class="grid min-w-0 grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                @if ($attendanceStatistics !== null)
                    <article class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs transition hover:border-slate-300">
                        <div class="flex items-center gap-2">
                            <span class="size-2.5 rounded-full bg-blue-500" aria-hidden="true"></span>
                            <h3 class="break-words text-xs font-semibold uppercase tracking-wider text-slate-500">
                                {{ ($attendanceStatistics['scope'] ?? 'today') === 'today' ? 'Kehadiran hari ini' : 'Total tercatat' }}
                            </h3>
                        </div>
                        <p class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl" data-statistic="attendance-total">{{ number_format($attendanceStatistics['total']) }}</p>
                    </article>
                    <article class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs transition hover:border-slate-300">
                        <div class="flex items-center gap-2">
                            <span class="size-2.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                            <h3 class="break-words text-xs font-semibold uppercase tracking-wider text-slate-500">Hadir</h3>
                        </div>
                        <p class="mt-2 text-2xl font-bold tracking-tight text-emerald-600 sm:text-3xl" data-statistic="attendance-hadir">{{ number_format($attendanceStatistics['hadir']) }}</p>
                    </article>
                    <article class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs transition hover:border-slate-300">
                        <div class="flex items-center gap-2">
                            <span class="size-2.5 rounded-full bg-amber-500" aria-hidden="true"></span>
                            <h3 class="break-words text-xs font-semibold uppercase tracking-wider text-slate-500">Terlambat</h3>
                        </div>
                        <p class="mt-2 text-2xl font-bold tracking-tight text-amber-600 sm:text-3xl" data-statistic="attendance-terlambat">{{ number_format($attendanceStatistics['terlambat']) }}</p>
                    </article>
                    <article class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs transition hover:border-slate-300">
                        <div class="flex items-center gap-2">
                            <span class="size-2.5 rounded-full bg-violet-500" aria-hidden="true"></span>
                            <h3 class="break-words text-xs font-semibold uppercase tracking-wider text-slate-500">Izin / Sakit</h3>
                        </div>
                        <p class="mt-2 text-2xl font-bold tracking-tight text-violet-600 sm:text-3xl" data-statistic="attendance-izin">{{ number_format($attendanceStatistics['izin'] + ($attendanceStatistics['sakit'] ?? 0)) }}</p>
                    </article>
                @else
                    @foreach ([
                        ['label' => 'Kehadiran hari ini', 'tone' => 'bg-blue-500'],
                        ['label' => 'Hadir', 'tone' => 'bg-emerald-500'],
                        ['label' => 'Terlambat', 'tone' => 'bg-amber-500'],
                        ['label' => 'Izin', 'tone' => 'bg-violet-500'],
                    ] as $statistic)
                        <article class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs">
                            <div class="flex items-center gap-2">
                                <span class="size-2.5 rounded-full {{ $statistic['tone'] }}" aria-hidden="true"></span>
                                <h3 class="break-words text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $statistic['label'] }}</h3>
                            </div>
                            <p class="mt-2 text-2xl font-bold tracking-tight text-slate-400 sm:text-3xl" aria-label="Data belum tersedia">
                                &mdash;
                            </p>
                            <p class="mt-1 text-xs text-slate-400">Data belum tersedia</p>
                        </article>
                    @endforeach
                @endif
            </div>
        </section>

        {{-- Dosen: Kelas yang diampu --}}
        @if (auth()->user()?->role === \App\Enums\UserRole::Dosen->value)
            <section aria-labelledby="my-sections-heading" class="min-w-0 space-y-3">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 id="my-sections-heading" class="text-base font-bold text-slate-950 sm:text-lg">Kelas yang diampu</h2>
                        <p class="mt-0.5 break-words text-xs text-slate-500 sm:text-sm">Fokus utama pengelolaan sesi perkuliahan dan rekapitulasi kelas Anda.</p>
                    </div>
                    <a href="{{ route('course-sections.index') }}" class="shrink-0 text-xs font-semibold text-blue-700 hover:underline">Lihat Semua Kelas</a>
                </div>

                @if ($myCourseSections->isNotEmpty())
                    @php
                        $nowJakarta = \Carbon\Carbon::now('Asia/Jakarta');
                        $sortedSections = $myCourseSections->sortBy(function ($section) use ($nowJakarta): string {
                            $hasTodaySchedule = $section->schedules->contains(fn ($sch) => $sch->isScheduledOnDate($nowJakarta) || $sch->resolveActiveMeetingDate($nowJakarta) !== null);

                            return ($hasTodaySchedule ? '0-' : '1-').$section->name;
                        })->values();
                    @endphp
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($sortedSections as $mySection)
                            @php
                                $sortedSchedules = $mySection->schedules->sortBy(function ($sch) use ($nowJakarta): string {
                                    $isActiveToday = $sch->resolveActiveMeetingDate($nowJakarta) !== null ? '0' : '1';
                                    $isSameWeekday = $sch->isScheduledOnDate($nowJakarta) ? '0' : '1';

                                    return sprintf('%s-%s-%02d-%s', $isActiveToday, $isSameWeekday, (int) $sch->day_of_week, (string) $sch->starts_at);
                                })->values();

                                $primarySchedule = $sortedSchedules->first();
                                $readySchedule = $sortedSchedules->first(function ($sch) use ($nowJakarta): bool {
                                    if (! $sch->canOpenSessionAt($nowJakarta)) {
                                        return false;
                                    }
                                    $meetingDate = $sch->resolveActiveMeetingDate($nowJakarta);
                                    $existing = $meetingDate ? $sch->findSessionForDate($meetingDate) : null;

                                    return $existing === null || $existing->canBeOpenedAt($nowJakarta);
                                });
                            @endphp
                            <article class="flex flex-col justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-xs transition hover:border-slate-300">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="inline-flex items-center rounded-md border border-blue-100 bg-blue-50/90 px-2 py-0.5 font-mono text-xs font-semibold text-blue-700">{{ $mySection->course->code }}</span>
                                        <span class="text-xs font-medium text-slate-500">{{ $mySection->active_students_count }} Mahasiswa Aktif</span>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-950">{{ $mySection->course->name }} &middot; {{ $mySection->name }}</h3>
                                    <p class="text-xs text-slate-600">
                                        @if ($primarySchedule !== null)
                                            Jadwal: {{ $primarySchedule->dayName() }}, {{ substr((string) $primarySchedule->starts_at, 0, 5) }} - {{ substr((string) $primarySchedule->ends_at, 0, 5) }}
                                            @if ($primarySchedule->isScheduledOnDate($nowJakarta))
                                                <span class="ml-1 inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-semibold text-blue-800">Hari Ini</span>
                                            @endif
                                        @else
                                            Belum ada jadwal tetap
                                        @endif
                                    </p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3">
                                    @if ($readySchedule !== null)
                                        <form method="POST" action="{{ route('course-sections.schedules.open-session', [$mySection, $readySchedule]) }}" class="flex-1">
                                            @csrf
                                            <button type="submit" class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-emerald-700 active:scale-[0.98]">
                                                <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                                                </svg>
                                                Buka Presensi
                                            </button>
                                        </form>
                                    @endif
                                    <a
                                        href="{{ route('course-sections.attendance-sessions.index', $mySection) }}"
                                        class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-blue-700 active:scale-[0.98]"
                                    >
                                        Kelola Sesi
                                    </a>
                                    <a
                                        href="{{ route('course-sections.attendance-recap', $mySection) }}"
                                        class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50 hover:border-slate-300"
                                    >
                                        Rekapitulasi
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-8 text-center text-sm text-slate-500">
                        Anda belum ditugaskan ke kelas aktif mana pun.
                    </div>
                @endif
            </section>
        @endif

        {{-- Mahasiswa: Kelas yang saya ikuti & Enrollment Mandiri --}}
        @if (auth()->user()?->role === \App\Enums\UserRole::Mahasiswa->value)
            <section aria-labelledby="student-enrolled-heading" class="min-w-0 space-y-3">
                <div>
                    <h2 id="student-enrolled-heading" class="text-base font-bold text-slate-950 sm:text-lg">Kelas yang saya ikuti</h2>
                    <p class="mt-0.5 break-words text-xs text-slate-500 sm:text-sm">Daftar mata kuliah dan kelas yang telah Anda daftarkan secara mandiri.</p>
                </div>

                @if (($studentEnrollments ?? collect())->isNotEmpty())
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($studentEnrollments as $enrollment)
                            @php
                                $enrolledSection = $enrollment->courseSection;
                            @endphp
                            @if ($enrolledSection)
                                <article class="flex flex-col justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-xs transition hover:border-slate-300">
                                    <div class="space-y-1.5">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="inline-flex rounded-md border border-blue-100 bg-blue-50/90 px-2 py-0.5 font-mono text-xs font-semibold text-blue-700">{{ $enrolledSection->course?->code }}</span>
                                            <span @class([
                                                'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium capitalize',
                                                'bg-emerald-50 text-emerald-700 border border-emerald-200/70' => $enrollment->status === 'aktif',
                                                'bg-slate-100 text-slate-700 border border-slate-200' => $enrollment->status !== 'aktif',
                                            ])>
                                                <span class="size-1.5 rounded-full {{ $enrollment->status === 'aktif' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                                {{ $enrollment->status }}
                                            </span>
                                        </div>
                                        <h3 class="text-sm font-bold text-slate-950">{{ $enrolledSection->course?->name }} &middot; {{ $enrolledSection->name }}</h3>
                                        <p class="text-xs text-slate-500">{{ $enrolledSection->academic_year }} &middot; <span class="capitalize">{{ $enrolledSection->semester }}</span></p>
                                    </div>
                                </article>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-8 text-center text-sm text-slate-500">
                        Anda belum terdaftar pada kelas mata kuliah mana pun. Silakan pilih kelas di bawah ini untuk mendaftar.
                    </div>
                @endif
            </section>

            <section aria-labelledby="student-self-enroll-heading" class="min-w-0 space-y-3">
                <div>
                    <h2 id="student-self-enroll-heading" class="text-base font-bold text-slate-950 sm:text-lg">Enrollment mandiri mata kuliah</h2>
                    <p class="mt-0.5 break-words text-xs text-slate-500 sm:text-sm">Pilih dan daftar ke kelas mata kuliah aktif secara mandiri melalui akun Anda.</p>
                </div>

                @if (($availableCourseSections ?? collect())->isNotEmpty())
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($availableCourseSections as $availableSection)
                            <article class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-xs transition hover:border-slate-300">
                                <div class="min-w-0 space-y-1">
                                    <span class="inline-flex rounded-md border border-blue-100 bg-blue-50/90 px-2 py-0.5 font-mono text-xs font-semibold text-blue-700">{{ $availableSection->course?->code }}</span>
                                    <h3 class="text-sm font-bold text-slate-950">{{ $availableSection->course?->name }} &middot; {{ $availableSection->name }}</h3>
                                    <p class="text-xs text-slate-500">{{ $availableSection->academic_year }} &middot; <span class="capitalize">{{ $availableSection->semester }}</span></p>
                                </div>
                                <form method="POST" action="{{ route('student.course-sections.enroll', $availableSection) }}" class="shrink-0">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-blue-700 active:scale-[0.98]">
                                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        Daftar Kelas
                                    </button>
                                </form>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-8 text-center text-sm text-slate-500">
                        Tidak ada kelas aktif lain yang tersedia untuk didaftarkan saat ini.
                    </div>
                @endif
            </section>
        @endif

        {{-- Sesi Perkuliahan Hari Ini --}}
        @if ($activeSessions->isNotEmpty())
            <section aria-labelledby="today-sessions-heading" class="min-w-0 space-y-3">
                <div>
                    <h2 id="today-sessions-heading" class="text-base font-bold text-slate-950 sm:text-lg">Sesi perkuliahan hari ini</h2>
                    <p class="mt-0.5 break-words text-xs text-slate-500 sm:text-sm">Sesi yang dijadwalkan atau sedang berlangsung hari ini.</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    @php
                        $nowJakartaSessions = \Carbon\Carbon::now('Asia/Jakarta');
                    @endphp
                    @foreach ($activeSessions as $activeSession)
                        @php
                            $activeDisplayStatus = $activeSession->displayStatus($nowJakartaSessions);
                        @endphp
                        <article class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-xs transition hover:border-slate-300">
                            <div class="min-w-0 space-y-1">
                                <div class="flex items-center gap-2">
                                    <span @class([
                                        'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize',
                                        'bg-emerald-50 text-emerald-700 border border-emerald-200/70' => $activeDisplayStatus === 'terbuka',
                                        'bg-blue-50 text-blue-700 border border-blue-200/70' => $activeDisplayStatus === 'terjadwal',
                                        'bg-slate-100 text-slate-700 border border-slate-200' => $activeDisplayStatus === 'ditutup',
                                        'bg-red-50 text-red-700 border border-red-200/70' => $activeDisplayStatus === 'dibatalkan',
                                        'bg-amber-50 text-amber-700 border border-amber-200/70' => $activeDisplayStatus === 'kedaluwarsa',
                                    ])>
                                        <span class="size-1.5 rounded-full {{ $activeDisplayStatus === 'terbuka' ? 'bg-emerald-500' : ($activeDisplayStatus === 'terjadwal' ? 'bg-blue-500' : 'bg-slate-400') }}"></span>
                                        {{ $activeDisplayStatus }}
                                    </span>
                                    <span class="text-xs font-medium text-slate-500">{{ $activeSession->courseSection->course->code }} &middot; {{ $activeSession->courseSection->name }}</span>
                                </div>
                                <h3 class="truncate text-sm font-semibold text-slate-900">{{ $activeSession->title }}</h3>
                                <p class="text-xs text-slate-500">{{ $activeSession->starts_at?->format('H:i') }} - {{ $activeSession->ends_at?->format('H:i') }} WIB</p>
                            </div>
                            <a
                                href="{{ route('course-sections.attendance-sessions.show', [$activeSession->courseSection, $activeSession]) }}"
                                class="shrink-0 inline-flex items-center justify-center gap-1 rounded-xl border border-blue-200 bg-blue-50/80 px-3.5 py-1.5 text-xs font-semibold text-blue-700 shadow-2xs transition hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            >
                                Buka Detail
                                <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                </svg>
                            </a>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Aktivitas Terbaru --}}
        <section aria-labelledby="activity-heading" class="min-w-0 space-y-3">
            <div>
                <h2 id="activity-heading" class="text-base font-bold text-slate-950 sm:text-lg">Aktivitas terbaru</h2>
                <p class="mt-0.5 break-words text-xs text-slate-500 sm:text-sm">
                    {{ $recentActivities->isNotEmpty() ? 'Catatan presensi terakhir yang tersimpan dalam sistem.' : 'Catatan aktivitas akan muncul saat sistem menerima data.' }}
                </p>
            </div>

            @if ($recentActivities->isNotEmpty())
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
                    <div class="divide-y divide-slate-100">
                        @foreach ($recentActivities as $activity)
                            <div class="flex items-center justify-between p-4 transition hover:bg-slate-50/80">
                                <div class="flex items-center gap-3 min-w-0">
                                    @php
                                        $actInitials = collect(explode(' ', $activity->student?->name ?? 'M'))
                                            ->filter()
                                            ->take(2)
                                            ->map(fn($p) => mb_substr($p, 0, 1))
                                            ->join('');
                                    @endphp
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-xs font-bold text-slate-700 shadow-2xs">
                                        {{ strtoupper($actInitials ?: 'M') }}
                                    </span>
                                    <div class="min-w-0 space-y-0.5">
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-semibold text-slate-900">{{ $activity->student?->name ?? 'Mahasiswa' }}</p>
                                            <span class="text-xs font-mono text-slate-400">({{ $activity->student?->nim ?? '-' }})</span>
                                        </div>
                                        <p class="text-xs text-slate-500">
                                            {{ $activity->attendanceSession?->courseSection?->course?->code }} &middot;
                                            {{ $activity->attendanceSession?->title }}
                                        </p>
                                    </div>
                                </div>
                                <div class="shrink-0 space-y-0.5 text-right">
                                    <span @class([
                                        'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize',
                                        'bg-emerald-50 text-emerald-700 border border-emerald-200/70' => in_array($activity->status, ['hadir', 'terlambat']),
                                        'bg-amber-50 text-amber-700 border border-amber-200/70' => $activity->status === 'izin',
                                        'bg-blue-50 text-blue-700 border border-blue-200/70' => $activity->status === 'sakit',
                                        'bg-red-50 text-red-700 border border-red-200/70' => $activity->status === 'tidak_hadir',
                                    ])>
                                        <span class="size-1.5 rounded-full {{ in_array($activity->status, ['hadir', 'terlambat']) ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                        {{ $activity->status }}
                                    </span>
                                    <p class="text-xs text-slate-400">
                                        {{ $activity->checked_in_at?->format('d/m/Y H:i') ?? '-' }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-8 text-center">
                    <h3 class="text-sm font-semibold text-slate-900">Belum ada aktivitas</h3>
                    <p class="mt-1 break-words text-xs text-slate-500">Aktivitas absensi terbaru akan ditampilkan di sini.</p>
                </div>
            @endif
        </section>

        {{-- Status Perangkat IoT & Metode Absensi --}}
        <section aria-labelledby="devices-heading" class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-xs sm:p-5">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 id="devices-heading" class="text-sm font-semibold text-slate-950">Kesiapan perangkat IoT &amp; metode absensi</h2>
                    <p class="text-xs text-slate-500">Belum ada data koneksi yang diterima dari perangkat keras.</p>
                </div>
                <span class="inline-flex w-fit items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                    <span class="size-1.5 rounded-full bg-slate-400"></span>
                    Menunggu integrasi IoT
                </span>
            </div>

            <div class="mt-3 grid grid-cols-1 gap-3 border-t border-slate-100 pt-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="flex items-center justify-between rounded-xl bg-slate-50 p-3 text-xs border border-slate-100">
                    <div>
                        <span class="font-semibold text-slate-900">Raspberry Pi</span>
                        <span class="block text-slate-500">Gateway absensi</span>
                    </div>
                    <span class="rounded-full bg-slate-200/70 px-2 py-0.5 font-medium text-slate-700">Belum ada data</span>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-slate-50 p-3 text-xs border border-slate-100">
                    <div>
                        <span class="font-semibold text-slate-900">ESP32 / Kamera</span>
                        <span class="block text-slate-500">Perangkat pembaca</span>
                    </div>
                    <span class="rounded-full bg-slate-200/70 px-2 py-0.5 font-medium text-slate-700">Belum ada data</span>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-slate-50 p-3 text-xs border border-slate-100">
                    <div>
                        <span class="font-semibold text-slate-900">QR Code</span>
                        <span class="block text-slate-500">Pemindaian QR</span>
                    </div>
                    <span class="rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-800">Belum dikonfigurasi</span>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-slate-50 p-3 text-xs border border-slate-100">
                    <div>
                        <span class="font-semibold text-slate-900">Face Recognition</span>
                        <span class="block text-slate-500">Pengenalan wajah</span>
                    </div>
                    <span class="rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-800">Belum dikonfigurasi</span>
                </div>
            </div>
        </section>
    </div>
@endsection
