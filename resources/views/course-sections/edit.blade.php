@extends('layouts.app')

@section('title', 'Edit Kelas')

@section('content')
    <div class="mx-auto max-w-3xl space-y-8">
        <section class="space-y-2">
            <p class="text-sm font-semibold tracking-wide text-blue-700">AKADEMIK</p>
            <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Edit Kelas</h1>
            <p class="max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                Perbarui informasi kelas {{ $courseSection->name }}.
            </p>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
            <form method="POST" action="{{ route('course-sections.update', $courseSection) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="space-y-2">
                    <label for="course_id" class="block text-sm font-medium text-slate-700">Mata kuliah</label>
                    <select id="course_id" name="course_id" required class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15" @error('course_id') aria-invalid="true" aria-describedby="course_id-error" @enderror>
                        <option value="">Pilih mata kuliah</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}" @selected((string) old('course_id', $courseSection->course_id) === (string) $course->id)>
                                {{ $course->code }} - {{ $course->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('course_id')
                        <p id="course_id-error" class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="name" class="block text-sm font-medium text-slate-700">Nama kelas</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $courseSection->name) }}" maxlength="255" required autofocus class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                    @error('name')
                        <p id="name-error" class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="academic_year" class="block text-sm font-medium text-slate-700">Tahun akademik</label>
                    <input id="academic_year" name="academic_year" type="text" value="{{ old('academic_year', $courseSection->academic_year) }}" maxlength="255" required class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15" @error('academic_year') aria-invalid="true" aria-describedby="academic_year-error" @enderror>
                    @error('academic_year')
                        <p id="academic_year-error" class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="semester" class="block text-sm font-medium text-slate-700">Semester</label>
                    <select id="semester" name="semester" required class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15" @error('semester') aria-invalid="true" aria-describedby="semester-error" @enderror>
                        <option value="">Pilih semester</option>
                        <option value="ganjil" @selected(old('semester', $courseSection->semester) === 'ganjil')>Ganjil</option>
                        <option value="genap" @selected(old('semester', $courseSection->semester) === 'genap')>Genap</option>
                    </select>
                    @error('semester')
                        <p id="semester-error" class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <a href="{{ route('course-sections.show', $courseSection) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-500/15">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/25">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection
