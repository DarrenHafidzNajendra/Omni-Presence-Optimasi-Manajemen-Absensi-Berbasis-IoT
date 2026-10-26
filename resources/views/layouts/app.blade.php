<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f8fafc">

    <title>@yield('title', 'Dashboard') | Omni-Presence</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    <div class="flex min-h-screen flex-col">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-blue-600 text-sm font-bold tracking-wide text-white">
                        OP
                    </span>
                    <span>
                        <span class="block text-base font-bold tracking-tight text-slate-950">Omni-Presence</span>
                        <span class="block text-xs text-slate-500">Manajemen absensi berbasis IoT</span>
                    </span>
                </a>

                <nav aria-label="Navigasi utama" class="flex items-center gap-2">
                    @auth
                        <a
                            href="{{ route('dashboard') }}"
                            @class([
                                'rounded-lg px-3 py-2 text-sm font-semibold transition',
                                'bg-blue-50 text-blue-700' => request()->routeIs('dashboard'),
                                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs('dashboard'),
                            ])
                            @if (request()->routeIs('dashboard')) aria-current="page" @endif
                        >
                            Dashboard
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
                                class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                            >
                                Keluar
                            </button>
                        </form>
                    @else
                        <a
                            href="{{ route('login') }}"
                            @class([
                                'rounded-lg px-3 py-2 text-sm font-semibold transition',
                                'bg-blue-50 text-blue-700' => request()->routeIs('login'),
                                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs('login'),
                            ])
                            @if (request()->routeIs('login')) aria-current="page" @endif
                        >
                            Masuk
                        </a>
                    @endauth
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
            @yield('content')
        </main>

        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-4 text-center text-xs text-slate-500 sm:px-6 lg:px-8">
                Omni-Presence <span aria-hidden="true">&middot;</span> Sistem absensi berbasis IoT
            </div>
        </footer>
    </div>
</body>
</html>
