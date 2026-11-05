@extends('layouts.app')

@section('title', 'Daftar Pengguna')

@section('content')
    <div class="space-y-6">
        {{-- Header Section --}}
        <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1.5">
                <div class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                    <svg class="size-3.5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                    <span>ADMINISTRASI</span>
                </div>
                <div class="flex flex-wrap items-baseline gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Daftar Pengguna</h1>
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                        {{ number_format($users->total()) }} total
                    </span>
                </div>
                <p class="max-w-3xl text-sm text-slate-600">
                    Lihat, telusuri, dan kelola status akun mahasiswa, dosen, serta admin yang terdaftar di Omni-Presence.
                </p>
            </div>
            <a
                href="{{ route('users.create') }}"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 hover:shadow focus:outline-none focus:ring-4 focus:ring-blue-500/25 active:scale-[0.98]"
            >
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Pengguna
            </a>
        </section>

        {{-- Flash Alerts --}}
        @if (session('success'))
            <div role="status" class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 shadow-2xs">
                <svg class="size-5 shrink-0 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <div class="flex-1">{{ session('success') }}</div>
            </div>
        @endif

        @error('is_active')
            <div role="alert" class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 shadow-2xs">
                <svg class="size-5 shrink-0 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                <div class="flex-1">{{ $message }}</div>
            </div>
        @enderror

        @error('delete')
            <div role="alert" class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 shadow-2xs">
                <svg class="size-5 shrink-0 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                <div class="flex-1">{{ $message }}</div>
            </div>
        @enderror

        {{-- Statistics KPI Overview --}}
        @if (isset($userCounts))
            <section aria-label="Statistik Ringkas Pengguna" class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs transition hover:border-slate-300">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Semua Pengguna</span>
                        <span class="flex size-7 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ number_format($userCounts['total']) }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">{{ number_format($userCounts['active'] ?? 0) }} akun aktif</p>
                </div>

                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs transition hover:border-slate-300">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Mahasiswa</span>
                        <span class="flex size-7 items-center justify-center rounded-lg bg-teal-50 text-teal-600">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-5.25 6.557q.154.341.388.65" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ number_format($userCounts['mahasiswa']) }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">Terdaftar absensi IoT</p>
                </div>

                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs transition hover:border-slate-300">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Dosen</span>
                        <span class="flex size-7 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ number_format($userCounts['dosen']) }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">Pengampu kelas</p>
                </div>

                <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs transition hover:border-slate-300">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Administrator</span>
                        <span class="flex size-7 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ number_format($userCounts['admin']) }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">Akses penuh sistem</p>
                </div>
            </section>
        @endif

        {{-- Filter and Search Section --}}
        <section aria-label="Pencarian dan filter pengguna" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs sm:p-5">
            <form method="GET" action="{{ route('users.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-12">
                    {{-- Search Input (takes 5 cols on lg) --}}
                    <div class="sm:col-span-2 lg:col-span-4">
                        <label for="search" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-600">Cari nama atau identitas</label>
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
                                placeholder="Masukkan nama, email, NIDN, atau NIM"
                                class="block w-full rounded-xl border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/15 transition"
                            >
                        </div>
                    </div>

                    {{-- Role Filter (takes 2 cols on lg) --}}
                    <div class="lg:col-span-2">
                        <label for="role" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-600">Filter role</label>
                        <select
                            id="role"
                            name="role"
                            class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/15 transition"
                        >
                            <option value="">Semua role</option>
                            @foreach ($roles as $value => $label)
                                <option value="{{ $value }}" @selected($selectedRole === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Kelas Filter (takes 3 cols on lg) --}}
                    <div class="lg:col-span-3">
                        <label for="course_section_id" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-600">Filter kelas</label>
                        <select
                            id="course_section_id"
                            name="course_section_id"
                            class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/15 transition"
                        >
                            <option value="">Semua kelas</option>
                            @foreach ($courseSections ?? [] as $sectionOption)
                                <option value="{{ $sectionOption->id }}" @selected((int) ($selectedCourseSectionId ?? 0) === (int) $sectionOption->id)>
                                    {{ $sectionOption->name }}{{ $sectionOption->course ? ' - '.$sectionOption->course->code : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Status Filter (takes 3 cols on lg) --}}
                    <div class="lg:col-span-3">
                        <label for="status" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-600">Status akun</label>
                        <select
                            id="status"
                            name="status"
                            class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/15 transition"
                        >
                            <option value="">Semua status</option>
                            <option value="active" @selected(in_array($selectedStatus ?? '', ['active', 'aktif', '1'], true))>Aktif</option>
                            <option value="inactive" @selected(in_array($selectedStatus ?? '', ['inactive', 'nonaktif', '0'], true))>Nonaktif</option>
                        </select>
                    </div>
                </div>

                {{-- Action Row with Active Badges --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-3">
                    <div class="flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
                        @php
                            $hasActiveFilters = ($search !== '') || !empty($selectedRole) || !empty($selectedCourseSectionId) || !empty($selectedStatus);
                        @endphp
                        @if ($hasActiveFilters)
                            <span class="font-medium text-slate-700">Filter aktif:</span>
                            @if ($search !== '')
                                <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-700">
                                    Cari: "{{ $search }}"
                                </span>
                            @endif
                            @if (!empty($selectedRole))
                                <span class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-0.5 font-medium text-blue-700">
                                    Role: {{ $roles[$selectedRole] ?? $selectedRole }}
                                </span>
                            @endif
                            @if (!empty($selectedCourseSectionId))
                                @php
                                    $activeSection = ($courseSections ?? collect())->firstWhere('id', $selectedCourseSectionId);
                                @endphp
                                <span class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-0.5 font-medium text-blue-700">
                                    Kelas: {{ $activeSection ? $activeSection->name : $selectedCourseSectionId }}
                                </span>
                            @endif
                            @if (!empty($selectedStatus))
                                <span class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-0.5 font-medium text-blue-700">
                                    Status: {{ in_array($selectedStatus, ['active', 'aktif', '1'], true) ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            @endif
                        @else
                            <span class="text-slate-400">Gunakan filter untuk mempersempit daftar pengguna</span>
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        @if ($hasActiveFilters)
                            <a
                                href="{{ route('users.index') }}"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 hover:text-slate-900"
                            >
                                <svg class="size-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                Reset
                            </a>
                        @endif
                        <button
                            type="submit"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/25 active:scale-[0.98]"
                        >
                            <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                            </svg>
                            Terapkan
                        </button>
                    </div>
                </div>
            </form>
        </section>

        {{-- Table Section --}}
        <section aria-label="Hasil daftar pengguna" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
            @if ($users->isEmpty())
                <div class="px-6 py-14 text-center">
                    <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <svg class="size-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </div>
                    <h2 class="mt-4 text-base font-semibold text-slate-900">Pengguna tidak ditemukan</h2>
                    <p class="mt-1 text-sm text-slate-500">Coba ubah kata pencarian, filter role, kelas, atau status akun.</p>
                    <a
                        href="{{ route('users.index') }}"
                        class="mt-5 inline-flex items-center gap-1.5 rounded-xl border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50 hover:text-slate-900"
                    >
                        <svg class="size-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                        </svg>
                        Lihat semua pengguna
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left">
                        <thead class="bg-slate-50/75">
                            <tr>
                                <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-slate-500">Nama</th>
                                <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-slate-500">Identitas (NIM / NIDN / Email)</th>
                                <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-slate-500">Role</th>
                                <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-slate-500">Kelas Terdaftar</th>
                                <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wider text-slate-500">Status Akun</th>
                                <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($users as $user)
                                <tr class="transition hover:bg-slate-50/80">
                                    {{-- Nama & Avatar --}}
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            @php
                                                $initials = collect(explode(' ', $user->name))
                                                    ->filter()
                                                    ->take(2)
                                                    ->map(fn($part) => mb_substr($part, 0, 1))
                                                    ->join('');
                                                $roleTone = match($user->role) {
                                                    \App\Enums\UserRole::Admin->value => 'bg-violet-100 text-violet-700',
                                                    \App\Enums\UserRole::Dosen->value => 'bg-indigo-100 text-indigo-700',
                                                    \App\Enums\UserRole::Mahasiswa->value => 'bg-teal-100 text-teal-700',
                                                    default => 'bg-slate-100 text-slate-700',
                                                };
                                            @endphp
                                            <span class="flex size-9 shrink-0 items-center justify-center rounded-xl {{ $roleTone }} text-xs font-bold tracking-tight shadow-2xs">
                                                {{ strtoupper($initials ?: 'U') }}
                                            </span>
                                            <div class="min-w-0">
                                                <a href="{{ route('users.show', $user) }}" class="block truncate text-sm font-semibold text-slate-900 transition hover:text-blue-600">
                                                    {{ $user->name }}
                                                </a>
                                                @if (auth()->id() === $user->id)
                                                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-blue-600">
                                                        <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                                        </svg>
                                                        Anda
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Identitas (NIM / NIDN / Email) --}}
                                    <td class="whitespace-nowrap px-5 py-4 text-sm">
                                        @switch($user->role)
                                            @case(\App\Enums\UserRole::Admin->value)
                                                @if ($user->email)
                                                    <span class="inline-flex items-center gap-1.5 font-mono text-xs text-slate-700">
                                                        <svg class="size-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                                        </svg>
                                                        {{ $user->email }}
                                                    </span>
                                                @else
                                                    <span class="text-xs text-slate-400 italic">Belum ditentukan</span>
                                                @endif
                                                @break
                                            @case(\App\Enums\UserRole::Dosen->value)
                                                @if ($user->nidn)
                                                    <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-medium text-slate-800">
                                                        {{ $user->nidn }}
                                                    </span>
                                                @else
                                                    <span class="text-xs text-slate-400 italic">Belum ditentukan</span>
                                                @endif
                                                @break
                                            @case(\App\Enums\UserRole::Mahasiswa->value)
                                                @if ($user->nim)
                                                    <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-medium text-slate-800">
                                                        {{ $user->nim }}
                                                    </span>
                                                @else
                                                    <span class="text-xs text-slate-400 italic">Belum ditentukan</span>
                                                @endif
                                                @break
                                            @default
                                                <span class="text-xs text-slate-400 italic">Belum ditentukan</span>
                                        @endswitch
                                    </td>

                                    {{-- Role --}}
                                    <td class="whitespace-nowrap px-5 py-4">
                                        @php
                                            $roleBadgeClass = match($user->role) {
                                                \App\Enums\UserRole::Admin->value => 'bg-violet-50 text-violet-700 border-violet-200/80',
                                                \App\Enums\UserRole::Dosen->value => 'bg-indigo-50 text-indigo-700 border-indigo-200/80',
                                                \App\Enums\UserRole::Mahasiswa->value => 'bg-teal-50 text-teal-700 border-teal-200/80',
                                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $roleBadgeClass }}">
                                            {{ $roles[$user->role ?? ''] ?? 'Belum ditentukan' }}
                                        </span>
                                    </td>

                                    {{-- Kelas Terdaftar --}}
                                    <td class="px-5 py-4">
                                        @if ($user->role === \App\Enums\UserRole::Mahasiswa->value)
                                            <div class="flex max-w-xs flex-wrap gap-1.5">
                                                @forelse ($user->enrollments as $enrollment)
                                                    <span class="inline-flex items-center gap-1 rounded-md border border-blue-100 bg-blue-50/90 px-2 py-0.5 text-xs font-medium text-blue-700">
                                                        {{ $enrollment->courseSection?->name ?? '-' }}{{ $enrollment->courseSection?->course ? ' ('.$enrollment->courseSection->course->code.')' : '' }}
                                                    </span>
                                                @empty
                                                    <span class="text-xs text-slate-400 italic">Belum terdaftar kelas</span>
                                                @endforelse
                                            </div>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>

                                    {{-- Status Akun --}}
                                    <td class="whitespace-nowrap px-5 py-4">
                                        @if ($user->is_active)
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

                                    {{-- Aksi --}}
                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        <div class="inline-flex items-center justify-end gap-1.5">
                                            <a
                                                href="{{ route('users.show', $user) }}"
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
                                                href="{{ route('users.edit', $user) }}"
                                                title="Edit Pengguna"
                                                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:border-slate-300 hover:bg-slate-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                            >
                                                <svg class="size-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                </svg>
                                                Edit
                                            </a>

                                            @if (auth()->id() === $user->id && $user->is_active)
                                                <span class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-semibold text-slate-500">
                                                    Akun Anda
                                                </span>
                                            @else
                                                <form
                                                    method="POST"
                                                    action="{{ route('users.status', $user) }}"
                                                    onsubmit="{{ $user->is_active ? "return confirm('Nonaktifkan akun pengguna ini?')" : '' }}"
                                                    class="inline-block"
                                                >
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="is_active" value="{{ $user->is_active ? '0' : '1' }}">
                                                    <button
                                                        type="submit"
                                                        class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs font-semibold shadow-2xs transition focus:outline-none focus:ring-2 {{ $user->is_active ? 'border-red-200 bg-red-50/60 text-red-600 hover:bg-red-100 hover:text-red-700 focus:ring-red-500/20' : 'border-emerald-200 bg-emerald-50/60 text-emerald-700 hover:bg-emerald-100 focus:ring-emerald-500/20' }}"
                                                    >
                                                        @if ($user->is_active)
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

                                                @if ($user->role === \App\Enums\UserRole::Mahasiswa->value && ($user->attendance_records_count ?? 0) === 0)
                                                    <form
                                                        method="POST"
                                                        action="{{ route('users.destroy', $user) }}"
                                                        onsubmit="return confirm('Hapus data mahasiswa ini secara permanen?')"
                                                        class="inline-block"
                                                    >
                                                        @csrf
                                                        @method('DELETE')
                                                        <button
                                                            type="submit"
                                                            class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-red-600 shadow-2xs transition hover:border-red-300 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500/20"
                                                        >
                                                            <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                            </svg>
                                                            Hapus
                                                        </button>
                                                    </form>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-200 px-5 py-3.5 sm:flex-row">
                    <p class="text-xs text-slate-500">
                        Menampilkan <span class="font-semibold text-slate-800">{{ $users->firstItem() ?? 0 }}</span> &ndash; <span class="font-semibold text-slate-800">{{ $users->lastItem() ?? 0 }}</span> dari <span class="font-semibold text-slate-800">{{ $users->total() }}</span> pengguna
                    </p>
                    <div>
                        {{ $users->links() }}
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection
