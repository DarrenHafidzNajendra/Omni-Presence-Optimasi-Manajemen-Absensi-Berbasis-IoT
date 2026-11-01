@extends('layouts.app')

@section('title', 'Sesi Absensi')

@section('content')
    <div class="mx-auto max-w-7xl space-y-8">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-2">
                <p class="text-sm font-semibold tracking-wide text-blue-700">SESI ABSENSI</p>
                <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">{{ $courseSection->name }}</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('course-sections.attendance-recap', $courseSection) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">Rekap Kehadiran</a>
                <a href="{{ route('course-sections.attendance-sessions.create', $courseSection) }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">Buat Sesi Reguler</a>
                @if (auth()->user()->role === 'admin')
                    <a href="{{ route('course-sections.show', $courseSection) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Kembali ke Kelas</a>
                @else
                    <a href="{{ route('course-sections.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Kembali ke Kelas Saya</a>
                @endif
            </div>
        </section>

        @if (session('success'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="divide-y divide-slate-100">
                @forelse ($sessions as $session)
                    <div class="flex items-center justify-between p-4">
                        <div>
                            <h3 class="font-semibold text-slate-900">{{ $session->title }}</h3>
                            <p class="text-sm text-slate-500">
                                {{ $session->starts_at?->format('d/m/Y H:i') }} - {{ $session->ends_at?->format('H:i') }}
                                ({{ $session->status }})
                                @if ($session->isUnexecutedOverdue())
                                    <span class="ml-1 inline-flex rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-800">
                                        Perlu Ditindaklanjuti
                                    </span>
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('course-sections.attendance-sessions.show', [$courseSection, $session]) }}" class="text-sm font-semibold text-blue-700 hover:text-blue-900">Lihat Detail</a>
                    </div>
                @empty
                    <div class="p-8 text-center text-slate-500">Belum ada sesi absensi untuk kelas ini.</div>
                @endforelse
            </div>
        </section>

        {{ $sessions->links() }}
    </div>
@endsection

