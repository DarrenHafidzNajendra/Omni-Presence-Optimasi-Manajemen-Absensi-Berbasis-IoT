@extends('layouts.app')

@section('title', 'Daftar Kelas')

@section('content')
    <div class="space-y-8">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-2">
                <p class="text-sm font-semibold tracking-wide text-blue-700">AKADEMIK</p>
                <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Daftar Kelas</h1>
                <p class="max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                    Kelola kelas untuk mata kuliah yang tersedia di Omni-Presence.
                </p>
            </div>
            <a href="{{ route('course-sections.create') }}" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/25">
                Tambah Kelas
            </a>
        </section>

        @if (session('success'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        <section aria-label="Pencarian kelas" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <form method="GET" action="{{ route('course-sections.index') }}" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                <div class="flex-1 space-y-2">
                    <label for="search" class="block text-sm font-medium text-slate-700">Cari nama kelas atau mata kuliah</label>
                    <input id="search" name="search" type="search" value="{{ $search }}" placeholder="Masukkan nama kelas, kode, atau nama mata kuliah" class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/25">
                    Cari
                </button>
            </form>
        </section>

        <section aria-label="Hasil daftar kelas" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if ($courseSections->isEmpty())
                <div class="px-6 py-12 text-center">
                    <h2 class="text-base font-semibold text-slate-900">Kelas tidak ditemukan</h2>
                    <p class="mt-2 text-sm text-slate-500">Coba ubah kata pencarian atau tambahkan kelas baru.</p>
                    <a href="{{ route('course-sections.index') }}" class="mt-5 inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Lihat semua kelas
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-500">Mata Kuliah</th>
                                <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-500">Nama Kelas</th>
                                <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-500">Tahun Akademik</th>
                                <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-500">Semester</th>
                                <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($courseSections as $courseSection)
                                <tr class="transition hover:bg-slate-50/70">
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="block text-sm font-semibold text-slate-900">{{ $courseSection->course->code }}</span>
                                        <span class="block text-sm text-slate-600">{{ $courseSection->course->name }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">{{ $courseSection->name }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">{{ $courseSection->academic_year }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm capitalize text-slate-700">{{ $courseSection->semester }}</td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span @class([
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-medium',
                                            'bg-emerald-100 text-emerald-800' => $courseSection->is_active,
                                            'bg-red-100 text-red-800' => ! $courseSection->is_active,
                                        ])>
                                            {{ $courseSection->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="flex min-w-max flex-wrap items-center gap-2">
                                            <a
                                                href="{{ route('course-sections.show', $courseSection) }}"
                                                class="inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-500/15"
                                            >
                                                Detail
                                            </a>
                                            <a
                                                href="{{ route('course-sections.edit', $courseSection) }}"
                                                class="inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-500/15"
                                            >
                                                Edit
                                            </a>
                                            <form
                                                class="shrink-0"
                                                method="POST"
                                                action="{{ route('course-sections.status', $courseSection) }}"
                                                onsubmit="{{ $courseSection->is_active ? "return confirm('Nonaktifkan kelas ini?')" : '' }}"
                                            >
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="is_active" value="{{ $courseSection->is_active ? '0' : '1' }}">
                                                <button
                                                    type="submit"
                                                    @class([
                                                        'inline-flex items-center justify-center rounded-lg border px-3 py-2 text-sm font-semibold transition focus:outline-none focus:ring-4',
                                                        'border-red-300 text-red-700 hover:bg-red-50 focus:ring-red-500/15' => $courseSection->is_active,
                                                        'border-emerald-300 text-emerald-700 hover:bg-emerald-50 focus:ring-emerald-500/15' => ! $courseSection->is_active,
                                                    ])
                                                >
                                                    {{ $courseSection->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($courseSections->hasPages())
                    <div class="border-t border-slate-100 px-6 py-4">
                        {{ $courseSections->links() }}
                    </div>
                @endif
            @endif
        </section>
    </div>
@endsection
