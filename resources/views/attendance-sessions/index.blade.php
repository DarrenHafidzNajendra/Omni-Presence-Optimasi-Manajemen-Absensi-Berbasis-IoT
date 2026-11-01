@extends('layouts.app')

@section('title', 'Sesi Absensi')

@section('content')
    <div class="mx-auto max-w-7xl space-y-8">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-2">
                <p class="text-sm font-semibold tracking-wide text-blue-700">SESI ABSENSI</p>
                <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">{{ $courseSection->name }}</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('course-sections.attendance-recap', $courseSection) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">Rekap Kehadiran</a>
                <a href="{{ route('course-sections.attendance-sessions.create', $courseSection) }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">Buat Sesi Reguler</a>
                @if (auth()->user()->role === 'admin')
                    <a href="{{ route('course-sections.show', $courseSection) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Kembali ke Kelas</a>
                @else
                    <a href="{{ route('course-sections.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Kembali ke Kelas Saya</a>
                @endif
            </div>
        </section>

        @if (session('success'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $currentTime = $now ?? \Carbon\Carbon::now('Asia/Jakarta');
            $rawSchedules = $courseSection->relationLoaded('schedules')
                ? $courseSection->schedules
                : $courseSection->schedules()->where('is_active', true)->orderBy('day_of_week')->orderBy('starts_at')->get();

            $activeSchedules = $rawSchedules->sortBy(function ($schedule) use ($currentTime): string {
                $isActiveToday = $schedule->resolveActiveMeetingDate($currentTime) !== null ? '0' : '1';
                $isSameWeekday = $schedule->isScheduledOnDate($currentTime) ? '0' : '1';

                return sprintf('%s-%s-%02d-%s', $isActiveToday, $isSameWeekday, (int) $schedule->day_of_week, (string) $schedule->starts_at);
            })->values();
        @endphp

        @if ($activeSchedules->isNotEmpty())
            <section aria-labelledby="weekly-schedules-heading" class="space-y-4">
                <div>
                    <h2 id="weekly-schedules-heading" class="text-lg font-semibold text-slate-950">Jadwal Perkuliahan Mingguan</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Jadwal hari ini ditampilkan lebih dulu. Buka sesi presensi langsung ketika waktu mulai telah tercapai.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($activeSchedules as $schedule)
                        @php
                            $meetingDate = $schedule->resolveActiveMeetingDate($currentTime);
                            $existingSession = $meetingDate ? $schedule->findSessionForDate($meetingDate) : null;
                            $canOpenNow = $schedule->canOpenSessionAt($currentTime);
                            $isTodaySchedule = $schedule->isScheduledOnDate($currentTime);
                            $existingStatus = $existingSession?->displayStatus($currentTime);
                        @endphp
                        <article @class([
                            'flex flex-col justify-between gap-4 rounded-2xl border bg-white p-4 shadow-sm',
                            'border-blue-300 ring-1 ring-blue-100' => $isTodaySchedule,
                            'border-slate-200' => ! $isTodaySchedule,
                        ])>
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex rounded-md bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                                            {{ $schedule->dayName() }}, {{ substr((string) $schedule->starts_at, 0, 5) }} - {{ substr((string) $schedule->ends_at, 0, 5) }} WIB
                                        </span>
                                        @if ($isTodaySchedule)
                                            <span class="inline-flex rounded-full bg-blue-600 px-2 py-0.5 text-[11px] font-semibold text-white">
                                                Jadwal Hari Ini
                                            </span>
                                        @endif
                                    </div>
                                    <span class="text-xs font-medium capitalize text-slate-500">
                                        {{ $schedule->session_type }} &middot; {{ $schedule->delivery_mode }}
                                    </span>
                                </div>
                                @if ($schedule->location || $schedule->meeting_url)
                                    <p class="text-xs text-slate-500">{{ $schedule->location ?? $schedule->meeting_url }}</p>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3">
                                @if ($existingSession && ! $existingSession->isCancelled())
                                    @if ($existingSession->canBeOpenedAt($currentTime) && $canOpenNow)
                                        <form method="POST" action="{{ route('course-sections.schedules.open-session', [$courseSection, $schedule]) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                                                Buka Presensi
                                            </button>
                                        </form>
                                    @else
                                        <span @class([
                                            'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize',
                                            'bg-emerald-100 text-emerald-800' => $existingStatus === 'terbuka',
                                            'bg-slate-100 text-slate-700' => $existingStatus === 'ditutup',
                                            'bg-amber-100 text-amber-800' => $existingStatus === 'kedaluwarsa',
                                            'bg-blue-100 text-blue-800' => $existingStatus === 'terjadwal',
                                        ])>
                                            Sesi {{ $existingStatus }}
                                        </span>
                                    @endif
                                    <a href="{{ route('course-sections.attendance-sessions.show', [$courseSection, $existingSession]) }}" class="text-xs font-semibold text-blue-700 hover:underline">
                                        Lihat Sesi
                                    </a>
                                @elseif ($existingSession && $existingSession->isCancelled())
                                    <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800">
                                        Sesi dibatalkan
                                    </span>
                                    <a href="{{ route('course-sections.attendance-sessions.show', [$courseSection, $existingSession]) }}" class="text-xs font-semibold text-blue-700 hover:underline">
                                        Lihat Sesi
                                    </a>
                                @elseif ($canOpenNow)
                                    <span class="text-xs font-medium text-emerald-700">Waktu perkuliahan telah tiba</span>
                                    <form method="POST" action="{{ route('course-sections.schedules.open-session', [$courseSection, $schedule]) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                                            Buka Presensi
                                        </button>
                                    </form>
                                @elseif ($meetingDate !== null && $currentTime->lt($schedule->startsAtOnDate($meetingDate)))
                                    <span class="text-xs text-slate-500">
                                        Belum dimulai (Mulai pukul {{ substr((string) $schedule->starts_at, 0, 5) }} WIB)
                                    </span>
                                @elseif ($meetingDate !== null && $currentTime->gte($schedule->startsAtOnDate($meetingDate)->addHours(\App\Models\AttendanceSession::MAX_DURATION_HOURS)))
                                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                                        Sesi kedaluwarsa
                                    </span>
                                @else
                                    <span class="text-xs text-slate-500">
                                        Menunggu hari jadwal ({{ $schedule->dayName() }})
                                    </span>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <section aria-labelledby="session-history-heading" class="space-y-3">
            <h2 id="session-history-heading" class="text-lg font-semibold text-slate-950">Riwayat &amp; Daftar Sesi Pertemuan</h2>
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="divide-y divide-slate-100">
                    @forelse ($sessions as $session)
                        @php
                            $itemStatus = $session->displayStatus($currentTime);
                        @endphp
                        <div class="flex flex-wrap items-center justify-between gap-4 p-4">
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold text-slate-900">{{ $session->title }}</h3>
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize',
                                        'bg-emerald-100 text-emerald-800' => $itemStatus === 'terbuka',
                                        'bg-slate-100 text-slate-700' => $itemStatus === 'ditutup',
                                        'bg-red-100 text-red-800' => $itemStatus === 'dibatalkan',
                                        'bg-amber-100 text-amber-800' => $itemStatus === 'kedaluwarsa',
                                        'bg-blue-100 text-blue-800' => $itemStatus === 'terjadwal',
                                    ])>
                                        {{ $itemStatus }}
                                    </span>
                                    @if ($session->isUnexecutedOverdue($currentTime))
                                        <span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-800">
                                            Perlu Ditindaklanjuti
                                        </span>
                                    @endif
                                </div>
                                <p class="text-sm text-slate-500">
                                    {{ $session->starts_at?->format('d/m/Y H:i') }} - {{ $session->ends_at?->format('H:i') }} WIB
                                    &middot; <span class="capitalize">{{ $session->session_type }} ({{ $session->delivery_mode }})</span>
                                </p>
                            </div>
                            <a href="{{ route('course-sections.attendance-sessions.show', [$courseSection, $session]) }}" class="text-sm font-semibold text-blue-700 hover:text-blue-900">Lihat Detail</a>
                        </div>
                    @empty
                        <div class="p-8 text-center text-slate-500">Belum ada sesi absensi untuk kelas ini.</div>
                    @endforelse
                </div>
            </div>
        </section>

        {{ $sessions->links() }}
    </div>
@endsection

