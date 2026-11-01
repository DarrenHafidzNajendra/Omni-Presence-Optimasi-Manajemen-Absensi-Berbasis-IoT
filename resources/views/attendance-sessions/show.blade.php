@extends('layouts.app')

@section('title', 'Detail Sesi Absensi')

@section('content')
    <div class="mx-auto max-w-5xl space-y-8">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-2">
                <p class="text-sm font-semibold tracking-wide text-blue-700">{{ $courseSection->name }}</p>
                <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">{{ $session->title }}</h1>
            </div>
            <a href="{{ route('course-sections.attendance-sessions.index', $courseSection) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Daftar Sesi</a>
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

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-slate-500">Status</dt>
                    <dd class="text-base font-semibold capitalize text-slate-900">
                        {{ $session->status }}
                        @if ($session->isUnexecutedOverdue())
                            <span class="ml-2 inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800">
                                Belum Terlaksana / Perlu Ditindaklanjuti
                            </span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">Jenis Sesi</dt>
                    <dd class="text-base font-semibold capitalize text-slate-900">{{ $session->session_kind ?? 'regular' }} ({{ $session->session_type }})</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">Jadwal Perkuliahan</dt>
                    <dd class="text-sm text-slate-900">{{ $session->starts_at?->format('d/m/Y H:i') }} s/d {{ $session->ends_at?->format('H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">Waktu Absensi</dt>
                    <dd class="text-sm text-slate-900">{{ $session->attendance_opens_at?->format('d/m/Y H:i') }} s/d {{ $session->attendance_closes_at?->format('H:i') }}</dd>
                </div>
                @if ($session->opened_at)
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Dibuka Pada</dt>
                        <dd class="text-sm text-slate-900">{{ $session->opened_at->format('d/m/Y H:i') }} ({{ $session->openedBy?->name ?? '-' }})</dd>
                    </div>
                @endif
                @if ($session->closed_at)
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Ditutup Pada</dt>
                        <dd class="text-sm text-slate-900">{{ $session->closed_at->format('d/m/Y H:i') }} ({{ $session->closedBy?->name ?? '-' }})</dd>
                    </div>
                @endif
                @if ($session->cancelled_at)
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-slate-500">Dibatalkan Pada</dt>
                        <dd class="text-sm text-red-600">{{ $session->cancelled_at->format('d/m/Y H:i') }} oleh {{ $session->cancelledBy?->name ?? '-' }}: "{{ $session->cancellation_reason }}"</dd>
                    </div>
                @endif
                @if ($session->original_session_id)
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-slate-500">Sesi Asal</dt>
                        <dd class="text-sm text-slate-900">
                            <a href="{{ route('course-sections.attendance-sessions.show', [$courseSection, $session->originalSession]) }}" class="text-blue-600 underline">
                                {{ $session->originalSession?->title ?? "Sesi #{$session->original_session_id}" }}
                            </a>
                        </dd>
                    </div>
                @endif
            </dl>

            <div class="mt-6 flex flex-wrap gap-3 border-t border-slate-100 pt-6">
                @if ($session->status === 'terjadwal')
                    <form method="POST" action="{{ route('course-sections.attendance-sessions.open', [$courseSection, $session]) }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Buka Sesi</button>
                    </form>

                    <form method="POST" action="{{ route('course-sections.attendance-sessions.cancel', [$courseSection, $session]) }}" onsubmit="return confirm('Batalkan sesi ini?')">
                        @csrf
                        <input type="hidden" name="cancellation_reason" value="Dibatalkan melalui antarmuka kelas">
                        <button type="submit" class="rounded-lg border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Batalkan Sesi</button>
                    </form>
                @elseif ($session->status === 'terbuka')
                    <form method="POST" action="{{ route('course-sections.attendance-sessions.close', [$courseSection, $session]) }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Tutup Sesi</button>
                    </form>
                @endif

                @if ($session->session_kind !== 'replacement')
                    <a href="{{ route('course-sections.attendance-sessions.replacement.create', [$courseSection, $session]) }}" class="rounded-lg border border-blue-600 bg-white px-4 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50">
                        Buat Sesi Pengganti
                    </a>
                @endif
            </div>
        </section>

        {{-- Ringkasan Kehadiran Sesi --}}
        <section aria-labelledby="attendance-summary-heading" class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 id="attendance-summary-heading" class="text-lg font-bold text-slate-900">Ringkasan Kehadiran Sesi</h2>
                    <p class="text-xs text-slate-500">
                        Persentase dihitung berdasarkan catatan yang sudah tersedia pada sesi ini (hadir + terlambat), bukan persentase kumulatif satu semester.
                    </p>
                </div>
                <div class="inline-flex items-baseline gap-2 rounded-xl bg-slate-50 px-4 py-2 border border-slate-200">
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

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
                <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-3">
                    <div class="text-xs font-medium text-slate-500">Total Mahasiswa</div>
                    <div class="mt-1 text-2xl font-bold text-slate-900">{{ $summary['total'] }}</div>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-3">
                    <div class="text-xs font-medium text-emerald-700">Hadir</div>
                    <div class="mt-1 text-2xl font-bold text-emerald-700">{{ $summary['hadir'] }}</div>
                </div>
                <div class="rounded-xl border border-teal-200 bg-teal-50/50 p-3">
                    <div class="text-xs font-medium text-teal-700">Terlambat</div>
                    <div class="mt-1 text-2xl font-bold text-teal-700">{{ $summary['terlambat'] }}</div>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-3">
                    <div class="text-xs font-medium text-amber-700">Izin</div>
                    <div class="mt-1 text-2xl font-bold text-amber-700">{{ $summary['izin'] }}</div>
                </div>
                <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-3">
                    <div class="text-xs font-medium text-blue-700">Sakit</div>
                    <div class="mt-1 text-2xl font-bold text-blue-700">{{ $summary['sakit'] }}</div>
                </div>
                <div class="rounded-xl border border-red-200 bg-red-50/50 p-3">
                    <div class="text-xs font-medium text-red-700">Tidak Hadir</div>
                    <div class="mt-1 text-2xl font-bold text-red-700">{{ $summary['tidak_hadir'] }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-3">
                    <div class="text-xs font-medium text-slate-500">Belum Dicatat</div>
                    <div class="mt-1 text-2xl font-bold text-slate-600">{{ $summary['belum_dicatat'] }}</div>
                </div>
            </div>
        </section>

        {{-- Daftar Mahasiswa & Pencatatan Kehadiran --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Presensi Mahasiswa</h2>
                    <p class="text-xs text-slate-500">
                        @if ($isPastSessionDate)
                            <span class="text-amber-600 font-medium">Batas waktu lokal dosen telah berakhir. Hanya Admin yang dapat melakukan koreksi dengan alasan.</span>
                        @else
                            <span>Dosen dapat mencatat atau mengubah kehadiran hingga akhir hari tanggal sesi.</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-slate-700">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Mahasiswa</th>
                            <th class="px-6 py-3 font-semibold">NIM</th>
                            <th class="px-6 py-3 font-semibold">Status Presensi</th>
                            <th class="px-6 py-3 font-semibold">Waktu Presensi</th>
                            <th class="px-6 py-3 font-semibold">Catatan / Alasan</th>
                            @if (! $isPastSessionDate || auth()->user()->role === 'admin')
                                <th class="px-6 py-3 font-semibold text-right">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($enrollments as $enrollment)
                            @php
                                $student = $enrollment->student;
                                $record = $records->get($student->id);
                            @endphp
                            <tr>
                                <td class="px-6 py-4 font-medium text-slate-900">{{ $student->name }}</td>
                                <td class="px-6 py-4 text-slate-500">{{ $student->nim ?? '-' }}</td>
                                <td class="px-6 py-4">
                                    @if ($record)
                                        <span @class([
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize',
                                            'bg-emerald-100 text-emerald-800' => in_array($record->status, ['hadir', 'terlambat']),
                                            'bg-amber-100 text-amber-800' => $record->status === 'izin',
                                            'bg-blue-100 text-blue-800' => $record->status === 'sakit',
                                            'bg-red-100 text-red-800' => $record->status === 'tidak_hadir',
                                        ])>
                                            {{ $record->status }}
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                            Belum Dicatat
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-500">
                                    {{ $record?->checked_in_at?->format('d/m/Y H:i') ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-slate-500 max-w-xs truncate" title="{{ $record?->notes }}">
                                    {{ $record?->notes ?? '-' }}
                                </td>
                                @if (! $isPastSessionDate || auth()->user()->role === 'admin')
                                    <td class="px-6 py-4 text-right">
                                        <form method="POST" action="{{ route('course-sections.attendance-sessions.records.store', [$courseSection, $session]) }}" class="inline-flex items-center gap-2">
                                            @csrf
                                            <input type="hidden" name="student_id" value="{{ $student->id }}">
                                            <select name="status" class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs text-slate-700">
                                                <option value="hadir" @selected($record?->status === 'hadir')>Hadir</option>
                                                <option value="terlambat" @selected($record?->status === 'terlambat')>Terlambat</option>
                                                <option value="izin" @selected($record?->status === 'izin')>Izin</option>
                                                <option value="sakit" @selected($record?->status === 'sakit')>Sakit</option>
                                            </select>
                                            @if ($isPastSessionDate && auth()->user()->role === 'admin')
                                                <input type="text" name="notes" placeholder="Alasan koreksi..." required class="rounded-lg border border-slate-300 px-2 py-1 text-xs text-slate-700 w-36">
                                            @endif
                                            <button type="submit" class="rounded-lg bg-blue-600 px-3 py-1 text-xs font-semibold text-white hover:bg-blue-700">
                                                Simpan
                                            </button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-slate-500">
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
