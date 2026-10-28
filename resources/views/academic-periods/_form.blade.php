@csrf

<div class="grid gap-6 sm:grid-cols-2">
    @foreach ([
        ['name' => 'name', 'label' => 'Nama periode', 'type' => 'text'],
        ['name' => 'academic_year', 'label' => 'Tahun akademik', 'type' => 'text'],
        ['name' => 'starts_on', 'label' => 'Mulai periode', 'type' => 'date'],
        ['name' => 'ends_on', 'label' => 'Selesai periode', 'type' => 'date'],
        ['name' => 'ats_starts_on', 'label' => 'Mulai ATS', 'type' => 'date'],
        ['name' => 'ats_ends_on', 'label' => 'Selesai ATS', 'type' => 'date'],
        ['name' => 'uas_starts_on', 'label' => 'Mulai UAS', 'type' => 'date'],
        ['name' => 'uas_ends_on', 'label' => 'Selesai UAS', 'type' => 'date'],
        ['name' => 'replacement_deadline', 'label' => 'Batas kelas pengganti', 'type' => 'date'],
    ] as $field)
        <div class="space-y-2">
            <label for="{{ $field['name'] }}" class="block text-sm font-medium text-slate-700">{{ $field['label'] }}</label>
            <input
                id="{{ $field['name'] }}"
                name="{{ $field['name'] }}"
                type="{{ $field['type'] }}"
                value="{{ old($field['name'], $field['type'] === 'date' ? $academicPeriod?->{$field['name']}?->format('Y-m-d') : $academicPeriod?->{$field['name']} ?? '') }}"
                required
                class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15"
                @error($field['name']) aria-invalid="true" aria-describedby="{{ $field['name'] }}-error" @enderror
            >
            @error($field['name'])
                <p id="{{ $field['name'] }}-error" class="text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>
    @endforeach

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
</div>

<div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
    <a href="{{ route('academic-periods.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Batal</a>
    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/25">{{ $academicPeriod ? 'Simpan Perubahan' : 'Simpan Periode' }}</button>
</div>
