<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f8fafc">

    <title>@yield('title', 'Dashboard') | Omni-Presence</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

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
                    <img src="{{ asset('images/logo.png') }}" alt="Omni-Presence Logo" class="size-10 shrink-0 object-contain drop-shadow-2xs">
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
                            'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                            'bg-blue-50 text-blue-700 shadow-2xs' => request()->routeIs('dashboard'),
                            'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs('dashboard'),
                        ])
                        @if (request()->routeIs('dashboard')) aria-current="page" @endif
                    >
                        <svg class="size-5 shrink-0 {{ request()->routeIs('dashboard') ? 'text-blue-600' : 'text-slate-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                        </svg>
                        <span>Dashboard</span>
                    </a>

                    @if (auth()->user()->role === \App\Enums\UserRole::Admin->value)
                        <a
                            href="{{ route('users.index') }}"
                            data-sidebar-link
                            @class([
                                'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                                'bg-blue-50 text-blue-700 shadow-2xs' => request()->routeIs('users.*'),
                                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs('users.*'),
                            ])
                            @if (request()->routeIs('users.*')) aria-current="page" @endif
                        >
                            <svg class="size-5 shrink-0 {{ request()->routeIs('users.*') ? 'text-blue-600' : 'text-slate-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg>
                            <span>Pengguna</span>
                        </a>
                    @endif

                    @if (auth()->user()->role === \App\Enums\UserRole::Dosen->value)
                        <a
                            href="{{ route('course-sections.index') }}"
                            data-sidebar-link
                            @class([
                                'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                                'bg-blue-50 text-blue-700 shadow-2xs' => request()->routeIs('course-sections.*'),
                                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs('course-sections.*'),
                            ])
                            @if (request()->routeIs('course-sections.*')) aria-current="page" @endif
                        >
                            <svg class="size-5 shrink-0 {{ request()->routeIs('course-sections.*') ? 'text-blue-600' : 'text-slate-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-5.25 6.557q.154.341.388.65" />
                            </svg>
                            <span>Kelas Saya</span>
                        </a>
                    @endif
                </div>

                @if (auth()->user()->role === \App\Enums\UserRole::Admin->value)
                    <p class="mt-6 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Akademik</p>
                    <div class="mt-3 space-y-1">
                        <a
                            href="{{ route('courses.index') }}"
                            data-sidebar-link
                            @class([
                                'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                                'bg-blue-50 text-blue-700 shadow-2xs' => request()->routeIs('courses.*'),
                                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs('courses.*'),
                            ])
                            @if (request()->routeIs('courses.*')) aria-current="page" @endif
                        >
                            <svg class="size-5 shrink-0 {{ request()->routeIs('courses.*') ? 'text-blue-600' : 'text-slate-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                            </svg>
                            <span>Mata Kuliah</span>
                        </a>
                        <a
                            href="{{ route('course-sections.index') }}"
                            data-sidebar-link
                            @class([
                                'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                                'bg-blue-50 text-blue-700 shadow-2xs' => request()->routeIs('course-sections.*'),
                                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs('course-sections.*'),
                            ])
                            @if (request()->routeIs('course-sections.*')) aria-current="page" @endif
                        >
                            <svg class="size-5 shrink-0 {{ request()->routeIs('course-sections.*') ? 'text-blue-600' : 'text-slate-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.429 9.75 2.25 12l4.179 2.25m0-4.5 5.571 3 5.571-3m-11.142 0L2.25 7.5 12 2.25l9.75 5.25-4.179 2.25m0 0L21.75 12l-4.179 2.25m0 0 4.179 2.25L12 21.75 2.25 16.5l4.179-2.25m11.142 0-5.571 3-5.571-3" />
                            </svg>
                            <span>Kelas Mata Kuliah</span>
                        </a>
                        <a
                            href="{{ route('academic-periods.index') }}"
                            data-sidebar-link
                            @class([
                                'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                                'bg-blue-50 text-blue-700 shadow-2xs' => request()->routeIs('academic-periods.*'),
                                'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs('academic-periods.*'),
                            ])
                            @if (request()->routeIs('academic-periods.*')) aria-current="page" @endif
                        >
                            <svg class="size-5 shrink-0 {{ request()->routeIs('academic-periods.*') ? 'text-blue-600' : 'text-slate-400' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                            <span>Periode Akademik</span>
                        </a>
                    </div>
                @endif
            </nav>

            <div class="shrink-0 border-t border-slate-100 p-4 space-y-3">
                <div class="rounded-xl bg-slate-50 p-3 border border-slate-200/80 shadow-2xs">
                    <div class="flex items-center gap-2.5">
                        @php
                            $userInitials = collect(explode(' ', auth()->user()->name))
                                ->filter()
                                ->take(2)
                                ->map(fn($p) => mb_substr($p, 0, 1))
                                ->join('');
                            $roleColor = match(auth()->user()->role) {
                                \App\Enums\UserRole::Admin->value => 'bg-violet-100 text-violet-700',
                                \App\Enums\UserRole::Dosen->value => 'bg-indigo-100 text-indigo-700',
                                \App\Enums\UserRole::Mahasiswa->value => 'bg-teal-100 text-teal-700',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl {{ $roleColor }} text-xs font-bold">
                            {{ strtoupper($userInitials ?: 'U') }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-slate-500">
                                {{ auth()->user()->email ?? auth()->user()->nidn ?? auth()->user()->nim }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-2.5 flex items-center justify-between border-t border-slate-200/60 pt-2">
                        <span class="text-xs text-slate-500">Role</span>
                        <span @class([
                            'shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide',
                            'bg-violet-100 text-violet-800' => auth()->user()->role === \App\Enums\UserRole::Admin->value,
                            'bg-emerald-100 text-emerald-800' => auth()->user()->role === \App\Enums\UserRole::Dosen->value,
                            'bg-amber-100 text-amber-800' => auth()->user()->role === \App\Enums\UserRole::Mahasiswa->value,
                        ])>
                            {{ auth()->user()->role }}
                        </span>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-left text-sm font-semibold text-slate-600 transition hover:bg-red-50 hover:text-red-700">
                        <svg class="size-4 text-slate-400 group-hover:text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                        </svg>
                        <span>Keluar</span>
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
                    <div class="flex items-center gap-2">
                        <img src="{{ asset('images/logo.png') }}" alt="Omni-Presence Logo" class="size-7 shrink-0 object-contain drop-shadow-2xs">
                        <span class="text-base font-bold text-slate-950">Omni-Presence</span>
                    </div>
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
                        <img src="{{ asset('images/logo.png') }}" alt="Omni-Presence Logo" class="size-10 shrink-0 object-contain drop-shadow-2xs">
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
