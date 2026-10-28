<div class="grid gap-6 sm:grid-cols-2">
    <div class="space-y-2">
        <label for="session_type" class="block text-sm font-medium text-slate-700">Tipe sesi</label>
        <select id="session_type" name="session_type" required class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15" @error('session_type') aria-invalid="true" aria-describedby="session_type-error" @enderror>
            <option value="">Pilih tipe sesi</option>
            <option value="teori" @selected(old('session_type', $schedule?->session_type) === 'teori')>Teori</option>
            <option value="praktikum" @selected(old('session_type', $schedule?->session_type) === 'praktikum')>Praktikum</option>
        </select>
        @error('session_type') <p id="session_type-error" class="text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-2">
        <label for="delivery_mode" class="block text-sm font-medium text-slate-700">Mode pembelajaran</label>
        <select id="delivery_mode" name="delivery_mode" required class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15" @error('delivery_mode') aria-invalid="true" aria-describedby="delivery_mode-error" @enderror>
            <option value="">Pilih mode</option>
            <option value="onsite" @selected(old('delivery_mode', $schedule?->delivery_mode) === 'onsite')>Onsite</option>
            <option value="online" @selected(old('delivery_mode', $schedule?->delivery_mode) === 'online')>Online</option>
        </select>
        @error('delivery_mode') <p id="delivery_mode-error" class="text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-2">
        <label for="day_of_week" class="block text-sm font-medium text-slate-700">Hari</label>
        <select id="day_of_week" name="day_of_week" required class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15" @error('day_of_week') aria-invalid="true" aria-describedby="day_of_week-error" @enderror>
            <option value="">Pilih hari</option>
            @foreach ($days as $dayValue => $dayLabel)
                <option value="{{ $dayValue }}" @selected((string) old('day_of_week', $schedule?->day_of_week) === (string) $dayValue)>{{ $dayLabel }}</option>
            @endforeach
        </select>
        @error('day_of_week') <p id="day_of_week-error" class="text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-2">
        <label for="timezone" class="block text-sm font-medium text-slate-700">Zona waktu</label>
        <input id="timezone" name="timezone" type="text" value="{{ old('timezone', $schedule?->timezone ?? 'Asia/Jakarta') }}" required maxlength="255" class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15" @error('timezone') aria-invalid="true" aria-describedby="timezone-error" @enderror>
        @error('timezone') <p id="timezone-error" class="text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-2">
        <label for="starts_at" class="block text-sm font-medium text-slate-700">Jam mulai</label>
        <input id="starts_at" name="starts_at" type="time" value="{{ old('starts_at', $schedule?->starts_at ? substr((string) $schedule->starts_at, 0, 5) : '') }}" required class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15" @error('starts_at') aria-invalid="true" aria-describedby="starts_at-error" @enderror>
        @error('starts_at') <p id="starts_at-error" class="text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-2">
        <label for="ends_at" class="block text-sm font-medium text-slate-700">Jam selesai</label>
        <input id="ends_at" name="ends_at" type="time" value="{{ old('ends_at', $schedule?->ends_at ? substr((string) $schedule->ends_at, 0, 5) : '') }}" required class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15" @error('ends_at') aria-invalid="true" aria-describedby="ends_at-error" @enderror>
        @error('ends_at') <p id="ends_at-error" class="text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-2">
        <label for="location" class="block text-sm font-medium text-slate-700">Ruangan/lokasi onsite</label>
        <input id="location" name="location" type="text" value="{{ old('location', $schedule?->location) }}" maxlength="255" class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15" @error('location') aria-invalid="true" aria-describedby="location-error" @enderror>
        @error('location') <p id="location-error" class="text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-2">
        <label for="meeting_url" class="block text-sm font-medium text-slate-700">URL pertemuan online</label>
        <input id="meeting_url" name="meeting_url" type="url" value="{{ old('meeting_url', $schedule?->meeting_url) }}" maxlength="255" placeholder="https://..." class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15" @error('meeting_url') aria-invalid="true" aria-describedby="meeting_url-error" @enderror>
        @error('meeting_url') <p id="meeting_url-error" class="text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-2">
        <label for="valid_from" class="block text-sm font-medium text-slate-700">Berlaku mulai</label>
        <input id="valid_from" name="valid_from" type="date" value="{{ old('valid_from', $schedule?->valid_from?->format('Y-m-d')) }}" class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15" @error('valid_from') aria-invalid="true" aria-describedby="valid_from-error" @enderror>
        @error('valid_from') <p id="valid_from-error" class="text-sm text-red-700">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-2">
        <label for="valid_until" class="block text-sm font-medium text-slate-700">Berlaku sampai</label>
        <input id="valid_until" name="valid_until" type="date" value="{{ old('valid_until', $schedule?->valid_until?->format('Y-m-d')) }}" class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/15" @error('valid_until') aria-invalid="true" aria-describedby="valid_until-error" @enderror>
        @error('valid_until') <p id="valid_until-error" class="text-sm text-red-700">{{ $message }}</p> @enderror
    </div>
</div>

<label class="flex items-center gap-3 text-sm font-medium text-slate-700">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $schedule?->is_active ?? true)) class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500/25">
    Jadwal aktif
</label>

<div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
    <a href="{{ route('course-sections.schedules.index', $courseSection) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-500/15">Batal</a>
    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/25">{{ isset($schedule) ? 'Simpan Perubahan' : 'Simpan Jadwal' }}</button>
</div>
