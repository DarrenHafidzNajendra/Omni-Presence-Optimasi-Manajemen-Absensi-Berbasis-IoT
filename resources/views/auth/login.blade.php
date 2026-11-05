@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
    <div class="w-full max-w-md">
        <div class="rounded-3xl border border-slate-200/80 bg-white p-7 shadow-xs sm:p-9">
            {{-- Logo & Brand Heading --}}
            <div class="text-center">
                <div class="mx-auto mb-4 flex size-20 items-center justify-center rounded-2xl bg-slate-50 p-2 shadow-2xs border border-slate-100">
                    <img src="{{ asset('images/logo.png') }}" alt="Omni-Presence Logo" class="size-16 object-contain drop-shadow-xs">
                </div>
                <div class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                    <span>SISTEM ABSENSI IOT</span>
                </div>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Masuk ke Akun</h1>
                <p class="mt-1 text-xs leading-5 text-slate-500 sm:text-sm">
                    Gunakan kredensial akun yang telah didaftarkan di Omni-Presence.
                </p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="mt-7 space-y-4">
                @csrf

                {{-- Role Selector --}}
                <div class="space-y-1.5">
                    <label for="role" class="block text-xs font-semibold uppercase tracking-wide text-slate-700">Masuk sebagai</label>
                    <div class="relative">
                        <select
                            id="role"
                            name="role"
                            required
                            class="block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-900 shadow-2xs outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                            @error('role') aria-invalid="true" aria-describedby="role-error" @enderror
                        >
                            @foreach ($roles as $role)
                                <option value="{{ $role->value }}" @selected(old('role', \App\Enums\UserRole::Admin->value) === $role->value)>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @error('role')
                        <p id="role-error" class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Dynamic Identity Input --}}
                <div class="space-y-1.5">
                    <label id="identity-label" for="identity" class="block text-xs font-semibold uppercase tracking-wide text-slate-700">Alamat email</label>
                    <div class="relative">
                        <input
                            id="identity"
                            name="identity"
                            type="email"
                            value="{{ old('identity') }}"
                            placeholder="nama@contoh.com"
                            inputmode="email"
                            autocomplete="username"
                            required
                            autofocus
                            class="block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-2xs outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                            @error('identity') aria-invalid="true" aria-describedby="identity-error" @enderror
                        >
                    </div>
                    @error('identity')
                        <p id="identity-error" class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password Input --}}
                <div class="space-y-1.5">
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wide text-slate-700">Kata sandi</label>
                    <div class="relative">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                            placeholder="Masukkan kata sandi akun"
                            class="block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-2xs outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                            @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                        >
                    </div>
                    @error('password')
                        <p id="password-error" class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label for="remember" class="flex items-center gap-2 text-xs font-medium text-slate-600 cursor-pointer select-none">
                        <input
                            id="remember"
                            name="remember"
                            type="checkbox"
                            value="1"
                            @checked(old('remember'))
                            class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        >
                        <span>Ingat saya</span>
                    </label>
                </div>

                <button
                    type="submit"
                    class="mt-2 flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 hover:shadow focus:outline-none focus:ring-4 focus:ring-blue-500/25 active:scale-[0.98]"
                >
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                    </svg>
                    <span>Masuk</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        const roleInput = document.getElementById('role');
        const identityLabel = document.getElementById('identity-label');
        const identityInput = document.getElementById('identity');
        const identitySettings = {
            admin: { label: 'Alamat email', placeholder: 'nama@contoh.com', type: 'email', inputmode: 'email' },
            dosen: { label: 'NIDN', placeholder: 'Masukkan 10 digit NIDN', type: 'text', inputmode: 'text' },
            mahasiswa: { label: 'NIM', placeholder: 'Masukkan 10 digit NIM', type: 'text', inputmode: 'text' },
        };

        function updateIdentityField() {
            const settings = identitySettings[roleInput.value];
            identityLabel.textContent = settings.label;
            identityInput.placeholder = settings.placeholder;
            identityInput.type = settings.type;
            identityInput.inputMode = settings.inputmode;
        }

        roleInput.addEventListener('change', updateIdentityField);
        updateIdentityField();
    </script>
@endsection
