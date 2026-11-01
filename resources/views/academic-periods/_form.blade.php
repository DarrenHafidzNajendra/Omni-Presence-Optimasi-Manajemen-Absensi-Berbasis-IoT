@csrf

<div class="space-y-8">
    {{-- Kelompok 1: Informasi Periode Akademik --}}
    <fieldset class="space-y-4">
        <legend class="text-base font-semibold text-slate-900 border-b border-slate-200 pb-2 w-full">
            1. Informasi Periode Akademik
        </legend>
        <div class="grid gap-6 sm:grid-cols-2">
            <div class="space-y-2">
                <label for="name" class="block text-sm font-medium text-slate-700">Nama periode</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name', $academicPeriod?->name ?? '') }}"
                    required
                    placeholder="Contoh: Semester Ganjil 2026/2027"
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                    @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
                >
                @error('name')
                    <p id="name-error" class="text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <label for="academic_year" class="block text-sm font-medium text-slate-700">Tahun akademik</label>
                <input
                    id="academic_year"
                    name="academic_year"
                    type="text"
                    value="{{ old('academic_year', $academicPeriod?->academic_year ?? '') }}"
                    required
                    placeholder="Contoh: 2026/2027"
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                    @error('academic_year') aria-invalid="true" aria-describedby="academic_year-error" @enderror
                >
                @error('academic_year')
                    <p id="academic_year-error" class="text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <label for="semester" class="block text-sm font-medium text-slate-700">Semester</label>
                <select id="semester" name="semester" required class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15" @error('semester') aria-invalid="true" aria-describedby="semester-error" @enderror>
                    <option value="">Pilih semester</option>
                    <option value="ganjil" @selected(old('semester', $academicPeriod?->semester) === 'ganjil')>Ganjil</option>
                    <option value="genap" @selected(old('semester', $academicPeriod?->semester) === 'genap')>Genap</option>
                </select>
                @error('semester')
                    <p id="semester-error" class="text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <label for="starts_on" class="block text-sm font-medium text-slate-700">Mulai periode</label>
                <input
                    id="starts_on"
                    name="starts_on"
                    type="date"
                    value="{{ old('starts_on', $academicPeriod?->starts_on ? \Carbon\Carbon::parse($academicPeriod->starts_on)->format('Y-m-d') : '') }}"
                    required
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                    @error('starts_on') aria-invalid="true" aria-describedby="starts_on-error" @enderror
                >
                @error('starts_on')
                    <p id="starts_on-error" class="text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <label for="ends_on" class="block text-sm font-medium text-slate-700">Selesai periode</label>
                <input
                    id="ends_on"
                    name="ends_on"
                    type="date"
                    value="{{ old('ends_on', $academicPeriod?->ends_on ? \Carbon\Carbon::parse($academicPeriod->ends_on)->format('Y-m-d') : '') }}"
                    required
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                    @error('ends_on') aria-invalid="true" aria-describedby="ends_on-error" @enderror
                >
                @error('ends_on')
                    <p id="ends_on-error" class="text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </fieldset>

    {{-- Kelompok 2: Jadwal ATS dan UAS --}}
    <fieldset class="space-y-4">
        <legend class="text-base font-semibold text-slate-900 border-b border-slate-200 pb-2 w-full">
            2. Jadwal ATS dan UAS
        </legend>
        <div class="grid gap-6 sm:grid-cols-2">
            <div class="space-y-2">
                <label for="ats_starts_on" class="block text-sm font-medium text-slate-700">Mulai ATS</label>
                <input
                    id="ats_starts_on"
                    name="ats_starts_on"
                    type="date"
                    value="{{ old('ats_starts_on', $academicPeriod?->ats_starts_on ? \Carbon\Carbon::parse($academicPeriod->ats_starts_on)->format('Y-m-d') : '') }}"
                    required
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                    @error('ats_starts_on') aria-invalid="true" aria-describedby="ats_starts_on-error" @enderror
                >
                @error('ats_starts_on')
                    <p id="ats_starts_on-error" class="text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <label for="ats_ends_on" class="block text-sm font-medium text-slate-700">Selesai ATS</label>
                <input
                    id="ats_ends_on"
                    name="ats_ends_on"
                    type="date"
                    value="{{ old('ats_ends_on', $academicPeriod?->ats_ends_on ? \Carbon\Carbon::parse($academicPeriod->ats_ends_on)->format('Y-m-d') : '') }}"
                    required
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                    @error('ats_ends_on') aria-invalid="true" aria-describedby="ats_ends_on-error" @enderror
                >
                @error('ats_ends_on')
                    <p id="ats_ends_on-error" class="text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <label for="uas_starts_on" class="block text-sm font-medium text-slate-700">Mulai UAS</label>
                <input
                    id="uas_starts_on"
                    name="uas_starts_on"
                    type="date"
                    value="{{ old('uas_starts_on', $academicPeriod?->uas_starts_on ? \Carbon\Carbon::parse($academicPeriod->uas_starts_on)->format('Y-m-d') : '') }}"
                    required
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                    @error('uas_starts_on') aria-invalid="true" aria-describedby="uas_starts_on-error" @enderror
                >
                @error('uas_starts_on')
                    <p id="uas_starts_on-error" class="text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-2">
                <label for="uas_ends_on" class="block text-sm font-medium text-slate-700">Selesai UAS</label>
                <input
                    id="uas_ends_on"
                    name="uas_ends_on"
                    type="date"
                    value="{{ old('uas_ends_on', $academicPeriod?->uas_ends_on ? \Carbon\Carbon::parse($academicPeriod->uas_ends_on)->format('Y-m-d') : '') }}"
                    required
                    class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                    @error('uas_ends_on') aria-invalid="true" aria-describedby="uas_ends_on-error" @enderror
                >
                @error('uas_ends_on')
                    <p id="uas_ends_on-error" class="text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </fieldset>

    {{-- Kelompok 3: Jadwal Minggu Pengganti (Collapsible) --}}
    @php
        $hasReplacementDataOrErrors = filled(old('ats_replacement_starts_on', $academicPeriod?->ats_replacement_starts_on))
            || filled(old('ats_replacement_ends_on', $academicPeriod?->ats_replacement_ends_on))
            || filled(old('uas_replacement_starts_on', $academicPeriod?->uas_replacement_starts_on))
            || filled(old('uas_replacement_ends_on', $academicPeriod?->uas_replacement_ends_on))
            || $errors->hasAny(['ats_replacement_starts_on', 'ats_replacement_ends_on', 'uas_replacement_starts_on', 'uas_replacement_ends_on']);
    @endphp
    <details class="group rounded-xl border border-slate-200 bg-slate-50/50 p-4" @if ($hasReplacementDataOrErrors) open @endif>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-2 text-base font-semibold text-slate-900">
            <span>3. Jadwal Minggu Pengganti <span class="text-xs font-normal text-slate-500">(opsional — klik untuk mengatur)</span></span>
            <span class="text-xs font-medium text-blue-700 group-open:hidden">Tampilkan</span>
            <span class="hidden text-xs font-medium text-slate-500 group-open:inline">Sembunyikan</span>
        </summary>
        <fieldset class="mt-4 space-y-4 border-t border-slate-200 pt-4">
            <p class="text-xs text-slate-500">
                Minggu pengganti berlangsung hari Senin–Jumat pada satu minggu perkuliahan sebelum ATS atau UAS.
            </p>
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="space-y-2">
                    <label for="ats_replacement_starts_on" class="block text-sm font-medium text-slate-700">
                        Mulai pengganti ATS <span class="text-slate-400 font-normal">(opsional)</span>
                    </label>
                    <input
                        id="ats_replacement_starts_on"
                        name="ats_replacement_starts_on"
                        type="date"
                        value="{{ old('ats_replacement_starts_on', $academicPeriod?->ats_replacement_starts_on ? \Carbon\Carbon::parse($academicPeriod->ats_replacement_starts_on)->format('Y-m-d') : '') }}"
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                        @error('ats_replacement_starts_on') aria-invalid="true" aria-describedby="ats_replacement_starts_on-error" @enderror
                    >
                    <p class="text-xs text-slate-500">Senin dalam satu minggu perkuliahan sebelum ATS.</p>
                    @error('ats_replacement_starts_on')
                        <p id="ats_replacement_starts_on-error" class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="ats_replacement_ends_on" class="block text-sm font-medium text-slate-700">
                        Selesai pengganti ATS <span class="text-slate-400 font-normal">(opsional)</span>
                    </label>
                    <input
                        id="ats_replacement_ends_on"
                        name="ats_replacement_ends_on"
                        type="date"
                        value="{{ old('ats_replacement_ends_on', $academicPeriod?->ats_replacement_ends_on ? \Carbon\Carbon::parse($academicPeriod->ats_replacement_ends_on)->format('Y-m-d') : '') }}"
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                        @error('ats_replacement_ends_on') aria-invalid="true" aria-describedby="ats_replacement_ends_on-error" @enderror
                    >
                    <p class="text-xs text-slate-500">Jumat dalam satu minggu perkuliahan sebelum ATS.</p>
                    @error('ats_replacement_ends_on')
                        <p id="ats_replacement_ends_on-error" class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="uas_replacement_starts_on" class="block text-sm font-medium text-slate-700">
                        Mulai pengganti UAS <span class="text-slate-400 font-normal">(opsional)</span>
                    </label>
                    <input
                        id="uas_replacement_starts_on"
                        name="uas_replacement_starts_on"
                        type="date"
                        value="{{ old('uas_replacement_starts_on', $academicPeriod?->uas_replacement_starts_on ? \Carbon\Carbon::parse($academicPeriod->uas_replacement_starts_on)->format('Y-m-d') : '') }}"
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                        @error('uas_replacement_starts_on') aria-invalid="true" aria-describedby="uas_replacement_starts_on-error" @enderror
                    >
                    <p class="text-xs text-slate-500">Senin dalam satu minggu perkuliahan sebelum UAS.</p>
                    @error('uas_replacement_starts_on')
                        <p id="uas_replacement_starts_on-error" class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="uas_replacement_ends_on" class="block text-sm font-medium text-slate-700">
                        Selesai pengganti UAS <span class="text-slate-400 font-normal">(opsional)</span>
                    </label>
                    <input
                        id="uas_replacement_ends_on"
                        name="uas_replacement_ends_on"
                        type="date"
                        value="{{ old('uas_replacement_ends_on', $academicPeriod?->uas_replacement_ends_on ? \Carbon\Carbon::parse($academicPeriod->uas_replacement_ends_on)->format('Y-m-d') : '') }}"
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                        @error('uas_replacement_ends_on') aria-invalid="true" aria-describedby="uas_replacement_ends_on-error" @enderror
                    >
                    <p class="text-xs text-slate-500">Jumat dalam satu minggu perkuliahan sebelum UAS.</p>
                    @error('uas_replacement_ends_on')
                        <p id="uas_replacement_ends_on-error" class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </fieldset>
    </details>
</div>

<div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end border-t border-slate-100 pt-6">
    <a href="{{ route('academic-periods.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Batal</a>
    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/25">{{ $academicPeriod ? 'Simpan Perubahan' : 'Simpan Periode' }}</button>
</div>
