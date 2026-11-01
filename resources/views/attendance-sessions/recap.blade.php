@extends('layouts.app')

@section('title', 'Rekap Kehadiran - ' . $courseSection->name)

@section('content')
    <div class="mx-auto max-w-7xl space-y-8">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-2">
                <p class="text-sm font-semibold tracking-wide text-blue-700">REKAP KEHADIRAN KELAS</p>
                <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">{{ $courseSection->name }}</h1>
                <p class="text-sm text-slate-600">
                    {{ $courseSection->course?->code }} - {{ $courseSection->course?->name }} 
                    ({{ $courseSection->academicPeriod?->name ?? 'Periode Akademik Belum Ditentukan' }})
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('course-sections.attendance-sessions.index', $courseSection) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                    Daftar Sesi Absensi
                </a>
                @if (auth()->user()->role === 'admin')
                    <a href="{{ route('course-sections.show', $courseSection) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                        Detail Kelas
                    </a>
                @else
                    <a href="{{ route('course-sections.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                        Kelas Saya
                    </a>
                @endif
            </div>
        </section>

        <!-- Summary Cards -->
        <section class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Mahasiswa Aktif</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ $recap['totalActiveStudents'] }}</p>
                <p class="mt-1 text-xs text-slate-500">Terdaftar aktif dalam kelas</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Pertemuan Terlaksana</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ $recap['totalQualifyingSessions'] }}</p>
                <p class="mt-1 text-xs text-slate-500">Sesi ditutup & memenuhi kriteria rekap</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Rata-rata Kehadiran Kelas</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                    {{ $recap['overallPercentage'] !== null ? $recap['overallPercentage'] . '%' : '—' }}
                </p>
                <p class="mt-1 text-xs text-slate-500">Kumulatif seluruh mahasiswa aktif</p>
            </div>
        </section>

        <!-- Table Section -->
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="text-base font-semibold leading-6 text-slate-900">Tabel Kehadiran Mahasiswa</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Persentase dihitung dari (Hadir + Terlambat) dibagi total pertemuan yang terlaksana.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-3.5 font-semibold text-slate-900">NIM</th>
                            <th scope="col" class="px-6 py-3.5 font-semibold text-slate-900">Nama Mahasiswa</th>
                            <th scope="col" class="px-4 py-3.5 text-center font-semibold text-slate-900">Pertemuan</th>
                            <th scope="col" class="px-4 py-3.5 text-center font-semibold text-emerald-700">Hadir</th>
                            <th scope="col" class="px-4 py-3.5 text-center font-semibold text-amber-700">Terlambat</th>
                            <th scope="col" class="px-4 py-3.5 text-center font-semibold text-blue-700">Izin</th>
                            <th scope="col" class="px-4 py-3.5 text-center font-semibold text-purple-700">Sakit</th>
                            <th scope="col" class="px-4 py-3.5 text-center font-semibold text-rose-700">Tidak Hadir</th>
                            <th scope="col" class="px-4 py-3.5 text-center font-semibold text-slate-500">Belum Dicatat</th>
                            <th scope="col" class="px-6 py-3.5 text-right font-semibold text-slate-900">% Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($recap['studentRecaps'] as $student)
                            <tr class="hover:bg-slate-50/50">
                                <td class="whitespace-nowrap px-6 py-4 font-medium text-slate-900">
                                    {{ $student['nim'] ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-slate-900">
                                    {{ $student['name'] }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-center font-medium text-slate-700">
                                    {{ $student['total_sessions'] }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-center font-semibold text-emerald-600">
                                    {{ $student['hadir'] }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-center font-semibold text-amber-600">
                                    {{ $student['terlambat'] }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-center text-blue-600">
                                    {{ $student['izin'] }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-center text-purple-600">
                                    {{ $student['sakit'] }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-center text-rose-600">
                                    {{ $student['tidak_hadir'] }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-center text-slate-400">
                                    {{ $student['belum_dicatat'] }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right font-semibold text-slate-900">
                                    @if ($student['percentage'] !== null)
                                        <span class="{{ $student['percentage'] >= 75 ? 'text-emerald-700' : 'text-rose-700' }}">
                                            {{ $student['percentage'] }}%
                                        </span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-12 text-center text-slate-500">
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

