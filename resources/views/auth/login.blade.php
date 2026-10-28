@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
    <div class="mx-auto max-w-md">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="space-y-2">
                <p class="text-sm font-semibold tracking-wide text-blue-700">OMNI-PRESENCE</p>
                <h1 class="text-2xl font-bold tracking-tight text-slate-950">Masuk ke akun</h1>
                <p class="text-sm leading-6 text-slate-600">Gunakan akun yang telah diberikan oleh administrator.</p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                @csrf

                <div class="space-y-2">
                    <label for="role" class="block text-sm font-medium text-slate-700">Masuk sebagai</label>
                    <select
                        id="role"
                        name="role"
                        required
                        class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                        @error('role') aria-invalid="true" aria-describedby="role-error" @enderror
                    >
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected(old('role', \App\Enums\UserRole::Admin->value) === $role->value)>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')
                        <p id="role-error" class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label id="identity-label" for="identity" class="block text-sm font-medium text-slate-700">Alamat email</label>
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
                        class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                        @error('identity') aria-invalid="true" aria-describedby="identity-error" @enderror
                    >
                    @error('identity')
                        <p id="identity-error" class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="password" class="block text-sm font-medium text-slate-700">Kata sandi</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                        @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                    >
                    @error('password')
                        <p id="password-error" class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <label for="remember" class="flex items-center gap-2 text-sm text-slate-600">
                    <input
                        id="remember"
                        name="remember"
                        type="checkbox"
                        value="1"
                        @checked(old('remember'))
                        class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    >
                    Ingat saya
                </label>

                <button
                    type="submit"
                    class="flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/25"
                >
                    Masuk
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
            dosen: { label: 'NIDN', placeholder: 'Masukkan NIDN', type: 'text', inputmode: 'text' },
            mahasiswa: { label: 'NIM', placeholder: 'Masukkan NIM', type: 'text', inputmode: 'text' },
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
