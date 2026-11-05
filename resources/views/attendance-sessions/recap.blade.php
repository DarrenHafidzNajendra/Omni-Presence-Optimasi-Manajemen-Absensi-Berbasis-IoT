@extends('layouts.app')

@section('title', 'Rekap Kehadiran - ' . $courseSection->name)

@section('content')
    <div class="mx-auto max-w-7xl space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1.5">
                <div class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                    <svg class="size-3.5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                    <span>REKAP KEHADIRAN KELAS</span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">{{ $courseSection->name }}</h1>
                <p class="text-xs text-slate-500 sm:text-sm">
                    {{ $courseSection->course?->code }} &middot; {{ $courseSection->course?->name }} 
                    ({{ $courseSection->academicPeriod?->name ?? 'Periode Akademik Belum Ditentukan' }})
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a
                    href="{{ route('course-sections.attendance-sessions.index', $courseSection) }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50 hover:text-slate-900"
                >
                    <svg class="size-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                    Daftar Sesi Absensi
                </a>
                @if (auth()->user()->role === 'admin')
                    <a
                        href="{{ route('course-sections.show', $courseSection) }}"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50"
                    >
                        Detail Kelas
                    </a>
                @else
                    <a
                        href="{{ route('course-sections.index') }}"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50"
                    >
                        Kelas Saya
                    </a>
                @endif
            </div>
        </section>

        <!-- Summary Cards -->
        <section class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs transition hover:border-slate-300">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Mahasiswa Aktif</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ $recap['totalActiveStudents'] }}</p>
                <p class="mt-1 text-xs text-slate-500">Terdaftar aktif dalam kelas</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs transition hover:border-slate-300">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pertemuan Terlaksana</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ $recap['totalQualifyingSessions'] }}</p>
                <p class="mt-1 text-xs text-slate-500">Sesi ditutup & memenuhi kriteria rekap</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs transition hover:border-slate-300">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Rata-rata Kehadiran Kelas</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                    {{ $recap['overallPercentage'] !== null ? $recap['overallPercentage'] . '%' : '—' }}
                </p>
                <p class="mt-1 text-xs text-slate-500">Kumulatif seluruh mahasiswa aktif</p>
            </div>
        </section>

        <!-- Table Section -->
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
            <div class="border-b border-slate-200 bg-slate-50/75 px-5 py-3.5 sm:px-6">
                <h2 class="text-base font-bold text-slate-900">Tabel Kehadiran Mahasiswa</h2>
                <p class="mt-0.5 text-xs text-slate-500">
                    Persentase dihitung dari (Hadir + Terlambat) dibagi total pertemuan yang terlaksana.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50/60 text-slate-600">
                        <tr>
                            <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wider">NIM</th>
                            <th scope="col" class="px-5 py-3 text-xs font-semibold uppercase tracking-wider">Nama Mahasiswa</th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider">Pertemuan</th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-emerald-700">Hadir</th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-amber-700">Terlambat</th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-blue-700">Izin</th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-purple-700">Sakit</th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-red-700">Tidak Hadir</th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-400">Belum Dicatat</th>
                            <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider">% Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($recap['studentRecaps'] as $student)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs text-slate-500">
                                    {{ $student['nim'] ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 font-semibold text-slate-900">
                                    {{ $student['name'] }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-3.5 text-center font-medium text-slate-700">
                                    {{ $student['total_sessions'] }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-3.5 text-center font-semibold text-emerald-600">
                                    {{ $student['hadir'] }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-3.5 text-center font-semibold text-amber-600">
                                    {{ $student['terlambat'] }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-3.5 text-center text-blue-600">
                                    {{ $student['izin'] }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-3.5 text-center text-purple-600">
                                    {{ $student['sakit'] }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-3.5 text-center text-red-600">
                                    {{ $student['tidak_hadir'] }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-3.5 text-center text-slate-400">
                                    {{ $student['belum_dicatat'] }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-right font-semibold">
                                    @if ($student['percentage'] !== null)
                                        <span @class([
                                            'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold',
                                            'bg-emerald-50 text-emerald-700 border border-emerald-200/70' => $student['percentage'] >= 75,
                                            'bg-red-50 text-red-700 border border-red-200/70' => $student['percentage'] < 75,
                                        ])>
                                            {{ $student['percentage'] }}%
                                        </span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-12 text-center text-sm text-slate-500">
                                    Belum ada mahasiswa aktif yang terdaftar dalam kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
