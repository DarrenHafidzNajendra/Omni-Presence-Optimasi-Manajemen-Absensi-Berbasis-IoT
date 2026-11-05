@extends('layouts.app')

@section('title', 'Sesi Absensi')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1.5">
                <div class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                    <svg class="size-3.5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                    <span>SESI ABSENSI KELAS</span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">{{ $courseSection->name }}</h1>
                <p class="text-xs text-slate-500 sm:text-sm">
                    {{ $courseSection->course?->code }} &middot; {{ $courseSection->course?->name }} ({{ $courseSection->academicPeriod?->name ?? 'Periode Akademik' }})
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('course-sections.attendance-recap', $courseSection) }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50 hover:text-slate-900"
                >
                    <svg class="size-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                    Rekap Kehadiran
                </a>
                <a
                    href="{{ route('course-sections.attendance-sessions.create', $courseSection) }}"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-blue-700 active:scale-[0.98]"
                >
                    <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Buat Sesi Reguler
                </a>
                @if (auth()->user()->role === 'admin')
                    <a
                        href="{{ route('course-sections.show', $courseSection) }}"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50"
                    >
                        Kembali ke Kelas
                    </a>
                @else
                    <a
                        href="{{ route('course-sections.index') }}"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50"
                    >
                        Kembali ke Kelas Saya
                    </a>
                @endif
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

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-800 shadow-2xs">
                <ul class="list-disc pl-5 space-y-1">
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
            <section aria-labelledby="weekly-schedules-heading" class="space-y-3">
                <div>
                    <h2 id="weekly-schedules-heading" class="text-base font-bold text-slate-950 sm:text-lg">Jadwal Perkuliahan Mingguan</h2>
                    <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">Jadwal hari ini ditampilkan lebih dulu. Buka sesi presensi langsung ketika waktu mulai telah tercapai.</p>
                </div>

                <div class="grid gap-3 sm:gap-4 sm:grid-cols-2">
                    @foreach ($activeSchedules as $schedule)
                        @php
                            $meetingDate = $schedule->resolveActiveMeetingDate($currentTime);
                            $existingSession = $meetingDate ? $schedule->findSessionForDate($meetingDate) : null;
                            $canOpenNow = $schedule->canOpenSessionAt($currentTime);
                            $isTodaySchedule = $schedule->isScheduledOnDate($currentTime);
                            $existingStatus = $existingSession?->displayStatus($currentTime);
                        @endphp
                        <article @class([
                            'flex flex-col justify-between gap-4 rounded-2xl border bg-white p-4 shadow-xs transition hover:border-slate-300',
                            'border-blue-300 ring-2 ring-blue-500/10' => $isTodaySchedule,
                            'border-slate-200' => ! $isTodaySchedule,
                        ])>
                            <div class="space-y-1.5">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1 rounded-md border border-blue-100 bg-blue-50/90 px-2.5 py-0.5 font-mono text-xs font-semibold text-blue-700">
                                            {{ $schedule->dayName() }}, {{ substr((string) $schedule->starts_at, 0, 5) }} - {{ substr((string) $schedule->ends_at, 0, 5) }} WIB
                                        </span>
                                        @if ($isTodaySchedule)
                                            <span class="inline-flex items-center rounded-full bg-blue-600 px-2 py-0.5 text-[11px] font-semibold text-white">
                                                Jadwal Hari Ini
                                            </span>
                                        @endif
                                    </div>
                                    <span class="text-xs font-medium capitalize text-slate-500">
                                        {{ $schedule->session_type }} &middot; {{ $schedule->delivery_mode }}
                                    </span>
                                </div>
                                @if ($schedule->location || $schedule->meeting_url)
                                    <p class="text-xs text-slate-500">Lokasi: {{ $schedule->location ?? $schedule->meeting_url }}</p>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3">
                                @if ($existingSession && ! $existingSession->isCancelled())
                                    @if ($existingSession->canBeOpenedAt($currentTime) && $canOpenNow)
                                        <form method="POST" action="{{ route('course-sections.schedules.open-session', [$courseSection, $schedule]) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-emerald-700 active:scale-[0.98]">
                                                <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                                                </svg>
                                                Buka Presensi
                                            </button>
                                        </form>
                                    @else
                                        <span @class([
                                            'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize',
                                            'bg-emerald-50 text-emerald-700 border border-emerald-200/70' => $existingStatus === 'terbuka',
                                            'bg-slate-100 text-slate-700 border border-slate-200' => $existingStatus === 'ditutup',
                                            'bg-amber-50 text-amber-700 border border-amber-200/70' => $existingStatus === 'kedaluwarsa',
                                            'bg-blue-50 text-blue-700 border border-blue-200/70' => $existingStatus === 'terjadwal',
                                        ])>
                                            <span class="size-1.5 rounded-full {{ $existingStatus === 'terbuka' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                            Sesi {{ $existingStatus }}
                                        </span>
                                    @endif
                                    <a href="{{ route('course-sections.attendance-sessions.show', [$courseSection, $existingSession]) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-700 hover:text-blue-900 transition">
                                        Lihat Sesi
                                        <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                        </svg>
                                    </a>
                                @elseif ($existingSession && $existingSession->isCancelled())
                                    <span class="inline-flex items-center gap-1 rounded-full border border-red-200/70 bg-red-50 px-2.5 py-0.5 text-xs font-semibold text-red-700">
                                        <span class="size-1.5 rounded-full bg-red-500"></span>
                                        Sesi dibatalkan
                                    </span>
                                    <a href="{{ route('course-sections.attendance-sessions.show', [$courseSection, $existingSession]) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-700 hover:text-blue-900 transition">
                                        Lihat Sesi
                                        <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                        </svg>
                                    </a>
                                @elseif ($canOpenNow)
                                    <span class="text-xs font-medium text-emerald-700">Waktu perkuliahan telah tiba</span>
                                    <form method="POST" action="{{ route('course-sections.schedules.open-session', [$courseSection, $schedule]) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-emerald-700 active:scale-[0.98]">
                                            <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                                            </svg>
                                            Buka Presensi
                                        </button>
                                    </form>
                                @elseif ($meetingDate !== null && $currentTime->lt($schedule->startsAtOnDate($meetingDate)))
                                    <span class="text-xs text-slate-500">
                                        Belum dimulai (Mulai pukul {{ substr((string) $schedule->starts_at, 0, 5) }} WIB)
                                    </span>
                                @elseif ($meetingDate !== null && $currentTime->gte($schedule->startsAtOnDate($meetingDate)->addHours(\App\Models\AttendanceSession::MAX_DURATION_HOURS)))
                                    <span class="inline-flex items-center gap-1 rounded-full border border-amber-200/70 bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                                        <span class="size-1.5 rounded-full bg-amber-500"></span>
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

        {{-- Sesi Pertemuan List --}}
        <section aria-labelledby="session-history-heading" class="space-y-3">
            <h2 id="session-history-heading" class="text-base font-bold text-slate-950 sm:text-lg">Riwayat &amp; Daftar Sesi Pertemuan</h2>
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
                <div class="divide-y divide-slate-100">
                    @forelse ($sessions as $session)
                        @php
                            $itemStatus = $session->displayStatus($currentTime);
                        @endphp
                        <div class="flex flex-wrap items-center justify-between gap-4 p-4 transition hover:bg-slate-50/80">
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold text-slate-900">{{ $session->title }}</h3>
                                    <span @class([
                                        'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize',
                                        'bg-emerald-50 text-emerald-700 border border-emerald-200/70' => $itemStatus === 'terbuka',
                                        'bg-slate-100 text-slate-700 border border-slate-200' => $itemStatus === 'ditutup',
                                        'bg-red-50 text-red-700 border border-red-200/70' => $itemStatus === 'dibatalkan',
                                        'bg-amber-50 text-amber-700 border border-amber-200/70' => $itemStatus === 'kedaluwarsa',
                                        'bg-blue-50 text-blue-700 border border-blue-200/70' => $itemStatus === 'terjadwal',
                                    ])>
                                        <span class="size-1.5 rounded-full {{ $itemStatus === 'terbuka' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                        {{ $itemStatus }}
                                    </span>
                                    @if ($session->isUnexecutedOverdue($currentTime))
                                        <span class="inline-flex items-center rounded-full border border-red-200/70 bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">
                                            Perlu Ditindaklanjuti
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500 sm:text-sm">
                                    <span class="font-mono">{{ $session->starts_at?->format('d/m/Y H:i') }} - {{ $session->ends_at?->format('H:i') }} WIB</span>
                                    &middot; <span class="capitalize">{{ $session->session_type }} ({{ $session->delivery_mode }})</span>
                                </p>
                            </div>
                            <a
                                href="{{ route('course-sections.attendance-sessions.show', [$courseSection, $session]) }}"
                                class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 hover:text-blue-600"
                            >
                                Lihat Detail
                                <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                </svg>
                            </a>
                        </div>
                    @empty
                        <div class="p-10 text-center text-sm text-slate-500">Belum ada sesi absensi untuk kelas ini.</div>
                    @endforelse
                </div>
            </div>
        </section>

        @if ($sessions->hasPages())
            <div class="pt-2">
                {{ $sessions->links() }}
            </div>
        @endif
    </div>
@endsection
