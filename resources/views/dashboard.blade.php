@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="min-w-0 space-y-8">
        <section class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0 space-y-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-blue-700">RINGKASAN OPERASIONAL</p>
                <h1 class="break-words text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Dashboard kehadiran</h1>
                <p class="max-w-2xl break-words text-sm text-slate-600">
                    Pantau ringkasan absensi dan kesiapan perangkat dari satu tempat.
                </p>
            </div>
            <p class="shrink-0 text-sm font-medium text-slate-500">{{ now()->format('d/m/Y') }}</p>
        </section>

        @if ($userStatistics !== null)
            <section aria-labelledby="users-heading" class="min-w-0 space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 id="users-heading" class="text-lg font-semibold text-slate-950">Statistik pengguna</h2>
                        <p class="mt-0.5 break-words text-sm text-slate-500">
                            Ringkasan akun sistem &middot; {{ $academicStatistics['course_sections'] ?? 0 }} kelas aktif &middot; {{ $academicStatistics['open_sessions'] ?? 0 }} sesi terbuka.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('users.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">Kelola Pengguna</a>
                        <a href="{{ route('courses.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">Mata Kuliah</a>
                        <a href="{{ route('academic-periods.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">Periode Akademik</a>
                        <a href="{{ route('course-sections.index') }}" class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-700">Kelola Kelas & Presensi</a>
                    </div>
                </div>

                <div class="grid min-w-0 grid-cols-2 gap-4 lg:grid-cols-4">
                    @foreach ([
                        ['key' => 'total', 'label' => 'Total pengguna', 'tone' => 'bg-blue-500'],
                        ['key' => 'admin', 'label' => 'Admin', 'tone' => 'bg-violet-500'],
                        ['key' => 'dosen', 'label' => 'Dosen', 'tone' => 'bg-emerald-500'],
                        ['key' => 'mahasiswa', 'label' => 'Mahasiswa', 'tone' => 'bg-amber-500'],
                    ] as $statistic)
                        <article class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-center gap-2.5">
                                <span class="size-2.5 rounded-full {{ $statistic['tone'] }}" aria-hidden="true"></span>
                                <h3 class="break-words text-sm font-medium text-slate-600">{{ $statistic['label'] }}</h3>
                            </div>
                            <p class="mt-3 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl" data-statistic="{{ $statistic['key'] }}">{{ number_format($userStatistics[$statistic['key']]) }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <section aria-labelledby="attendance-heading" class="min-w-0 space-y-4">
            <div>
                <h2 id="attendance-heading" class="text-lg font-semibold text-slate-950">Statistik kehadiran</h2>
                <p class="mt-0.5 break-words text-sm text-slate-500">
                    @if ($attendanceStatistics !== null && ($attendanceStatistics['scope'] ?? 'today') === 'today')
                        Ringkasan presensi yang tercatat pada sesi hari ini.
                    @elseif ($attendanceStatistics !== null)
                        Belum ada sesi pada hari ini; menampilkan ringkasan presensi kumulatif yang tercatat di sistem.
                    @else
                        Ringkasan akan muncul setelah data absensi tersedia.
                    @endif
                </p>
            </div>

            <div class="grid min-w-0 grid-cols-2 gap-4 lg:grid-cols-4">
                @if ($attendanceStatistics !== null)
                    <article class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-center gap-2.5">
                            <span class="size-2.5 rounded-full bg-blue-500" aria-hidden="true"></span>
                            <h3 class="break-words text-sm font-medium text-slate-600">
                                {{ ($attendanceStatistics['scope'] ?? 'today') === 'today' ? 'Kehadiran hari ini' : 'Total tercatat' }}
                            </h3>
                        </div>
                        <p class="mt-3 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl" data-statistic="attendance-total">{{ number_format($attendanceStatistics['total']) }}</p>
                    </article>
                    <article class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-center gap-2.5">
                            <span class="size-2.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                            <h3 class="break-words text-sm font-medium text-slate-600">Hadir</h3>
                        </div>
                        <p class="mt-3 text-2xl font-bold tracking-tight text-emerald-600 sm:text-3xl" data-statistic="attendance-hadir">{{ number_format($attendanceStatistics['hadir']) }}</p>
                    </article>
                    <article class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-center gap-2.5">
                            <span class="size-2.5 rounded-full bg-amber-500" aria-hidden="true"></span>
                            <h3 class="break-words text-sm font-medium text-slate-600">Terlambat</h3>
                        </div>
                        <p class="mt-3 text-2xl font-bold tracking-tight text-amber-600 sm:text-3xl" data-statistic="attendance-terlambat">{{ number_format($attendanceStatistics['terlambat']) }}</p>
                    </article>
                    <article class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-center gap-2.5">
                            <span class="size-2.5 rounded-full bg-violet-500" aria-hidden="true"></span>
                            <h3 class="break-words text-sm font-medium text-slate-600">Izin / Sakit</h3>
                        </div>
                        <p class="mt-3 text-2xl font-bold tracking-tight text-violet-600 sm:text-3xl" data-statistic="attendance-izin">{{ number_format($attendanceStatistics['izin'] + ($attendanceStatistics['sakit'] ?? 0)) }}</p>
                    </article>
                @else
                    @foreach ([
                        ['label' => 'Kehadiran hari ini', 'tone' => 'bg-blue-500'],
                        ['label' => 'Hadir', 'tone' => 'bg-emerald-500'],
                        ['label' => 'Terlambat', 'tone' => 'bg-amber-500'],
                        ['label' => 'Izin', 'tone' => 'bg-violet-500'],
                    ] as $statistic)
                        <article class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-center gap-2.5">
                                <span class="size-2.5 rounded-full {{ $statistic['tone'] }}" aria-hidden="true"></span>
                                <h3 class="break-words text-sm font-medium text-slate-600">{{ $statistic['label'] }}</h3>
                            </div>
                            <p class="mt-3 text-2xl font-bold tracking-tight text-slate-400 sm:text-3xl" aria-label="Data belum tersedia">
                                &mdash;
                            </p>
                            <p class="mt-1 text-xs text-slate-500">Data belum tersedia</p>
                        </article>
                    @endforeach
                @endif
            </div>
        </section>

        @if (auth()->user()?->role === \App\Enums\UserRole::Dosen->value)
            <section aria-labelledby="my-sections-heading" class="min-w-0 space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 id="my-sections-heading" class="text-lg font-semibold text-slate-950">Kelas yang diampu</h2>
                        <p class="mt-0.5 break-words text-sm text-slate-500">Fokus utama pengelolaan sesi perkuliahan dan rekapitulasi kelas Anda.</p>
                    </div>
                    <a href="{{ route('course-sections.index') }}" class="shrink-0 text-xs font-semibold text-blue-700 hover:underline">Lihat Semua Kelas</a>
                </div>

                @if ($myCourseSections->isNotEmpty())
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($myCourseSections as $mySection)
                            <article class="flex flex-col justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="inline-flex rounded-md bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700">{{ $mySection->course->code }}</span>
                                        <span class="text-xs text-slate-500">{{ $mySection->active_students_count }} Mahasiswa Aktif</span>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-950">{{ $mySection->course->name }} &middot; {{ $mySection->name }}</h3>
                                    <p class="text-xs text-slate-600">
                                        @if ($mySection->schedules->isNotEmpty())
                                            Jadwal: {{ [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'][$mySection->schedules->first()->day_of_week] ?? '-' }}, {{ substr((string) $mySection->schedules->first()->starts_at, 0, 5) }} - {{ substr((string) $mySection->schedules->first()->ends_at, 0, 5) }}
                                        @else
                                            Belum ada jadwal tetap
                                        @endif
                                    </p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3">
                                    <a
                                        href="{{ route('course-sections.attendance-sessions.index', $mySection) }}"
                                        class="inline-flex flex-1 items-center justify-center rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700"
                                    >
                                        Kelola Sesi
                                    </a>
                                    <a
                                        href="{{ route('course-sections.attendance-recap', $mySection) }}"
                                        class="inline-flex flex-1 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                    >
                                        Rekapitulasi
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-sm text-slate-500">
                        Anda belum ditugaskan ke kelas aktif mana pun.
                    </div>
                @endif
            </section>
        @endif

        @if ($activeSessions->isNotEmpty())
            <section aria-labelledby="today-sessions-heading" class="min-w-0 space-y-4">
                <div>
                    <h2 id="today-sessions-heading" class="text-lg font-semibold text-slate-950">Sesi perkuliahan hari ini</h2>
                    <p class="mt-0.5 break-words text-sm text-slate-500">Sesi yang dijadwalkan atau sedang berlangsung hari ini.</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($activeSessions as $activeSession)
                        <article class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="min-w-0 space-y-1">
                                <div class="flex items-center gap-2">
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize',
                                        'bg-emerald-100 text-emerald-800' => $activeSession->status === 'terbuka',
                                        'bg-blue-100 text-blue-800' => $activeSession->status === 'terjadwal',
                                        'bg-slate-100 text-slate-700' => $activeSession->status === 'ditutup',
                                    ])>
                                        {{ $activeSession->status }}
                                    </span>
                                    <span class="text-xs font-medium text-slate-500">{{ $activeSession->courseSection->course->code }} &middot; {{ $activeSession->courseSection->name }}</span>
                                </div>
                                <h3 class="truncate text-sm font-semibold text-slate-900">{{ $activeSession->title }}</h3>
                                <p class="text-xs text-slate-500">{{ $activeSession->starts_at?->format('H:i') }} - {{ $activeSession->ends_at?->format('H:i') }} WIB</p>
                            </div>
                            <a
                                href="{{ route('course-sections.attendance-sessions.show', [$activeSession->courseSection, $activeSession]) }}"
                                class="shrink-0 inline-flex items-center justify-center rounded-lg border border-blue-600 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100"
                            >
                                Buka Detail
                            </a>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <section aria-labelledby="activity-heading" class="min-w-0 space-y-4">
            <div>
                <h2 id="activity-heading" class="text-lg font-semibold text-slate-950">Aktivitas terbaru</h2>
                <p class="mt-0.5 break-words text-sm text-slate-500">
                    {{ $recentActivities->isNotEmpty() ? 'Catatan presensi terakhir yang tersimpan dalam sistem.' : 'Catatan aktivitas akan muncul saat sistem menerima data.' }}
                </p>
            </div>

            @if ($recentActivities->isNotEmpty())
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="divide-y divide-slate-100">
                        @foreach ($recentActivities as $activity)
                            <div class="flex items-center justify-between p-4 transition hover:bg-slate-50/70">
                                <div class="min-w-0 space-y-0.5">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-semibold text-slate-900">{{ $activity->student?->name ?? 'Mahasiswa' }}</p>
                                        <span class="text-xs font-medium text-slate-500">({{ $activity->student?->nim ?? '-' }})</span>
                                    </div>
                                    <p class="text-xs text-slate-600">
                                        {{ $activity->attendanceSession?->courseSection?->course?->code }} &middot;
                                        {{ $activity->attendanceSession?->title }}
                                    </p>
                                </div>
                                <div class="shrink-0 space-y-0.5 text-right">
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize',
                                        'bg-emerald-100 text-emerald-800' => in_array($activity->status, ['hadir', 'terlambat']),
                                        'bg-amber-100 text-amber-800' => $activity->status === 'izin',
                                        'bg-blue-100 text-blue-800' => $activity->status === 'sakit',
                                        'bg-red-100 text-red-800' => $activity->status === 'tidak_hadir',
                                    ])>
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
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-6 text-center">
                    <h3 class="text-sm font-semibold text-slate-900">Belum ada aktivitas</h3>
                    <p class="mt-1 break-words text-xs text-slate-500">Aktivitas absensi terbaru akan ditampilkan di sini.</p>
                </div>
            @endif
        </section>

        {{-- Bagian Ringkas: Status Perangkat IoT & Metode Absensi --}}
        <section aria-labelledby="devices-heading" class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 id="devices-heading" class="text-sm font-semibold text-slate-950">Kesiapan perangkat IoT &amp; metode absensi</h2>
                    <p class="text-xs text-slate-500">Belum ada data koneksi yang diterima dari perangkat keras.</p>
                </div>
                <span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                    Menunggu integrasi IoT
                </span>
            </div>

            <div class="mt-3 grid grid-cols-1 gap-3 border-t border-slate-100 pt-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 text-xs">
                    <div>
                        <span class="font-semibold text-slate-900">Raspberry Pi</span>
                        <span class="block text-slate-500">Gateway absensi</span>
                    </div>
                    <span class="rounded-full bg-slate-200/70 px-2 py-0.5 font-medium text-slate-700">Belum ada data</span>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 text-xs">
                    <div>
                        <span class="font-semibold text-slate-900">ESP32 / Kamera</span>
                        <span class="block text-slate-500">Perangkat pembaca</span>
                    </div>
                    <span class="rounded-full bg-slate-200/70 px-2 py-0.5 font-medium text-slate-700">Belum ada data</span>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 text-xs">
                    <div>
                        <span class="font-semibold text-slate-900">QR Code</span>
                        <span class="block text-slate-500">Pemindaian QR</span>
                    </div>
                    <span class="rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-800">Belum dikonfigurasi</span>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 text-xs">
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
