@extends('layouts.app')

@section('title', 'Edit Periode Akademik')

@section('content')
    <div class="mx-auto max-w-3xl space-y-8">
        <section class="space-y-2">
            <p class="text-sm font-semibold tracking-wide text-blue-700">AKADEMIK</p>
            <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Edit Periode Akademik</h1>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
            <form method="POST" action="{{ route('academic-periods.update', $academicPeriod) }}" class="space-y-6">
                @method('PUT')
                @include('academic-periods._form')
            </form>
        </section>
    </div>
@endsection
