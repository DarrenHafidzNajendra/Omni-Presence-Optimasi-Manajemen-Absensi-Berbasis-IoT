@extends('layouts.app')

@section('title', 'Edit Pengguna')

@section('content')
    @php
        $selectedRole = old('role', $user->role);
    @endphp

    <div class="mx-auto max-w-3xl space-y-8">
        <section class="space-y-2">
            <p class="text-sm font-semibold tracking-wide text-blue-700">ADMINISTRASI</p>
            <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Edit Pengguna</h1>
            <p class="max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                Perbarui informasi akun {{ $user->name }}.
            </p>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
            <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="space-y-2 sm:col-span-2">
                        <label for="name" class="block text-sm font-medium text-slate-700">Nama</label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name', $user->name) }}"
                            autocomplete="name"
                            maxlength="255"
                            required
                            autofocus
                            class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                            @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
                        >
                        @error('name')
                            <p id="name-error" class="text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2 sm:col-span-2">
                        <label for="role" class="block text-sm font-medium text-slate-700">Role</label>
                        <select
                            id="role"
                            name="role"
                            required
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                            @error('role') aria-invalid="true" aria-describedby="role-error" @enderror
                        >
                            @foreach ($roles as $role)
                                <option value="{{ $role->value }}" @selected($selectedRole === $role->value)>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('role')
                            <p id="role-error" class="text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div
                        data-identity-field="admin"
                        @if ($selectedRole !== \App\Enums\UserRole::Admin->value) hidden @endif
                        class="space-y-2 sm:col-span-2"
                    >
                        <label for="email" class="block text-sm font-medium text-slate-700">Alamat email</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', $user->email) }}"
                            autocomplete="email"
                            maxlength="255"
                            @if ($selectedRole !== \App\Enums\UserRole::Admin->value) disabled @endif
                            class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                            @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                        >
                        @error('email')
                            <p id="email-error" class="text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div
                        data-identity-field="dosen"
                        @if ($selectedRole !== \App\Enums\UserRole::Dosen->value) hidden @endif
                        class="space-y-2 sm:col-span-2"
                    >
                        <label for="nidn" class="block text-sm font-medium text-slate-700">NIDN</label>
                        <input
                            id="nidn"
                            name="nidn"
                            type="text"
                            value="{{ old('nidn', $user->nidn) }}"
                            autocomplete="off"
                            inputmode="numeric"
                            pattern="[0-9]{6}"
                            maxlength="6"
                            title="NIDN harus terdiri dari tepat 6 digit angka."
                            @if ($selectedRole !== \App\Enums\UserRole::Dosen->value) disabled @endif
                            class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                            @error('nidn') aria-invalid="true" aria-describedby="nidn-error" @enderror
                        >
                        @error('nidn')
                            <p id="nidn-error" class="text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div
                        data-identity-field="mahasiswa"
                        @if ($selectedRole !== \App\Enums\UserRole::Mahasiswa->value) hidden @endif
                        class="space-y-2 sm:col-span-2"
                    >
                        <label for="nim" class="block text-sm font-medium text-slate-700">NIM</label>
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
                            class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                            @error('nim') aria-invalid="true" aria-describedby="nim-error" @enderror
                        >
                        @error('nim')
                            <p id="nim-error" class="text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="password" class="block text-sm font-medium text-slate-700">Kata sandi baru (opsional)</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            minlength="8"
                            class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                            @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                        >
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
                            minlength="8"
                            class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                        >
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
                    <a
                        href="{{ route('users.index') }}"
                        class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-500/15"
                    >
                        Batal
                    </a>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/25"
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
