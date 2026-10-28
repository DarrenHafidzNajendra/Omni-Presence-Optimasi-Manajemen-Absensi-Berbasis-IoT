@extends('layouts.app')

@section('title', 'Detail Periode Akademik')

@section('content')
    <div class="mx-auto max-w-3xl space-y-8">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-2">
                <p class="text-sm font-semibold tracking-wide text-blue-700">AKADEMIK</p>
                <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Detail Periode Akademik</h1>
            </div>
            <a href="{{ route('academic-periods.edit', $academicPeriod) }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Edit Periode</a>
        </section>

        @if (session('success'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <dl class="divide-y divide-slate-100">
                @foreach ([
                    'Nama' => $academicPeriod->name,
                    'Tahun Akademik' => $academicPeriod->academic_year,
                    'Semester' => ucfirst($academicPeriod->semester),
                    'Periode' => $academicPeriod->starts_on->format('d/m/Y').' - '.$academicPeriod->ends_on->format('d/m/Y'),
                    'ATS' => $academicPeriod->ats_starts_on->format('d/m/Y').' - '.$academicPeriod->ats_ends_on->format('d/m/Y'),
                    'UAS' => $academicPeriod->uas_starts_on->format('d/m/Y').' - '.$academicPeriod->uas_ends_on->format('d/m/Y'),
                    'Batas Kelas Pengganti' => $academicPeriod->replacement_deadline->format('d/m/Y'),
                    'Status' => $academicPeriod->is_active ? 'Aktif' : 'Nonaktif',
                ] as $label => $value)
                    <div class="grid gap-2 px-5 py-4 sm:grid-cols-[14rem_minmax(0,1fr)] sm:gap-6 sm:px-8">
                        <dt class="text-sm font-medium text-slate-500">{{ $label }}</dt>
                        <dd class="text-sm text-slate-900">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <div class="flex justify-between gap-3">
            <a href="{{ route('academic-periods.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Kembali</a>
            <form method="POST" action="{{ route('academic-periods.status', $academicPeriod) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="is_active" value="{{ $academicPeriod->is_active ? '0' : '1' }}">
                <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">{{ $academicPeriod->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
            </form>
        </div>
    </div>
@endsection
