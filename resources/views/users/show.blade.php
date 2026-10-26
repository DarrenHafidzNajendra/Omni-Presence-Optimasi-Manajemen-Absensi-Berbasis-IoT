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
    @endphp

    <div class="mx-auto max-w-3xl space-y-8">
        <section class="space-y-2">
            <p class="text-sm font-semibold tracking-wide text-blue-700">ADMINISTRASI</p>
            <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Detail Pengguna</h1>
            <p class="max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                Informasi akun {{ $user->name }} yang terdaftar di Omni-Presence.
            </p>
        </section>

        <section aria-label="Informasi pengguna" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <dl class="divide-y divide-slate-100">
                <div class="grid gap-2 px-5 py-4 sm:grid-cols-[12rem_minmax(0,1fr)] sm:gap-6 sm:px-8">
                    <dt class="text-sm font-medium text-slate-500">Nama</dt>
                    <dd class="text-sm font-semibold text-slate-900">{{ $user->name }}</dd>
                </div>
                <div class="grid gap-2 px-5 py-4 sm:grid-cols-[12rem_minmax(0,1fr)] sm:gap-6 sm:px-8">
                    <dt class="text-sm font-medium text-slate-500">Role</dt>
                    <dd>
                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                            {{ $roleLabel }}
                        </span>
                    </dd>
                </div>
                <div class="grid gap-2 px-5 py-4 sm:grid-cols-[12rem_minmax(0,1fr)] sm:gap-6 sm:px-8">
                    <dt class="text-sm font-medium text-slate-500">{{ $identity['label'] }}</dt>
                    <dd class="break-all text-sm text-slate-900">{{ $identity['value'] ?? 'Belum ditentukan' }}</dd>
                </div>
            </dl>
        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
            <a
                href="{{ route('users.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-500/15"
            >
                Kembali ke Daftar
            </a>
            <a
                href="{{ route('users.edit', $user) }}"
                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/25"
            >
                Edit Pengguna
            </a>
        </div>
    </div>
@endsection
