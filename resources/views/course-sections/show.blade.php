@extends('layouts.app')

@section('title', 'Detail Kelas')

@section('content')
    <div class="mx-auto max-w-3xl space-y-8">
        <section class="space-y-2">
            <p class="text-sm font-semibold tracking-wide text-blue-700">AKADEMIK</p>
            <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Detail Kelas</h1>
            <p class="max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                Informasi kelas {{ $courseSection->name }} yang terdaftar di Omni-Presence.
            </p>
        </section>

        @if (session('success'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        <section aria-label="Informasi kelas" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <dl class="divide-y divide-slate-100">
                <div class="grid gap-2 px-5 py-4 sm:grid-cols-[12rem_minmax(0,1fr)] sm:gap-6 sm:px-8">
                    <dt class="text-sm font-medium text-slate-500">Mata Kuliah</dt>
                    <dd class="text-sm text-slate-900">
                        <span class="block font-semibold">{{ $courseSection->course->code }}</span>
                        <span class="block">{{ $courseSection->course->name }}</span>
                    </dd>
                </div>
                <div class="grid gap-2 px-5 py-4 sm:grid-cols-[12rem_minmax(0,1fr)] sm:gap-6 sm:px-8">
                    <dt class="text-sm font-medium text-slate-500">Nama Kelas</dt>
                    <dd class="text-sm font-semibold text-slate-900">{{ $courseSection->name }}</dd>
                </div>
                <div class="grid gap-2 px-5 py-4 sm:grid-cols-[12rem_minmax(0,1fr)] sm:gap-6 sm:px-8">
                    <dt class="text-sm font-medium text-slate-500">Tahun Akademik</dt>
                    <dd class="text-sm text-slate-900">{{ $courseSection->academic_year }}</dd>
                </div>
                <div class="grid gap-2 px-5 py-4 sm:grid-cols-[12rem_minmax(0,1fr)] sm:gap-6 sm:px-8">
                    <dt class="text-sm font-medium text-slate-500">Semester</dt>
                    <dd class="text-sm text-slate-900">{{ ucfirst($courseSection->semester) }}</dd>
                </div>
                <div class="grid gap-2 px-5 py-4 sm:grid-cols-[12rem_minmax(0,1fr)] sm:gap-6 sm:px-8">
                    <dt class="text-sm font-medium text-slate-500">Status</dt>
                    <dd>
                        <span @class([
                            'inline-flex rounded-full px-2.5 py-1 text-xs font-medium',
                            'bg-emerald-100 text-emerald-800' => $courseSection->is_active,
                            'bg-red-100 text-red-800' => ! $courseSection->is_active,
                        ])>
                            {{ $courseSection->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </dd>
                </div>
            </dl>
        </section>

        <section aria-labelledby="lecturers-heading" class="space-y-4">
            <div>
                <h2 id="lecturers-heading" class="text-lg font-semibold text-slate-950">Dosen pengampu</h2>
                <p class="mt-1 text-sm text-slate-500">Dosen yang ditugaskan untuk mengajar kelas ini.</p>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                @if ($courseSection->lecturers->isEmpty())
                    <p class="px-5 py-6 text-sm text-slate-500 sm:px-8">Belum ada dosen pengampu.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-left">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:px-8">Nama</th>
                                    <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-500">NIDN</th>
                                    <th scope="col" class="px-5 py-3.5 text-xs font-semibold uppercase tracking-wide text-slate-500">Jenis Pengajaran</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($courseSection->lecturers as $lecturer)
                                    <tr>
                                        <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-900 sm:px-8">
                                            {{ $lecturer->user->name }}
                                            @if (! $lecturer->user->is_active)
                                                <span class="ml-1 text-xs font-normal text-slate-500">(Nonaktif)</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $lecturer->user->nidn }}</td>
                                        <td class="whitespace-nowrap px-5 py-4 text-sm capitalize text-slate-600">{{ $lecturer->teaching_type }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>

        <section aria-labelledby="assign-lecturer-heading" class="space-y-4">
            <div>
                <h2 id="assign-lecturer-heading" class="text-lg font-semibold text-slate-950">Tambah dosen pengampu</h2>
                <p class="mt-1 text-sm text-slate-500">Pilih dosen aktif dan jenis pengajaran untuk kelas ini.</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
                <form method="POST" action="{{ route('course-sections.lecturers.store', $courseSection) }}" class="space-y-6">
                    @csrf
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="space-y-2">
                            <label for="user_id" class="block text-sm font-medium text-slate-700">Dosen</label>
                            <select
                                id="user_id"
                                name="user_id"
                                required
                                class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                                @error('user_id') aria-invalid="true" aria-describedby="user-id-error" @enderror
                            >
                                <option value="">Pilih dosen</option>
                                @foreach ($lecturerOptions as $lecturerOption)
                                    <option value="{{ $lecturerOption->id }}" @selected((string) old('user_id') === (string) $lecturerOption->id)>
                                        {{ $lecturerOption->name }} ({{ $lecturerOption->nidn }})
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <p id="user-id-error" class="text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="teaching_type" class="block text-sm font-medium text-slate-700">Jenis pengajaran</label>
                            <select
                                id="teaching_type"
                                name="teaching_type"
                                required
                                class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                                @error('teaching_type') aria-invalid="true" aria-describedby="teaching-type-error" @enderror
                            >
                                <option value="">Pilih jenis pengajaran</option>
                                <option value="teori" @selected(old('teaching_type') === 'teori')>Teori</option>
                                <option value="praktikum" @selected(old('teaching_type') === 'praktikum')>Praktikum</option>
                            </select>
                            @error('teaching_type')
                                <p id="teaching-type-error" class="text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/25 sm:w-auto"
                    >
                        Simpan Penugasan
                    </button>
                </form>
            </div>
        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('course-sections.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-500/15">
                Kembali ke Daftar
            </a>
            <div class="flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('course-sections.edit', $courseSection) }}" class="inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/25">
                    Edit Kelas
                </a>
                <form
                    class="shrink-0"
                    method="POST"
                    action="{{ route('course-sections.status', $courseSection) }}"
                    onsubmit="{{ $courseSection->is_active ? "return confirm('Nonaktifkan kelas ini?')" : '' }}"
                >
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="is_active" value="{{ $courseSection->is_active ? '0' : '1' }}">
                    <button
                        type="submit"
                        @class([
                            'inline-flex items-center justify-center rounded-lg border px-3 py-2 text-sm font-semibold transition focus:outline-none focus:ring-4',
                            'border-red-300 text-red-700 hover:bg-red-50 focus:ring-red-500/15' => $courseSection->is_active,
                            'border-emerald-300 text-emerald-700 hover:bg-emerald-50 focus:ring-emerald-500/15' => ! $courseSection->is_active,
                        ])
                    >
                        {{ $courseSection->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
