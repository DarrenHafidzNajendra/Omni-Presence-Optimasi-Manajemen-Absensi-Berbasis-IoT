@extends('layouts.app')

@section('title', 'Tambah Mata Kuliah')

@section('content')
    <div class="mx-auto max-w-3xl space-y-8">
        <section class="space-y-2">
            <p class="text-sm font-semibold tracking-wide text-blue-700">AKADEMIK</p>
            <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Tambah Mata Kuliah</h1>
            <p class="max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                Tambahkan mata kuliah baru ke dalam sistem Omni-Presence.
            </p>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
            <form method="POST" action="{{ route('courses.store') }}" class="space-y-6">
                @csrf

                <div class="space-y-2">
                    <label for="code" class="block text-sm font-medium text-slate-700">Kode mata kuliah</label>
                    <input id="code" name="code" type="text" value="{{ old('code') }}" maxlength="255" required autofocus class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15" @error('code') aria-invalid="true" aria-describedby="code-error" @enderror>
                    @error('code')
                        <p id="code-error" class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="name" class="block text-sm font-medium text-slate-700">Nama mata kuliah</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" maxlength="255" required class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                    @error('name')
                        <p id="name-error" class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="description" class="block text-sm font-medium text-slate-700">Deskripsi</label>
                    <textarea id="description" name="description" rows="5" class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15" @error('description') aria-invalid="true" aria-describedby="description-error" @enderror>{{ old('description') }}</textarea>
                    @error('description')
                        <p id="description-error" class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <a href="{{ route('courses.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-500/15">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/25">
                        Simpan Mata Kuliah
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection
