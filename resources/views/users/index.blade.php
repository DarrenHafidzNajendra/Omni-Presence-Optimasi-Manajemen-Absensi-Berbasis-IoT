@extends('layouts.app')

@section('title', 'Daftar Pengguna')

@section('content')
    <div class="space-y-8">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-2">
                <p class="text-sm font-semibold tracking-wide text-blue-700">ADMINISTRASI</p>
                <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Daftar Pengguna</h1>
                <p class="max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                    Lihat dan telusuri akun yang terdaftar di Omni-Presence.
                </p>
            </div>
            <a
                href="{{ route('users.create') }}"
                class="inline-flex shrink-0 items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/25"
            >
                Tambah Pengguna
            </a>
        </section>

        @if (session('success'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        <section aria-label="Pencarian dan filter pengguna" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <form method="GET" action="{{ route('users.index') }}" class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_14rem_auto] sm:items-end">
                <div class="space-y-2">
                    <label for="search" class="block text-sm font-medium text-slate-700">Cari nama atau identitas</label>
                    <input
                        id="search"
                        name="search"
                        type="search"
                        value="{{ $search }}"
                        placeholder="Masukkan nama, email, NIDN, atau NIM"
                        class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                    >
                </div>

                <div class="space-y-2">
                    <label for="role" class="block text-sm font-medium text-slate-700">Filter role</label>
                    <select
                        id="role"
                        name="role"
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                    >
                        <option value="">Semua role</option>
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}" @selected($selectedRole === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/25"
                >
                    Terapkan
                </button>
            </form>
        </section>

        <section aria-label="Hasil daftar pengguna" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if ($users->isEmpty())
                <div class="px-6 py-12 text-center">
                    <h2 class="text-base font-semibold text-slate-900">Pengguna tidak ditemukan</h2>
                    <p class="mt-2 text-sm text-slate-500">Coba ubah kata pencarian atau filter role.</p>
                    <a
                        href="{{ route('users.index') }}"
                        class="mt-5 inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Lihat semua pengguna
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</th>
                                <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-500">Identitas</th>
                                <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-500">Role</th>
                                <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($users as $user)
                                <tr class="transition hover:bg-slate-50/70">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">{{ $user->name }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                                        @switch($user->role)
                                            @case(\App\Enums\UserRole::Admin->value)
                                                {{ $user->email ?? 'Belum ditentukan' }}
                                                @break
                                            @case(\App\Enums\UserRole::Dosen->value)
                                                {{ $user->nidn ?? 'Belum ditentukan' }}
                                                @break
                                            @case(\App\Enums\UserRole::Mahasiswa->value)
                                                {{ $user->nim ?? 'Belum ditentukan' }}
                                                @break
                                            @default
                                                Belum ditentukan
                                        @endswitch
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                            {{ $roles[$user->role ?? ''] ?? 'Belum ditentukan' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <a
                                            href="{{ route('users.edit', $user) }}"
                                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-500/15"
                                        >
                                            Edit
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-4 py-4 sm:px-6">
                    {{ $users->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
