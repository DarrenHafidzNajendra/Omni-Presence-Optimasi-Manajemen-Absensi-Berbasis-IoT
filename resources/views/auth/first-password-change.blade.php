@extends('layouts.app')

@section('title', 'Ganti Kata Sandi Pertama')

@section('content')
    <div class="mx-auto w-full max-w-md">
        <div class="rounded-3xl border border-slate-200/80 bg-white p-7 shadow-xs sm:p-9">
            <div class="text-center">
                <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl bg-slate-50 p-2 shadow-2xs border border-slate-100">
                    <img src="{{ asset('images/logo.png') }}" alt="Omni-Presence Logo" class="size-12 object-contain drop-shadow-xs">
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800 border border-amber-200/70">
                    <span class="size-1.5 rounded-full bg-amber-500"></span>
                    Wajib Ganti Kata Sandi
                </span>
                <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-950">Ganti Kata Sandi Awal</h1>
                <p class="mt-1 text-xs leading-5 text-slate-500 sm:text-sm">
                    Demi keamanan akun Anda, silakan buat kata sandi baru sebelum dapat menggunakan sistem Omni-Presence.
                </p>
            </div>

            <div class="mt-5 rounded-2xl border border-slate-100 bg-slate-50/80 p-3.5 text-xs text-slate-600 space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Nama Akun:</span>
                    <span class="font-semibold text-slate-900">{{ $user->name }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Identitas ({{ strtoupper($user->role) }}):</span>
                    <span class="font-mono font-medium text-slate-900">{{ $user->nidn ?? $user->nim ?? $user->email }}</span>
                </div>
            </div>

            <form method="POST" action="{{ route('password.first-change.update') }}" class="mt-6 space-y-4">
                @csrf

                <div class="space-y-1.5">
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wide text-slate-700">Kata sandi baru</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="new-password"
                        required
                        autofocus
                        placeholder="Minimal 8 karakter"
                        class="block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-2xs outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                        @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                    >
                    <p class="text-xs text-slate-500">Minimal 8 karakter.</p>
                    @error('password')
                        <p id="password-error" class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wide text-slate-700">Konfirmasi kata sandi baru</label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        required
                        placeholder="Ulangi kata sandi baru"
                        class="block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-2xs outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                    >
                </div>

                <button
                    type="submit"
                    class="mt-2 flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 hover:shadow focus:outline-none focus:ring-4 focus:ring-blue-500/25 active:scale-[0.98]"
                >
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span>Simpan & Lanjutkan ke Dashboard</span>
                </button>
            </form>

            <div class="mt-6 border-t border-slate-100 pt-4 text-center">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xs font-medium text-slate-500 transition hover:text-red-600">
                        Batal & Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
