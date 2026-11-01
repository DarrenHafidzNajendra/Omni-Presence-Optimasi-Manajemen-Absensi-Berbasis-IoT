@extends('layouts.app')

@section('title', 'Buat Sesi Absensi')

@section('content')
    <div class="mx-auto max-w-3xl space-y-8">
        <section class="space-y-2">
            <p class="text-sm font-semibold tracking-wide text-blue-700">SESI ABSENSI</p>
            <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Buat Sesi Reguler</h1>
            <p class="text-sm leading-6 text-slate-600 sm:text-base">{{ $courseSection->name }} - {{ $courseSection->course->name }}</p>
        </section>

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
            <form method="POST" action="{{ route('course-sections.attendance-sessions.store', $courseSection) }}" class="space-y-8">
                @csrf

                {{-- Kelompok 1: Informasi Sesi --}}
                <fieldset class="space-y-4">
                    <legend class="w-full border-b border-slate-200 pb-2 text-base font-semibold text-slate-900">
                        1. Informasi Sesi
                    </legend>

                    @if ($schedules->isNotEmpty())
                        <div class="space-y-2">
                            <label for="class_schedule_id" class="block text-sm font-medium text-slate-700">Jadwal Perkuliahan (Opsional)</label>
                            <select id="class_schedule_id" name="class_schedule_id" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
                                <option value="">Pilih jadwal terkait (jika ada)</option>
                                @foreach ($schedules as $sch)
                                    <option value="{{ $sch->id }}" @selected(old('class_schedule_id') == $sch->id)>
                                        Hari {{ $sch->day_of_week }} ({{ $sch->starts_at }} - {{ $sch->ends_at }}) - {{ ucfirst($sch->session_type) }} / {{ ucfirst($sch->delivery_mode) }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-slate-500">Jika jadwal dipilih, hari pelaksanaan pada tanggal mulai harus cocok dengan hari jadwal.</p>
                            @error('class_schedule_id') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div class="space-y-2">
                        <label for="title" class="block text-sm font-medium text-slate-700">Judul / Topik Sesi</label>
                        <input id="title" name="title" type="text" value="{{ old('title') }}" required placeholder="Contoh: Pertemuan 1 - Pengantar Basis Data" class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
                        @error('title') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="space-y-2">
                            <label for="session_type" class="block text-sm font-medium text-slate-700">Tipe Sesi</label>
                            <select id="session_type" name="session_type" required class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
                                <option value="teori" @selected(old('session_type') === 'teori')>Teori</option>
                                <option value="praktikum" @selected(old('session_type') === 'praktikum')>Praktikum</option>
                            </select>
                            @error('session_type') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="delivery_mode" class="block text-sm font-medium text-slate-700">Mode Pembelajaran</label>
                            <select id="delivery_mode" name="delivery_mode" required class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
                                <option value="onsite" @selected(old('delivery_mode') === 'onsite')>Onsite</option>
                                <option value="online" @selected(old('delivery_mode') === 'online')>Online</option>
                            </select>
                            @error('delivery_mode') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </fieldset>

                {{-- Kelompok 2: Waktu Perkuliahan --}}
                <fieldset class="space-y-4">
                    <legend class="w-full border-b border-slate-200 pb-2 text-base font-semibold text-slate-900">
                        2. Waktu Perkuliahan
                    </legend>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="space-y-2">
                            <label for="starts_at" class="block text-sm font-medium text-slate-700">Waktu Mulai Perkuliahan</label>
                            <input id="starts_at" name="starts_at" type="text" value="{{ old('starts_at') }}" required placeholder="YYYY-MM-DD HH:MM (contoh: 2026-11-05 08:00)" class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
                            @error('starts_at') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="ends_at" class="block text-sm font-medium text-slate-700">Waktu Selesai Perkuliahan</label>
                            <input id="ends_at" name="ends_at" type="text" value="{{ old('ends_at') }}" required placeholder="YYYY-MM-DD HH:MM (contoh: 2026-11-05 10:00)" class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
                            @error('ends_at') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </fieldset>

                {{-- Kelompok 3: Waktu Presensi --}}
                <fieldset class="space-y-4">
                    <legend class="w-full border-b border-slate-200 pb-2 text-base font-semibold text-slate-900">
                        3. Waktu Presensi
                    </legend>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="space-y-2">
                            <label for="attendance_opens_at" class="block text-sm font-medium text-slate-700">Waktu Buka Absensi</label>
                            <input id="attendance_opens_at" name="attendance_opens_at" type="text" value="{{ old('attendance_opens_at') }}" required placeholder="YYYY-MM-DD HH:MM (contoh: 2026-11-05 07:45)" class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
                            @error('attendance_opens_at') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="attendance_closes_at" class="block text-sm font-medium text-slate-700">Waktu Tutup Absensi</label>
                            <input id="attendance_closes_at" name="attendance_closes_at" type="text" value="{{ old('attendance_closes_at') }}" required placeholder="YYYY-MM-DD HH:MM (contoh: 2026-11-05 10:15)" class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
                            @error('attendance_closes_at') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </fieldset>

                {{-- Kelompok 4: Pengaturan Presensi --}}
                <fieldset class="space-y-4">
                    <legend class="w-full border-b border-slate-200 pb-2 text-base font-semibold text-slate-900">
                        4. Pengaturan Presensi
                    </legend>

                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-700">Metode Presensi yang Diizinkan</label>
                        <div class="flex flex-wrap gap-4">
                            @php
                                $selectedMethods = old('allowed_methods', ['manual', 'qr_code']);
                            @endphp
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="allowed_methods[]" value="manual" @checked(in_array('manual', (array) $selectedMethods)) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                Manual (oleh Dosen)
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="allowed_methods[]" value="qr_code" @checked(in_array('qr_code', (array) $selectedMethods)) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                QR Code
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="allowed_methods[]" value="face_recognition" @checked(in_array('face_recognition', (array) $selectedMethods)) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                Face Recognition
                            </label>
                        </div>
                        @error('allowed_methods') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="late_after_minutes" class="block text-sm font-medium text-slate-700">Batas Menit Terlambat (Opsional)</label>
                        <input id="late_after_minutes" name="late_after_minutes" type="number" min="0" max="360" value="{{ old('late_after_minutes', 15) }}" placeholder="15" class="block w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
                        <p class="text-xs text-slate-500">Menit setelah waktu buka absensi sebelum mahasiswa dianggap terlambat.</p>
                        @error('late_after_minutes') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                </fieldset>

                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                    <a href="{{ route('course-sections.attendance-sessions.index', $courseSection) }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">Simpan Sesi</button>
                </div>
            </form>
        </section>
    </div>
@endsection
