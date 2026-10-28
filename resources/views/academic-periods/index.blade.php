@extends('layouts.app')

@section('title', 'Periode Akademik')

@section('content')
    <div class="space-y-8">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-2">
                <p class="text-sm font-semibold tracking-wide text-blue-700">AKADEMIK</p>
                <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Periode Akademik</h1>
                <p class="max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">Kelola kalender periode akademik dan batas kelas pengganti.</p>
            </div>
            <a href="{{ route('academic-periods.create') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Tambah Periode</a>
        </section>

        @if (session('success'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if ($academicPeriods->isEmpty())
                <p class="px-6 py-12 text-center text-sm text-slate-500">Belum ada periode akademik.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</th>
                                <th class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-500">Tahun/Semester</th>
                                <th class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-500">Periode</th>
                                <th class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($academicPeriods as $period)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-slate-900">{{ $period->name }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">{{ $period->academic_year }} <span class="capitalize">({{ $period->semester }})</span></td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-700">{{ $period->starts_on->format('d/m/Y') }} - {{ $period->ends_on->format('d/m/Y') }}</td>
                                    <td class="px-6 py-4 text-sm"><span @class(['inline-flex rounded-full px-2.5 py-1 text-xs font-medium', 'bg-emerald-100 text-emerald-800' => $period->is_active, 'bg-red-100 text-red-800' => ! $period->is_active])>{{ $period->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm"><a href="{{ route('academic-periods.show', $period) }}" class="font-semibold text-blue-700 hover:text-blue-900">Detail</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($academicPeriods->hasPages())
                    <div class="border-t border-slate-200 px-6 py-4">{{ $academicPeriods->links() }}</div>
                @endif
            @endif
        </section>
    </div>
@endsection
