@extends('layouts.app')

@section('title', 'Edit Jadwal')

@section('content')
    <div class="mx-auto max-w-3xl space-y-8">
        <section class="space-y-2">
            <p class="text-sm font-semibold tracking-wide text-blue-700">AKADEMIK</p>
            <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Edit Jadwal</h1>
            <p class="text-sm leading-6 text-slate-600 sm:text-base">{{ $courseSection->course->code }} - {{ $courseSection->name }}</p>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
            <form method="POST" action="{{ route('course-sections.schedules.update', [$courseSection, $schedule]) }}" class="space-y-6">
                @csrf
                @method('PUT')
                @include('class-schedules._form')
            </form>
        </section>
    </div>
@endsection
