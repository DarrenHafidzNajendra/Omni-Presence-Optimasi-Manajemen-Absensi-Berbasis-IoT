@extends('layouts.app')

@section('title', 'Edit Pengguna')

@section('content')
    @php
        $selectedRole = old('role', $user->role);
    @endphp

    <div class="mx-auto max-w-3xl space-y-6">
        <section class="space-y-1.5">
            <div class="flex items-center gap-2 text-xs font-medium text-slate-500">
                <a href="{{ route('users.index') }}" class="inline-flex items-center gap-1 transition hover:text-blue-600">
                    <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                    <span>Daftar Pengguna</span>
                </a>
                <span>&rsaquo;</span>
                <span class="font-semibold text-blue-700">Edit</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Edit Pengguna</h1>
            <p class="max-w-2xl text-sm leading-6 text-slate-600">
                Perbarui informasi akun {{ $user->name }}.
            </p>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs sm:p-8">
            <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="space-y-1.5 sm:col-span-2">
                        <label for="name" class="block text-xs font-semibold uppercase tracking-wide text-slate-700">Nama Lengkap</label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name', $user->name) }}"
                            autocomplete="name"
                            maxlength="255"
                            required
                            autofocus
                            class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-2xs outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                            @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
                        >
                        @error('name')
                            <p id="name-error" class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1.5 sm:col-span-2">
                        <label for="role" class="block text-xs font-semibold uppercase tracking-wide text-slate-700">Role Pengguna</label>
                        <select
                            id="role"
                            name="role"
                            required
                            class="block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-2xs outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                            @error('role') aria-invalid="true" aria-describedby="role-error" @enderror
                        >
                            @foreach ($roles as $role)
                                <option value="{{ $role->value }}" @selected($selectedRole === $role->value)>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('role')
                            <p id="role-error" class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div
                        data-identity-field="admin"
                        @if ($selectedRole !== \App\Enums\UserRole::Admin->value) hidden @endif
                        class="space-y-1.5 sm:col-span-2"
                    >
                        <label for="email" class="block text-xs font-semibold uppercase tracking-wide text-slate-700">Alamat Email</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', $user->email) }}"
                            autocomplete="email"
                            maxlength="255"
                            @if ($selectedRole !== \App\Enums\UserRole::Admin->value) disabled @endif
                            class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-2xs outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                            @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                        >
                        @error('email')
                            <p id="email-error" class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div
                        data-identity-field="dosen"
                        @if ($selectedRole !== \App\Enums\UserRole::Dosen->value) hidden @endif
                        class="space-y-1.5 sm:col-span-2"
                    >
                        <label for="nidn" class="block text-xs font-semibold uppercase tracking-wide text-slate-700">NIDN (10 Digit)</label>
                        <input
                            id="nidn"
                            name="nidn"
                            type="text"
                            value="{{ old('nidn', $user->nidn) }}"
                            autocomplete="off"
                            inputmode="numeric"
                            pattern="[0-9]{10}"
                            maxlength="10"
                            title="NIDN harus terdiri dari tepat 10 digit angka."
                            @if ($selectedRole !== \App\Enums\UserRole::Dosen->value) disabled @endif
                            class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-mono text-sm text-slate-900 shadow-2xs outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                            @error('nidn') aria-invalid="true" aria-describedby="nidn-error" @enderror
                        >
                        @error('nidn')
                            <p id="nidn-error" class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div
                        data-identity-field="mahasiswa"
                        @if ($selectedRole !== \App\Enums\UserRole::Mahasiswa->value) hidden @endif
                        class="space-y-1.5 sm:col-span-2"
                    >
                        <label for="nim" class="block text-xs font-semibold uppercase tracking-wide text-slate-700">NIM (10 Digit)</label>
                        <input
                            id="nim"
                            name="nim"
                            type="text"
                            value="{{ old('nim', $user->nim) }}"
                            autocomplete="off"
                            inputmode="numeric"
                            pattern="[0-9]{10}"
                            maxlength="10"
                            title="NIM harus terdiri dari tepat 10 digit angka."
                            @if ($selectedRole !== \App\Enums\UserRole::Mahasiswa->value) disabled @endif
                            class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 font-mono text-sm text-slate-900 shadow-2xs outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                            @error('nim') aria-invalid="true" aria-describedby="nim-error" @enderror
                        >
                        @error('nim')
                            <p id="nim-error" class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wide text-slate-700">Kata sandi baru (opsional)</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            minlength="8"
                            placeholder="Biarkan kosong jika tidak diubah"
                            class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-2xs outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                            @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                        >
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
                            minlength="8"
                            placeholder="Ulangi kata sandi baru"
                            class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-2xs outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                        >
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
                    <a
                        href="{{ route('users.index') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-2xs transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-500/15"
                    >
                        Batal
                    </a>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-semibold text-white shadow-2xs transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/25 active:scale-[0.98]"
                    >
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </section>
    </div>

    <script>
        const roleSelect = document.getElementById('role');
        const identityFields = document.querySelectorAll('[data-identity-field]');

        const updateIdentityField = () => {
            identityFields.forEach((container) => {
                const isActive = container.dataset.identityField === roleSelect.value;

                container.hidden = !isActive;
                container.querySelector('input').disabled = !isActive;
                container.querySelector('input').required = isActive;
            });
        };

        roleSelect.addEventListener('change', updateIdentityField);
        updateIdentityField();
    </script>
@endsection
