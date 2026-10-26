@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="space-y-8">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-2">
                <p class="text-sm font-semibold tracking-wide text-blue-700">RINGKASAN OPERASIONAL</p>
                <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Dashboard kehadiran</h1>
                <p class="max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                    Pantau ringkasan absensi dan kesiapan perangkat dari satu tempat.
                </p>
            </div>
            <p class="text-sm font-medium text-slate-500">{{ now()->format('d/m/Y') }}</p>
        </section>

        @if ($userStatistics !== null)
            <section aria-labelledby="users-heading" class="space-y-4">
                <div>
                    <h2 id="users-heading" class="text-lg font-semibold text-slate-950">Statistik pengguna</h2>
                    <p class="mt-1 text-sm text-slate-500">Ringkasan akun yang terdaftar di sistem.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ([
                        ['key' => 'total', 'label' => 'Total pengguna', 'tone' => 'bg-blue-500'],
                        ['key' => 'admin', 'label' => 'Admin', 'tone' => 'bg-violet-500'],
                        ['key' => 'dosen', 'label' => 'Dosen', 'tone' => 'bg-emerald-500'],
                        ['key' => 'mahasiswa', 'label' => 'Mahasiswa', 'tone' => 'bg-amber-500'],
                    ] as $statistic)
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex items-center gap-3">
                                <span class="size-2.5 rounded-full {{ $statistic['tone'] }}" aria-hidden="true"></span>
                                <h3 class="text-sm font-medium text-slate-600">{{ $statistic['label'] }}</h3>
                            </div>
                            <p class="mt-5 text-3xl font-bold tracking-tight text-slate-950" data-statistic="{{ $statistic['key'] }}">{{ number_format($userStatistics[$statistic['key']]) }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <section aria-labelledby="attendance-heading" class="space-y-4">
            <div>
                <h2 id="attendance-heading" class="text-lg font-semibold text-slate-950">Statistik kehadiran</h2>
                <p class="mt-1 text-sm text-slate-500">Ringkasan akan muncul setelah data absensi tersedia.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['label' => 'Kehadiran hari ini', 'tone' => 'bg-blue-500'],
                    ['label' => 'Hadir', 'tone' => 'bg-emerald-500'],
                    ['label' => 'Terlambat', 'tone' => 'bg-amber-500'],
                    ['label' => 'Izin', 'tone' => 'bg-violet-500'],
                ] as $statistic)
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <span class="size-2.5 rounded-full {{ $statistic['tone'] }}" aria-hidden="true"></span>
                            <h3 class="text-sm font-medium text-slate-600">{{ $statistic['label'] }}</h3>
                        </div>
                        <p class="mt-5 text-3xl font-bold tracking-tight text-slate-400" aria-label="Data belum tersedia">
                            &mdash;
                        </p>
                        <p class="mt-1 text-xs text-slate-500">Data belum tersedia</p>
                    </article>
                @endforeach
            </div>
        </section>

        <div class="grid gap-8 xl:grid-cols-2">
            <section aria-labelledby="devices-heading" class="space-y-4">
                <div>
                    <h2 id="devices-heading" class="text-lg font-semibold text-slate-950">Status perangkat IoT</h2>
                    <p class="mt-1 text-sm text-slate-500">Status koneksi menunggu integrasi perangkat.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ([
                        ['name' => 'Raspberry Pi', 'role' => 'Gateway absensi'],
                        ['name' => 'ESP32', 'role' => 'Perangkat pembaca'],
                    ] as $device)
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-semibold text-slate-950">{{ $device['name'] }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">{{ $device['role'] }}</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                    Belum ada data
                                </span>
                            </div>
                            <p class="mt-6 border-t border-slate-100 pt-4 text-xs leading-5 text-slate-500">
                                Belum ada data koneksi yang diterima.
                            </p>
                        </article>
                    @endforeach
                </div>
            </section>

            <section aria-labelledby="methods-heading" class="space-y-4">
                <div>
                    <h2 id="methods-heading" class="text-lg font-semibold text-slate-950">Metode absensi</h2>
                    <p class="mt-1 text-sm text-slate-500">Metode yang disiapkan untuk proses pencatatan kehadiran.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ([
                        ['name' => 'QR Code', 'description' => 'Pemindaian QR belum terhubung ke pencatatan absensi.'],
                        ['name' => 'Face Recognition', 'description' => 'Pengenalan wajah belum terhubung ke pencatatan absensi.'],
                    ] as $method)
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="font-semibold text-slate-950">{{ $method['name'] }}</h3>
                                <span class="shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                                    Belum dikonfigurasi
                                </span>
                            </div>
                            <p class="mt-4 text-sm leading-6 text-slate-600">{{ $method['description'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        </div>

        <section aria-labelledby="activity-heading" class="space-y-4">
            <div>
                <h2 id="activity-heading" class="text-lg font-semibold text-slate-950">Aktivitas terbaru</h2>
                <p class="mt-1 text-sm text-slate-500">Catatan aktivitas akan muncul saat sistem menerima data.</p>
            </div>

            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center">
                <div class="mx-auto flex size-11 items-center justify-center rounded-full bg-slate-100 text-slate-500" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="size-5" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l2.5 1.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <h3 class="mt-3 text-sm font-semibold text-slate-900">Belum ada aktivitas</h3>
                <p class="mt-1 text-sm text-slate-500">Aktivitas absensi terbaru akan ditampilkan di sini.</p>
            </div>
        </section>
    </div>
@endsection
