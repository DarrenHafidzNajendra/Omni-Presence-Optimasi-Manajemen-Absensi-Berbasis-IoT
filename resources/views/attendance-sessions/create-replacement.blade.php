@extends('layouts.app')

@section('title', 'Buat Sesi Pengganti')

@section('content')
    <div class="mx-auto max-w-3xl space-y-8">
        <section class="space-y-2">
            <p class="text-sm font-semibold tracking-wide text-blue-700">SESI PENGGANTI</p>
            <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Buat Sesi Pengganti</h1>
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

        {{-- Info Sesi Asal & Periode Akademik --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 space-y-4">
            <h2 class="text-base font-bold text-slate-900">Informasi Sesi Asal</h2>
            <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2 text-sm">
                <div>
                    <dt class="font-medium text-slate-500">Judul Sesi Asal:</dt>
                    <dd class="font-semibold text-slate-900">{{ $originalSession->title }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500">Jadwal Sesi Asal:</dt>
                    <dd class="text-slate-900">{{ $originalSession->starts_at?->format('d/m/Y H:i') }} - {{ $originalSession->ends_at?->format('H:i') }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500">Status Sesi Asal:</dt>
                    <dd class="font-medium capitalize text-slate-900">{{ $originalSession->status }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500">Jenis Sesi Asal:</dt>
                    <dd class="capitalize text-slate-900">{{ $originalSession->session_kind ?? 'regular' }} ({{ $originalSession->session_type }})</dd>
                </div>
            </dl>

            <div class="border-t border-slate-100 pt-4">
                <h3 class="text-sm font-bold text-slate-900 mb-2">Konfigurasi Kalender Pengganti ({{ $academicPeriod?->name ?? 'Belum ditentukan' }})</h3>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 text-xs">
                    <div class="rounded-lg bg-slate-50 p-2.5 border border-slate-200">
                        <span class="font-semibold text-slate-700 block">Periode Pengganti ATS:</span>
                        @if ($academicPeriod?->ats_replacement_starts_on && $academicPeriod?->ats_replacement_ends_on)
                            <span class="text-emerald-700 font-medium">
                                {{ $academicPeriod->ats_replacement_starts_on->format('d/m/Y') }} s/d {{ $academicPeriod->ats_replacement_ends_on->format('d/m/Y') }}
                            </span>
                        @else
                            <span class="text-red-600 font-medium">Belum diatur oleh Admin</span>
                        @endif
                    </div>
                    <div class="rounded-lg bg-slate-50 p-2.5 border border-slate-200">
                        <span class="font-semibold text-slate-700 block">Periode Pengganti UAS:</span>
                        @if ($academicPeriod?->uas_replacement_starts_on && $academicPeriod?->uas_replacement_ends_on)
                            <span class="text-emerald-700 font-medium">
                                {{ $academicPeriod->uas_replacement_starts_on->format('d/m/Y') }} s/d {{ $academicPeriod->uas_replacement_ends_on->format('d/m/Y') }}
                            </span>
                        @else
                            <span class="text-red-600 font-medium">Belum diatur oleh Admin</span>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
            <form method="POST" action="{{ route('course-sections.attendance-sessions.replacement', [$courseSection, $originalSession]) }}" class="space-y-6">
                @csrf

                <div class="space-y-2">
                    <label for="replacement_category" class="block text-sm font-medium text-slate-700">Kategori Pengganti</label>
                    <select id="replacement_category" name="replacement_category" required class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
                        <option value="">Pilih kategori pengganti</option>
                        <option value="ats" @selected(old('replacement_category') === 'ats')>
                            ATS @if ($academicPeriod?->ats_replacement_starts_on) ({{ $academicPeriod->ats_replacement_starts_on->format('d/m/Y') }} - {{ $academicPeriod->ats_replacement_ends_on->format('d/m/Y') }}) @else [Rentang tanggal belum diatur] @endif
                        </option>
                        <option value="uas" @selected(old('replacement_category') === 'uas')>
                            UAS @if ($academicPeriod?->uas_replacement_starts_on) ({{ $academicPeriod->uas_replacement_starts_on->format('d/m/Y') }} - {{ $academicPeriod->uas_replacement_ends_on->format('d/m/Y') }}) @else [Rentang tanggal belum diatur] @endif
                        </option>
                    </select>
                    @error('replacement_category') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="title" class="block text-sm font-medium text-slate-700">Judul Sesi Pengganti</label>
                    <input id="title" name="title" type="text" value="{{ old('title', "Pengganti - {$originalSession->title}") }}" required placeholder="Contoh: Sesi Pengganti Pertemuan 3" class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
                    @error('title') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="space-y-2">
                        <label for="session_type" class="block text-sm font-medium text-slate-700">Tipe Sesi</label>
                        <select id="session_type" name="session_type" required class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
                            <option value="teori" @selected(old('session_type', $originalSession->session_type) === 'teori')>Teori</option>
                            <option value="praktikum" @selected(old('session_type', $originalSession->session_type) === 'praktikum')>Praktikum</option>
                        </select>
                        @error('session_type') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="delivery_mode" class="block text-sm font-medium text-slate-700">Mode Pembelajaran</label>
                        <select id="delivery_mode" name="delivery_mode" required class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
                            <option value="onsite" @selected(old('delivery_mode', $originalSession->delivery_mode) === 'onsite')>Onsite</option>
                            <option value="online" @selected(old('delivery_mode', $originalSession->delivery_mode) === 'online')>Online</option>
                        </select>
                        @error('delivery_mode') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="space-y-2">
                        <label for="starts_at" class="block text-sm font-medium text-slate-700">Waktu Mulai Perkuliahan</label>
                        <input id="starts_at" name="starts_at" type="text" value="{{ old('starts_at') }}" required placeholder="YYYY-MM-DD HH:MM (contoh: 2026-10-15 08:00)" class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
                        <p class="text-xs text-slate-500">Tanggal pelaksanaan harus berada dalam rentang tanggal kategori yang dipilih dan hanya boleh pada hari kerja (Senin–Jumat).</p>
                        @error('starts_at') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="ends_at" class="block text-sm font-medium text-slate-700">Waktu Selesai Perkuliahan</label>
                        <input id="ends_at" name="ends_at" type="text" value="{{ old('ends_at') }}" required placeholder="YYYY-MM-DD HH:MM (contoh: 2026-10-15 10:00)" class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
                        @error('ends_at') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="space-y-2">
                        <label for="attendance_opens_at" class="block text-sm font-medium text-slate-700">Waktu Buka Absensi</label>
                        <input id="attendance_opens_at" name="attendance_opens_at" type="text" value="{{ old('attendance_opens_at') }}" required placeholder="YYYY-MM-DD HH:MM (contoh: 2026-10-15 07:45)" class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
                        @error('attendance_opens_at') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="attendance_closes_at" class="block text-sm font-medium text-slate-700">Waktu Tutup Absensi</label>
                        <input id="attendance_closes_at" name="attendance_closes_at" type="text" value="{{ old('attendance_closes_at') }}" required placeholder="YYYY-MM-DD HH:MM (contoh: 2026-10-15 10:15)" class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
                        @error('attendance_closes_at') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700">Metode Presensi yang Diizinkan</label>
                    <div class="flex flex-wrap gap-4">
                        @php
                            $selectedMethods = old('allowed_methods', (array) ($originalSession->allowed_methods ?? ['manual', 'qr_code']));
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
                    <input id="late_after_minutes" name="late_after_minutes" type="number" min="0" max="360" value="{{ old('late_after_minutes', $originalSession->late_after_minutes ?? 15) }}" placeholder="15" class="block w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15">
                    @error('late_after_minutes') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                    <a href="{{ route('course-sections.attendance-sessions.show', [$courseSection, $originalSession]) }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">Buat Sesi Pengganti</button>
                </div>
            </form>
        </section>
    </div>
@endsection

