@extends('layouts.app')

@section('title', 'Ganti Kata Sandi Pertama')

@section('content')
    <div class="mx-auto w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="space-y-2">
            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                Wajib Ganti Kata Sandi
            </span>
            <h1 class="text-2xl font-bold tracking-tight text-slate-950">Ganti Kata Sandi Awal</h1>
            <p class="text-sm leading-6 text-slate-600">
                Demi keamanan akun Anda, silakan buat kata sandi baru sebelum dapat menggunakan sistem Omni-Presence.
            </p>
        </div>

        <div class="mt-4 rounded-xl border border-slate-100 bg-slate-50 p-4 text-xs text-slate-600 space-y-1">
            <p><strong>Nama:</strong> {{ $user->name }}</p>
            <p><strong>Identitas ({{ strtoupper($user->role) }}):</strong> {{ $user->nidn ?? $user->nim ?? $user->email }}</p>
        </div>

        <form method="POST" action="{{ route('password.first-change.update') }}" class="mt-6 space-y-5">
            @csrf

            <div class="space-y-2">
                <label for="password" class="block text-sm font-medium text-slate-700">Kata sandi baru</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="new-password"
                    required
                    autofocus
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                    @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                >
                <p class="text-xs text-slate-500">Minimal 8 karakter.</p>
                @error('password')
                    <p id="password-error" class="text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Konfirmasi kata sandi baru</label>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    required
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                >
            </div>

            <button
                type="submit"
                class="flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/25"
            >
                Simpan & Lanjutkan ke Dashboard
            </button>
        </form>

        <div class="mt-6 border-t border-slate-100 pt-4 text-center">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-xs font-semibold text-slate-500 hover:text-slate-800">
                    Batal & Keluar
                </button>
            </form>
        </div>
    </div>
@endsection

