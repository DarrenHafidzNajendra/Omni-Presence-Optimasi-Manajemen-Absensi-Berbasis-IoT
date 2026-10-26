@extends('layouts.app')

@section('title', 'Jadwal Kelas')

@section('content')
    <div class="mx-auto max-w-5xl space-y-8">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-2">
                <p class="text-sm font-semibold tracking-wide text-blue-700">AKADEMIK</p>
                <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Jadwal Kelas</h1>
                <p class="text-sm leading-6 text-slate-600 sm:text-base">
                    {{ $courseSection->course->code }} - {{ $courseSection->course->name }} · {{ $courseSection->name }}
                </p>
            </div>
            <a href="{{ route('course-sections.schedules.create', $courseSection) }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/25">
                Tambah Jadwal
            </a>
        </section>

        @if (session('success'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if ($schedules->isEmpty())
                <p class="px-5 py-8 text-sm text-slate-500 sm:px-8">Belum ada jadwal untuk kelas ini.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-500">Sesi</th>
                                <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-500">Hari & Waktu</th>
                                <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-500">Lokasi</th>
                                <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-500">Periode</th>
                                <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($schedules as $schedule)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                                        <span class="font-semibold capitalize text-slate-900">{{ $schedule->session_type }}</span>
                                        <span class="block text-xs capitalize text-slate-500">{{ $schedule->delivery_mode }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                                        {{ $days[$schedule->day_of_week] ?? '-' }}<br>
                                        {{ substr((string) $schedule->starts_at, 0, 5) }} - {{ substr((string) $schedule->ends_at, 0, 5) }}
                                    </td>
                                    <td class="max-w-xs px-5 py-4 text-sm text-slate-700">
                                        @if ($schedule->delivery_mode === 'online')
                                            <a href="{{ $schedule->meeting_url }}" target="_blank" rel="noopener noreferrer" class="break-all text-blue-700 hover:underline">{{ $schedule->meeting_url }}</a>
                                        @else
                                            {{ $schedule->location }}
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                                        {{ $schedule->valid_from?->format('d/m/Y') ?? 'Tanpa batas' }} -
                                        {{ $schedule->valid_until?->format('d/m/Y') ?? 'Tanpa batas' }}
                                    </td>
                                    <td class="px-5 py-4 text-sm">
                                        <span @class(['inline-flex rounded-full px-2.5 py-1 text-xs font-medium', 'bg-emerald-100 text-emerald-800' => $schedule->is_active, 'bg-red-100 text-red-800' => ! $schedule->is_active])>{{ $schedule->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm">
                                        <a href="{{ route('course-sections.schedules.edit', [$courseSection, $schedule]) }}" class="font-semibold text-blue-700 hover:text-blue-900">Edit</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <a href="{{ route('course-sections.show', $courseSection) }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-500/15">Kembali ke Detail Kelas</a>
    </div>
@endsection
