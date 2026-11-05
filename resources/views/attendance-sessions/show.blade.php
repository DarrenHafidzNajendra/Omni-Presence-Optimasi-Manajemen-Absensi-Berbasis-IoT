@extends('layouts.app')

@section('title', 'Detail Sesi Absensi')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1.5">
                <div class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                    <span>{{ $courseSection->name }}</span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">{{ $session->title }}</h1>
                <p class="text-xs text-slate-500 sm:text-sm">
                    {{ $courseSection->course?->code }} &middot; {{ $courseSection->course?->name }}
                </p>
            </div>
            <a
                href="{{ route('course-sections.attendance-sessions.index', $courseSection) }}"
                class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50"
            >
                <svg class="size-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Daftar Sesi
            </a>
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
            $currentTime = \Carbon\Carbon::now('Asia/Jakarta');
            $displayStatus = $session->displayStatus($currentTime);
        @endphp

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Status</dt>
                    <dd class="mt-1 flex flex-wrap items-center gap-2 text-base font-semibold capitalize text-slate-900">
                        <span @class([
                            'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize',
                            'bg-emerald-50 text-emerald-700 border border-emerald-200/70' => $displayStatus === 'terbuka',
                            'bg-slate-100 text-slate-700 border border-slate-200' => $displayStatus === 'ditutup',
                            'bg-red-50 text-red-700 border border-red-200/70' => $displayStatus === 'dibatalkan',
                            'bg-amber-50 text-amber-700 border border-amber-200/70' => $displayStatus === 'kedaluwarsa',
                            'bg-blue-50 text-blue-700 border border-blue-200/70' => $displayStatus === 'terjadwal',
                        ])>
                            <span class="size-1.5 rounded-full {{ $displayStatus === 'terbuka' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                            {{ $displayStatus }}
                        </span>
                        @if ($session->isUnexecutedOverdue($currentTime))
                            <span class="inline-flex items-center rounded-full border border-red-200/70 bg-red-50 px-2.5 py-0.5 text-xs font-semibold text-red-700">
                                Belum Terlaksana / Perlu Ditindaklanjuti
                            </span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Jenis Sesi</dt>
                    <dd class="mt-1 text-sm font-semibold capitalize text-slate-900">{{ $session->session_kind ?? 'regular' }} ({{ $session->session_type }})</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Jadwal Perkuliahan</dt>
                    <dd class="mt-1 font-mono text-sm text-slate-900">{{ $session->starts_at?->format('d/m/Y H:i') }} s/d {{ $session->ends_at?->format('H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Waktu Absensi</dt>
                    <dd class="mt-1 font-mono text-sm text-slate-900">{{ $session->attendance_opens_at?->format('d/m/Y H:i') }} s/d {{ $session->attendance_closes_at?->format('H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Metode Absensi</dt>
                    <dd class="mt-1 text-sm text-slate-900">
                        {{ collect($session->allowed_methods ?? ['manual'])->map(fn ($m) => ['manual' => 'Manual', 'qr_code' => 'QR Code', 'face_recognition' => 'Face Recognition'][$m] ?? $m)->implode(', ') }}
                    </dd>
                </div>
                @if ($session->opened_at)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Dibuka Pada</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $session->opened_at->format('d/m/Y H:i') }} ({{ $session->openedBy?->name ?? '-' }})</dd>
                    </div>
                @endif
                @if ($session->closed_at)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Ditutup Pada</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $session->closed_at->format('d/m/Y H:i') }} ({{ $session->closedBy?->name ?? '-' }})</dd>
                    </div>
                @endif
                @if ($session->cancelled_at)
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Dibatalkan Pada</dt>
                        <dd class="mt-1 text-sm text-red-600">{{ $session->cancelled_at->format('d/m/Y H:i') }} oleh {{ $session->cancelledBy?->name ?? '-' }}: "{{ $session->cancellation_reason }}"</dd>
                    </div>
                @endif
                @if ($session->original_session_id)
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Sesi Asal</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            <a href="{{ route('course-sections.attendance-sessions.show', [$courseSection, $session->originalSession]) }}" class="text-blue-600 underline">
                                {{ $session->originalSession?->title ?? "Sesi #{$session->original_session_id}" }}
                            </a>
                        </dd>
                    </div>
                @endif
            </dl>

            <div class="mt-6 flex flex-wrap gap-2.5 border-t border-slate-100 pt-5">
                @if ($session->canBeOpenedAt($currentTime))
                    <form method="POST" action="{{ route('course-sections.attendance-sessions.open', [$courseSection, $session]) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-emerald-700 active:scale-[0.98]">
                            <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                            </svg>
                            Buka Sesi
                        </button>
                    </form>
                @endif

                @if ($session->isScheduled() && ! $session->isCancelled() && ! $session->isExpiredAt($currentTime))
                    <form method="POST" action="{{ route('course-sections.attendance-sessions.cancel', [$courseSection, $session]) }}" onsubmit="return confirm('Batalkan sesi ini?')">
                        @csrf
                        <input type="hidden" name="cancellation_reason" value="Dibatalkan melalui antarmuka kelas">
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-red-200 bg-red-50/60 px-4 py-2 text-xs font-semibold text-red-600 shadow-2xs transition hover:bg-red-100">
                            Batalkan Sesi
                        </button>
                    </form>
                @elseif ($session->isOpen() && ! $session->isCancelled())
                    <form method="POST" action="{{ route('course-sections.attendance-sessions.close', [$courseSection, $session]) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-amber-600 px-4 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-amber-700 active:scale-[0.98]">
                            Tutup Sesi
                        </button>
                    </form>
                @endif

                @if ($session->session_kind !== 'replacement')
                    <a href="{{ route('course-sections.attendance-sessions.replacement.create', [$courseSection, $session]) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-blue-600 bg-white px-4 py-2 text-xs font-semibold text-blue-700 shadow-2xs transition hover:bg-blue-50">
                        Buat Sesi Pengganti
                    </a>
                @endif
            </div>
        </section>

        {{-- Ringkasan Kehadiran Sesi --}}
        <section aria-labelledby="attendance-summary-heading" class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-xs sm:p-6">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 id="attendance-summary-heading" class="text-base font-bold text-slate-900 sm:text-lg">Ringkasan Kehadiran Sesi</h2>
                    <p class="text-xs text-slate-500">
                        Persentase dihitung berdasarkan catatan yang sudah tersedia pada sesi ini (hadir + terlambat), bukan persentase kumulatif satu semester.
                    </p>
                </div>
                <div class="inline-flex items-baseline gap-2 rounded-xl bg-slate-50 px-4 py-2 border border-slate-200/80 shadow-2xs">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Persentase Kehadiran:</span>
                    <span class="text-xl font-bold text-slate-900">
                        @if ($summary['percentage'] !== null)
                            {{ $summary['percentage'] }}%
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4 lg:grid-cols-7">
                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total Mahasiswa</div>
                    <div class="mt-1 text-2xl font-bold text-slate-900">{{ $summary['total'] }}</div>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Hadir</div>
                    <div class="mt-1 text-2xl font-bold text-emerald-700">{{ $summary['hadir'] }}</div>
                </div>
                <div class="rounded-xl border border-teal-200 bg-teal-50/60 p-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-teal-700">Terlambat</div>
                    <div class="mt-1 text-2xl font-bold text-teal-700">{{ $summary['terlambat'] }}</div>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-amber-700">Izin</div>
                    <div class="mt-1 text-2xl font-bold text-amber-700">{{ $summary['izin'] }}</div>
                </div>
                <div class="rounded-xl border border-blue-200 bg-blue-50/60 p-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-blue-700">Sakit</div>
                    <div class="mt-1 text-2xl font-bold text-blue-700">{{ $summary['sakit'] }}</div>
                </div>
                <div class="rounded-xl border border-red-200 bg-red-50/60 p-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-red-700">Tidak Hadir</div>
                    <div class="mt-1 text-2xl font-bold text-red-700">{{ $summary['tidak_hadir'] }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Belum Dicatat</div>
                    <div class="mt-1 text-2xl font-bold text-slate-600">{{ $summary['belum_dicatat'] }}</div>
                </div>
            </div>
        </section>

        {{-- Daftar Mahasiswa & Pencatatan Kehadiran --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
            <div class="border-b border-slate-200 bg-slate-50/75 px-5 py-3.5 sm:px-6">
                <h2 class="text-base font-bold text-slate-900">Presensi Mahasiswa</h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    @if ($isPastSessionDate)
                        <span class="text-amber-600 font-medium">Batas waktu lokal dosen telah berakhir. Hanya Admin yang dapat melakukan koreksi dengan alasan.</span>
                    @else
                        <span>Dosen dapat mencatat atau mengubah kehadiran hingga akhir hari tanggal sesi.</span>
                    @endif
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50/60 text-slate-600">
                        <tr>
                            <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider">Mahasiswa</th>
                            <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider">NIM</th>
                            <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider">Status Presensi</th>
                            <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider">Waktu Presensi</th>
                            <th class="px-5 py-3 text-xs font-semibold uppercase tracking-wider">Catatan / Alasan</th>
                            @if (! $isPastSessionDate || auth()->user()->role === 'admin')
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($enrollments as $enrollment)
                            @php
                                $student = $enrollment->student;
                                $record = $records->get($student->id);
                            @endphp
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="whitespace-nowrap px-5 py-3.5 font-semibold text-slate-900">{{ $student->name }}</td>
                                <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs text-slate-500">{{ $student->nim ?? '-' }}</td>
                                <td class="whitespace-nowrap px-5 py-3.5">
                                    @if ($record)
                                        <span @class([
                                            'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize',
                                            'bg-emerald-50 text-emerald-700 border border-emerald-200/70' => in_array($record->status, ['hadir', 'terlambat']),
                                            'bg-amber-50 text-amber-700 border border-amber-200/70' => $record->status === 'izin',
                                            'bg-blue-50 text-blue-700 border border-blue-200/70' => $record->status === 'sakit',
                                            'bg-red-50 text-red-700 border border-red-200/70' => $record->status === 'tidak_hadir',
                                        ])>
                                            <span class="size-1.5 rounded-full {{ in_array($record->status, ['hadir', 'terlambat']) ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                            {{ $record->status }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                                            Belum Dicatat
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs text-slate-500">
                                    {{ $record?->checked_in_at?->format('d/m/Y H:i') ?? '-' }}
                                </td>
                                <td class="max-w-xs truncate px-5 py-3.5 text-xs text-slate-500" title="{{ $record?->notes }}">
                                    {{ $record?->notes ?? '-' }}
                                </td>
                                @if (! $isPastSessionDate || auth()->user()->role === 'admin')
                                    <td class="whitespace-nowrap px-5 py-3.5 text-right">
                                        <form method="POST" action="{{ route('course-sections.attendance-sessions.records.store', [$courseSection, $session]) }}" class="inline-flex items-center gap-2">
                                            @csrf
                                            <input type="hidden" name="student_id" value="{{ $student->id }}">
                                            <select name="status" class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs text-slate-700 focus:border-blue-500 focus:outline-none">
                                                <option value="hadir" @selected($record?->status === 'hadir')>Hadir</option>
                                                <option value="terlambat" @selected($record?->status === 'terlambat')>Terlambat</option>
                                                <option value="izin" @selected($record?->status === 'izin')>Izin</option>
                                                <option value="sakit" @selected($record?->status === 'sakit')>Sakit</option>
                                            </select>
                                            @if ($isPastSessionDate && auth()->user()->role === 'admin')
                                                <input type="text" name="notes" placeholder="Alasan koreksi..." required class="rounded-lg border border-slate-300 px-2 py-1 text-xs text-slate-700 w-36">
                                            @endif
                                            <button type="submit" class="rounded-lg bg-blue-600 px-3 py-1 text-xs font-semibold text-white shadow-2xs transition hover:bg-blue-700 active:scale-[0.98]">
                                                Simpan
                                            </button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-slate-500">
                                    Tidak ada mahasiswa aktif terdaftar pada kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
