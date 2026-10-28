<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f8fafc">

    <title>@yield('title', 'Dashboard') | Omni-Presence</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen overflow-x-hidden bg-slate-50 text-slate-800 antialiased @auth lg:flex @endauth">
    @auth
        <div id="sidebar-overlay" class="fixed inset-0 z-30 hidden bg-slate-950/50 lg:hidden" aria-hidden="true"></div>

        <aside
            id="app-sidebar"
            class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-200 ease-out lg:static lg:translate-x-0"
            aria-label="Navigasi utama"
        >
            <div class="flex h-20 shrink-0 items-center justify-between border-b border-slate-100 px-5">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3" data-sidebar-link>
                    <span class="flex size-10 items-center justify-center rounded-xl bg-blue-600 text-sm font-bold tracking-wide text-white">OP</span>
                    <span>
                        <span class="block text-base font-bold tracking-tight text-slate-950">Omni-Presence</span>
                        <span class="block text-xs text-slate-500">Absensi berbasis IoT</span>
                    </span>
                </a>
                <button
                    type="button"
                    class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-500/20 lg:hidden"
                    aria-label="Tutup navigasi"
                    data-sidebar-close
                >
                    <span aria-hidden="true" class="text-xl leading-none">&times;</span>
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-4 py-6">
                <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Menu utama</p>
                <div class="mt-3 space-y-1">
                    <a
                        href="{{ route('dashboard') }}"
                        data-sidebar-link
                        @class([
                            'flex items-center rounded-lg px-3 py-2.5 text-sm font-semibold transition',
                            'bg-blue-50 text-blue-700' => request()->routeIs('dashboard'),
                            'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs('dashboard'),
                        ])
                        @if (request()->routeIs('dashboard')) aria-current="page" @endif
                    >Dashboard</a>

                    @if (auth()->user()->role === \App\Enums\UserRole::Admin->value)
                        <a
                            href="{{ route('users.index') }}"
                            data-sidebar-link
                            @class([
                                'flex items-center rounded-lg px-3 py-2.5 text-sm font-semibold transition',
                                'bg-blue-50 text-blue-700' => request()->routeIs('users.*'),
                                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs('users.*'),
                            ])
                            @if (request()->routeIs('users.*')) aria-current="page" @endif
                        >Pengguna</a>
                        <a
                            href="{{ route('courses.index') }}"
                            data-sidebar-link
                            @class([
                                'flex items-center rounded-lg px-3 py-2.5 text-sm font-semibold transition',
                                'bg-blue-50 text-blue-700' => request()->routeIs('courses.*'),
                                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs('courses.*'),
                            ])
                            @if (request()->routeIs('courses.*')) aria-current="page" @endif
                        >Mata Kuliah</a>
                        <a
                            href="{{ route('course-sections.index') }}"
                            data-sidebar-link
                            @class([
                                'flex items-center rounded-lg px-3 py-2.5 text-sm font-semibold transition',
                                'bg-blue-50 text-blue-700' => request()->routeIs('course-sections.*'),
                                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs('course-sections.*'),
                            ])
                            @if (request()->routeIs('course-sections.*')) aria-current="page" @endif
                        >Kelas Mata Kuliah</a>
                    @endif
                </div>
            </nav>

            <div class="shrink-0 border-t border-slate-100 p-4">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center rounded-lg px-3 py-2.5 text-left text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                        Keluar
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex min-h-screen min-w-0 flex-1 flex-col lg:pl-0">
            <header class="sticky top-0 z-20 border-b border-slate-200 bg-white lg:hidden">
                <div class="flex h-16 items-center gap-3 px-4">
                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-500/20"
                        aria-label="Buka navigasi"
                        aria-controls="app-sidebar"
                        aria-expanded="false"
                        data-sidebar-open
                    >
                        <span aria-hidden="true" class="text-2xl leading-none">&#9776;</span>
                    </button>
                    <span class="text-base font-bold text-slate-950">Omni-Presence</span>
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
    @else
        <div class="flex min-h-screen w-full flex-col">
            <header class="border-b border-slate-200 bg-white">
                <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                    <a href="{{ route('login') }}" class="flex items-center gap-3">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-blue-600 text-sm font-bold tracking-wide text-white">OP</span>
                        <span>
                            <span class="block text-base font-bold tracking-tight text-slate-950">Omni-Presence</span>
                            <span class="block text-xs text-slate-500">Manajemen absensi berbasis IoT</span>
                        </span>
                    </a>
                </div>
            </header>
            <main class="flex w-full max-w-none flex-1 items-center justify-center px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
                @yield('content')
            </main>
        </div>
    @endauth
</body>
</html>
