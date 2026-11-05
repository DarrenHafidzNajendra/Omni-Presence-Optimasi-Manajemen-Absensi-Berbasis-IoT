@extends('layouts.app')

@section('title', 'Daftar Mata Kuliah')

@section('content')
    <div class="space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1.5">
                <div class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                    <svg class="size-3.5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                    <span>AKADEMIK</span>
                </div>
                <div class="flex flex-wrap items-baseline gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Daftar Mata Kuliah</h1>
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                        {{ number_format($courses->total()) }} total
                    </span>
                </div>
                <p class="max-w-2xl text-sm leading-6 text-slate-600">
                    Kelola mata kuliah yang tersedia di Omni-Presence.
                </p>
            </div>
            <a
                href="{{ route('courses.create') }}"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 hover:shadow focus:outline-none focus:ring-4 focus:ring-blue-500/25 active:scale-[0.98]"
            >
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Mata Kuliah
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

        <section aria-label="Pencarian mata kuliah" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs sm:p-5">
            <form method="GET" action="{{ route('courses.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1 space-y-1.5">
                    <label for="search" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">Cari kode atau nama</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </div>
                        <input
                            id="search"
                            name="search"
                            type="search"
                            value="{{ $search }}"
                            placeholder="Masukkan kode atau nama mata kuliah"
                            class="block w-full rounded-xl border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/15 transition"
                        >
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if ($search !== '')
                        <a
                            href="{{ route('courses.index') }}"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-900"
                        >
                            Reset
                        </a>
                    @endif
                    <button
                        type="submit"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/25 active:scale-[0.98]"
                    >
                        Cari
                    </button>
                </div>
            </form>
        </section>

        <section aria-label="Hasil daftar mata kuliah" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
            @if ($courses->isEmpty())
                <div class="px-6 py-14 text-center">
                    <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                        </svg>
                    </div>
                    <h2 class="mt-4 text-base font-semibold text-slate-900">Mata kuliah tidak ditemukan</h2>
                    <p class="mt-1 text-sm text-slate-500">Coba ubah kata pencarian atau tambahkan mata kuliah baru.</p>
                    <a
                        href="{{ route('courses.index') }}"
                        class="mt-5 inline-flex items-center gap-1.5 rounded-xl border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50 hover:text-slate-900"
                    >
                        <svg class="size-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                        </svg>
                        Lihat semua mata kuliah
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left">
                        <thead class="bg-slate-50/75">
                            <tr>
                                <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-slate-500">Kode</th>
                                <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-slate-500">Nama</th>
                                <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-slate-500">Deskripsi</th>
                                <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($courses as $course)
                                <tr class="transition hover:bg-slate-50/80">
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="inline-flex items-center rounded-lg border border-blue-100 bg-blue-50/80 px-2.5 py-1 font-mono text-xs font-semibold text-blue-700">
                                            {{ $course->code }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-900">
                                        <a href="{{ route('courses.show', $course) }}" class="transition hover:text-blue-600">
                                            {{ $course->name }}
                                        </a>
                                    </td>
                                    <td class="max-w-md px-5 py-4 text-sm text-slate-600">
                                        <p class="line-clamp-2">{{ $course->description ?? '—' }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        @if ($course->is_active)
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
                                                href="{{ route('courses.show', $course) }}"
                                                title="Lihat Detail"
                                                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                            >
                                                <svg class="size-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                </svg>
                                                Detail
                                            </a>
                                            <form
                                                method="POST"
                                                action="{{ route('courses.status', $course) }}"
                                                onsubmit="return confirm('{{ $course->is_active ? 'Nonaktifkan' : 'Aktifkan' }} mata kuliah ini?')"
                                                class="inline-block"
                                            >
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="is_active" value="{{ $course->is_active ? '0' : '1' }}">
                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs font-semibold shadow-2xs transition focus:outline-none focus:ring-2 {{ $course->is_active ? 'border-red-200 bg-red-50/60 text-red-600 hover:bg-red-100 hover:text-red-700 focus:ring-red-500/20' : 'border-emerald-200 bg-emerald-50/60 text-emerald-700 hover:bg-emerald-100 focus:ring-emerald-500/20' }}"
                                                >
                                                    @if ($course->is_active)
                                                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                                                        </svg>
                                                        Nonaktifkan
                                                    @else
                                                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                        </svg>
                                                        Aktifkan
                                                    @endif
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($courses->hasPages())
                    <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-200 px-5 py-3.5 sm:flex-row">
                        <p class="text-xs text-slate-500">
                            Menampilkan <span class="font-semibold text-slate-800">{{ $courses->firstItem() ?? 0 }}</span> &ndash; <span class="font-semibold text-slate-800">{{ $courses->lastItem() ?? 0 }}</span> dari <span class="font-semibold text-slate-800">{{ $courses->total() }}</span> mata kuliah
                        </p>
                        <div>
                            {{ $courses->links() }}
                        </div>
                    </div>
                @endif
            @endif
        </section>
    </div>
@endsection
