@extends('layouts.app')

@section('title', 'Periode Akademik')

@section('content')
    <div class="space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1.5">
                <div class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                    <svg class="size-3.5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                    <span>AKADEMIK</span>
                </div>
                <div class="flex flex-wrap items-baseline gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Periode Akademik</h1>
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                        {{ number_format($academicPeriods->total()) }} periode
                    </span>
                </div>
                <p class="max-w-2xl text-sm leading-6 text-slate-600">
                    Kelola kalender periode akademik dan batas kelas pengganti.
                </p>
            </div>
            <a
                href="{{ route('academic-periods.create') }}"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 hover:shadow focus:outline-none focus:ring-4 focus:ring-blue-500/25 active:scale-[0.98]"
            >
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Periode
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

        <section aria-label="Daftar periode akademik" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
            @if ($academicPeriods->isEmpty())
                <div class="px-6 py-14 text-center">
                    <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                        </svg>
                    </div>
                    <h2 class="mt-4 text-base font-semibold text-slate-900">Belum ada periode akademik</h2>
                    <p class="mt-1 text-sm text-slate-500">Mulai dengan menambahkan periode akademik baru untuk sistem.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left">
                        <thead class="bg-slate-50/75">
                            <tr>
                                <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-slate-500">Nama</th>
                                <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-slate-500">Tahun/Semester</th>
                                <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-slate-500">Periode</th>
                                <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($academicPeriods as $period)
                                <tr class="transition hover:bg-slate-50/80">
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <a href="{{ route('academic-periods.show', $period) }}" class="text-sm font-semibold text-slate-900 transition hover:text-blue-600">
                                            {{ $period->name }}
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                                        <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                            {{ $period->academic_year }} <span class="capitalize">({{ $period->semester }})</span>
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                        <span class="inline-flex items-center gap-1.5 font-mono text-xs">
                                            <svg class="size-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                            {{ $period->starts_on->format('d/m/Y') }} &ndash; {{ $period->ends_on->format('d/m/Y') }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        @if ($period->is_active)
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
                                                href="{{ route('academic-periods.show', $period) }}"
                                                title="Lihat Detail"
                                                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                            >
                                                <svg class="size-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                </svg>
                                                Detail
                                            </a>
                                            <a
                                                href="{{ route('academic-periods.edit', $period) }}"
                                                title="Edit Periode"
                                                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                            >
                                                <svg class="size-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                </svg>
                                                Edit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($academicPeriods->hasPages())
                    <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-200 px-5 py-3.5 sm:flex-row">
                        <p class="text-xs text-slate-500">
                            Menampilkan <span class="font-semibold text-slate-800">{{ $academicPeriods->firstItem() ?? 0 }}</span> &ndash; <span class="font-semibold text-slate-800">{{ $academicPeriods->lastItem() ?? 0 }}</span> dari <span class="font-semibold text-slate-800">{{ $academicPeriods->total() }}</span> periode
                        </p>
                        <div>
                            {{ $academicPeriods->links() }}
                        </div>
                    </div>
                @endif
            @endif
        </section>
    </div>
@endsection
