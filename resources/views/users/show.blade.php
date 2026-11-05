@extends('layouts.app')

@section('title', 'Detail Pengguna')

@section('content')
    @php
        $identity = match ($user->role) {
            \App\Enums\UserRole::Admin->value => ['label' => 'Alamat email', 'value' => $user->email],
            \App\Enums\UserRole::Dosen->value => ['label' => 'NIDN', 'value' => $user->nidn],
            \App\Enums\UserRole::Mahasiswa->value => ['label' => 'NIM', 'value' => $user->nim],
            default => ['label' => 'Identitas', 'value' => null],
        };

        $initials = collect(explode(' ', $user->name))
            ->filter()
            ->take(2)
            ->map(fn($part) => mb_substr($part, 0, 1))
            ->join('');

        $roleColors = match($user->role) {
            \App\Enums\UserRole::Admin->value => ['badge' => 'bg-violet-50 text-violet-700 border-violet-200/80', 'avatar' => 'bg-violet-100 text-violet-700'],
            \App\Enums\UserRole::Dosen->value => ['badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200/80', 'avatar' => 'bg-indigo-100 text-indigo-700'],
            \App\Enums\UserRole::Mahasiswa->value => ['badge' => 'bg-teal-50 text-teal-700 border-teal-200/80', 'avatar' => 'bg-teal-100 text-teal-700'],
            default => ['badge' => 'bg-slate-100 text-slate-700 border-slate-200', 'avatar' => 'bg-slate-100 text-slate-700'],
        };
    @endphp

    <div class="mx-auto max-w-4xl space-y-6">
        {{-- Header Breadcrumb & Title --}}
        <section class="space-y-1.5">
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500">
                <a href="{{ route('users.index') }}" class="inline-flex items-center gap-1 transition hover:text-blue-600">
                    <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                    <span>Daftar Pengguna</span>
                </a>
                <span>&rsaquo;</span>
                <span class="font-semibold text-blue-700">Detail</span>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Detail Pengguna</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Informasi akun {{ $user->name }} yang terdaftar di Omni-Presence.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a
                        href="{{ route('users.index') }}"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-500/15"
                    >
                        <svg class="size-3.5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                        Kembali ke Daftar
                    </a>
                    <a
                        href="{{ route('users.edit', $user) }}"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-2xs transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/25 active:scale-[0.98]"
                    >
                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                        </svg>
                        Edit Pengguna
                    </a>
                </div>
            </div>
        </section>

        @error('delete')
            <div role="alert" class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 shadow-2xs">
                <svg class="size-5 shrink-0 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                <div class="flex-1">{{ $message }}</div>
            </div>
        @enderror

        {{-- Profile Hero Card --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <span class="flex size-16 shrink-0 items-center justify-center rounded-2xl {{ $roleColors['avatar'] }} text-xl font-bold tracking-tight shadow-2xs">
                        {{ strtoupper($initials ?: 'U') }}
                    </span>
                    <div class="space-y-1">
                        <h2 class="text-xl font-bold text-slate-900">{{ $user->name }}</h2>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $roleColors['badge'] }}">
                                {{ $roleLabel }}
                            </span>
                            @if ($user->is_active)
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200/70 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                    <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                    Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                                    <span class="size-1.5 rounded-full bg-slate-400"></span>
                                    Nonaktif
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($user->role === \App\Enums\UserRole::Mahasiswa->value && ! $user->attendanceRecords()->exists())
                    <form
                        method="POST"
                        action="{{ route('users.destroy', $user) }}"
                        onsubmit="return confirm('Hapus data mahasiswa ini secara permanen?')"
                    >
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-red-200 bg-white px-3.5 py-2 text-xs font-semibold text-red-600 shadow-2xs transition hover:border-red-300 hover:bg-red-50 focus:outline-none focus:ring-4 focus:ring-red-500/15"
                        >
                            <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                            Hapus Mahasiswa
                        </button>
                    </form>
                @endif
            </div>
        </section>

        {{-- Detailed Attributes Card --}}
        <section aria-label="Informasi pengguna" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
            <div class="border-b border-slate-100 bg-slate-50/75 px-6 py-3.5">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Informasi Rinci Akun</h3>
            </div>
            <dl class="divide-y divide-slate-100">
                <div class="grid gap-2 px-6 py-4 sm:grid-cols-[14rem_minmax(0,1fr)] sm:gap-6">
                    <dt class="text-sm font-medium text-slate-500">Nama</dt>
                    <dd class="text-sm font-semibold text-slate-900">{{ $user->name }}</dd>
                </div>
                <div class="grid gap-2 px-6 py-4 sm:grid-cols-[14rem_minmax(0,1fr)] sm:gap-6">
                    <dt class="text-sm font-medium text-slate-500">Role</dt>
                    <dd class="text-sm text-slate-900">
                        <span class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $roleColors['badge'] }}">
                            {{ $roleLabel }}
                        </span>
                    </dd>
                </div>
                <div class="grid gap-2 px-6 py-4 sm:grid-cols-[14rem_minmax(0,1fr)] sm:gap-6">
                    <dt class="text-sm font-medium text-slate-500">Status akun</dt>
                    <dd class="text-sm text-slate-900">
                        @if ($user->is_active)
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200/70 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                Aktif
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                                <span class="size-1.5 rounded-full bg-slate-400"></span>
                                Nonaktif
                            </span>
                        @endif
                    </dd>
                </div>
                <div class="grid gap-2 px-6 py-4 sm:grid-cols-[14rem_minmax(0,1fr)] sm:gap-6">
                    <dt class="text-sm font-medium text-slate-500">{{ $identity['label'] }}</dt>
                    <dd class="text-sm font-mono font-medium text-slate-900">
                        {{ $identity['value'] ?? 'Belum ditentukan' }}
                    </dd>
                </div>
                @if ($user->role === \App\Enums\UserRole::Mahasiswa->value)
                    <div class="grid gap-2 px-6 py-4 sm:grid-cols-[14rem_minmax(0,1fr)] sm:gap-6">
                        <dt class="text-sm font-medium text-slate-500">Kelas terdaftar</dt>
                        <dd class="text-sm text-slate-900">
                            @forelse ($user->enrollments ?? [] as $enrollment)
                                <span class="mr-1.5 mb-1.5 inline-flex items-center gap-1 rounded-md border border-blue-100 bg-blue-50/90 px-2.5 py-1 text-xs font-medium text-blue-700">
                                    {{ $enrollment->courseSection?->name ?? '-' }}{{ $enrollment->courseSection?->course ? ' ('.$enrollment->courseSection->course->code.')' : '' }}
                                </span>
                            @empty
                                <span class="text-xs text-slate-400 italic">Belum terdaftar kelas</span>
                            @endforelse
                        </dd>
                    </div>
                @endif
            </dl>
        </section>
    </div>
@endsection
