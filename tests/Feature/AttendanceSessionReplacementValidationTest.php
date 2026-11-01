<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AcademicPeriod;
use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\CourseSectionLecturer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AttendanceSessionReplacementValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_valid_ats_replacement_session(): void
    {
        $period = $this->createAcademicPeriod([
            'ats_replacement_starts_on' => '2026-10-20',
            'ats_replacement_ends_on' => '2026-10-25',
        ]);
        $section = $this->createCourseSection($period);
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $original = $this->createAttendanceSession($section, [
            'title' => 'Sesi Asli',
            'cancelled_at' => '2026-10-10 08:00:00',
        ]);

        $replacement = $original->createReplacement($lecturer, [
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Sesi Pengganti ATS',
            'starts_at' => '2026-10-22 08:00:00',
            'ends_at' => '2026-10-22 10:00:00',
            'attendance_opens_at' => '2026-10-22 07:45:00',
            'attendance_closes_at' => '2026-10-22 10:15:00',
            'allowed_methods' => ['qr_code'],
            'replacement_category' => 'ats',
        ]);

        $this->assertSame('replacement', $replacement->session_kind);
        $this->assertSame($original->id, $replacement->original_session_id);
        $this->assertSame('ats', $replacement->replacement_category);
        $this->assertSame($section->id, $replacement->course_section_id);
        $this->assertSame('terjadwal', $replacement->status);
    }

    public function test_creating_valid_uas_replacement_session(): void
    {
        $period = $this->createAcademicPeriod([
            'uas_replacement_starts_on' => '2027-01-25',
            'uas_replacement_ends_on' => '2027-01-29',
        ]);
        $section = $this->createCourseSection($period);
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $original = $this->createAttendanceSession($section, [
            'title' => 'Sesi Asli',
            'cancelled_at' => '2026-12-10 08:00:00',
        ]);

        $replacement = $original->createReplacement($lecturer, [
            'session_type' => 'praktikum',
            'delivery_mode' => 'onsite',
            'title' => 'Sesi Pengganti UAS',
            'starts_at' => '2027-01-26 13:00:00',
            'ends_at' => '2027-01-26 15:00:00',
            'attendance_opens_at' => '2027-01-26 12:45:00',
            'attendance_closes_at' => '2027-01-26 15:15:00',
            'allowed_methods' => ['qr_code'],
            'replacement_category' => 'uas',
        ]);

        $this->assertSame('replacement', $replacement->session_kind);
        $this->assertSame($original->id, $replacement->original_session_id);
        $this->assertSame('uas', $replacement->replacement_category);
        $this->assertSame($section->id, $replacement->course_section_id);
    }

    public function test_rejects_replacement_when_date_is_outside_ats_period(): void
    {
        $period = $this->createAcademicPeriod([
            'ats_replacement_starts_on' => '2026-10-20',
            'ats_replacement_ends_on' => '2026-10-25',
        ]);
        $section = $this->createCourseSection($period);
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $original = $this->createAttendanceSession($section);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tanggal sesi pengganti harus berada dalam periode pengganti ATS');

        $original->createReplacement($lecturer, [
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pengganti Diluar Periode',
            'starts_at' => '2026-10-26 08:00:00', // outside ATS replacement period
            'ends_at' => '2026-10-26 10:00:00',
            'attendance_opens_at' => '2026-10-26 07:45:00',
            'attendance_closes_at' => '2026-10-26 10:15:00',
            'allowed_methods' => ['qr_code'],
            'replacement_category' => 'ats',
        ]);
    }

    public function test_rejects_replacement_when_date_is_outside_uas_period(): void
    {
        $period = $this->createAcademicPeriod([
            'uas_replacement_starts_on' => '2027-01-25',
            'uas_replacement_ends_on' => '2027-01-29',
        ]);
        $section = $this->createCourseSection($period);
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $original = $this->createAttendanceSession($section);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tanggal sesi pengganti harus berada dalam periode pengganti UAS');

        $original->createReplacement($lecturer, [
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pengganti Diluar Periode UAS',
            'starts_at' => '2027-01-20 08:00:00', // before UAS replacement period
            'ends_at' => '2027-01-20 10:00:00',
            'attendance_opens_at' => '2027-01-20 07:45:00',
            'attendance_closes_at' => '2027-01-20 10:15:00',
            'allowed_methods' => ['qr_code'],
            'replacement_category' => 'uas',
        ]);
    }

    public function test_rejects_replacement_when_replacement_period_not_set_in_academic_period(): void
    {
        $period = $this->createAcademicPeriod([
            'ats_replacement_starts_on' => null,
            'ats_replacement_ends_on' => null,
        ]);
        $section = $this->createCourseSection($period);
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $original = $this->createAttendanceSession($section);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Periode pengganti ATS belum diatur pada periode akademik.');

        $original->createReplacement($lecturer, [
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pengganti Tanpa Periode',
            'starts_at' => '2026-10-22 08:00:00',
            'ends_at' => '2026-10-22 10:00:00',
            'attendance_opens_at' => '2026-10-22 07:45:00',
            'attendance_closes_at' => '2026-10-22 10:15:00',
            'allowed_methods' => ['qr_code'],
            'replacement_category' => 'ats',
        ]);
    }

    public function test_rejects_replacement_with_invalid_category(): void
    {
        $period = $this->createAcademicPeriod([
            'ats_replacement_starts_on' => '2026-10-20',
            'ats_replacement_ends_on' => '2026-10-25',
        ]);
        $section = $this->createCourseSection($period);
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $original = $this->createAttendanceSession($section);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Kategori sesi pengganti tidak valid.');

        $original->createReplacement($lecturer, [
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pengganti Kategori Salah',
            'starts_at' => '2026-10-22 08:00:00',
            'ends_at' => '2026-10-22 10:00:00',
            'attendance_opens_at' => '2026-10-22 07:45:00',
            'attendance_closes_at' => '2026-10-22 10:15:00',
            'allowed_methods' => ['qr_code'],
            'replacement_category' => 'remedial',
        ]);
    }

    public function test_rejects_replacement_when_course_section_does_not_match_original(): void
    {
        $period = $this->createAcademicPeriod([
            'ats_replacement_starts_on' => '2026-10-20',
            'ats_replacement_ends_on' => '2026-10-25',
        ]);
        $sectionA = $this->createCourseSection($period, 'Kelas A');
        $sectionB = $this->createCourseSection($period, 'Kelas B');
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($sectionA, $lecturer);

        $original = $this->createAttendanceSession($sectionA);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CourseSection sesi pengganti harus sama dengan sesi asal.');

        $original->createReplacement($lecturer, [
            'course_section_id' => $sectionB->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pengganti Beda Section',
            'starts_at' => '2026-10-22 08:00:00',
            'ends_at' => '2026-10-22 10:00:00',
            'attendance_opens_at' => '2026-10-22 07:45:00',
            'attendance_closes_at' => '2026-10-22 10:15:00',
            'allowed_methods' => ['qr_code'],
            'replacement_category' => 'ats',
        ]);
    }

    public function test_rejects_replacement_session_on_saturday_or_sunday(): void
    {
        // 2026-10-19 (Senin) s/d 2026-10-25 (Minggu)
        $period = $this->createAcademicPeriod([
            'ats_replacement_starts_on' => '2026-10-19',
            'ats_replacement_ends_on' => '2026-10-25',
        ]);
        $section = $this->createCourseSection($period);
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $original = $this->createAttendanceSession($section);

        // 2026-10-24 adalah hari Sabtu
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sesi pengganti hanya boleh dilaksanakan pada hari kerja (Senin–Jumat).');

        $original->createReplacement($lecturer, [
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pengganti Hari Sabtu',
            'starts_at' => '2026-10-24 08:00:00',
            'ends_at' => '2026-10-24 10:00:00',
            'attendance_opens_at' => '2026-10-24 07:45:00',
            'attendance_closes_at' => '2026-10-24 10:15:00',
            'allowed_methods' => ['qr_code'],
            'replacement_category' => 'ats',
        ]);
    }

    public function test_replacement_sessions_use_their_respective_ats_and_uas_ranges(): void
    {
        // ATS replacement: 2026-10-19 (Senin) - 2026-10-23 (Jumat)
        // UAS replacement: 2027-01-04 (Senin) - 2027-01-08 (Jumat)
        $period = $this->createAcademicPeriod([
            'ats_replacement_starts_on' => '2026-10-19',
            'ats_replacement_ends_on' => '2026-10-23',
            'uas_replacement_starts_on' => '2027-01-04',
            'uas_replacement_ends_on' => '2027-01-08',
        ]);
        $section = $this->createCourseSection($period);
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $original = $this->createAttendanceSession($section);

        // Valid ATS session on Wednesday 2026-10-21
        $atsSession = $original->createReplacement($lecturer, [
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pengganti ATS Valid',
            'starts_at' => '2026-10-21 08:00:00',
            'ends_at' => '2026-10-21 10:00:00',
            'attendance_opens_at' => '2026-10-21 07:45:00',
            'attendance_closes_at' => '2026-10-21 10:15:00',
            'allowed_methods' => ['qr_code'],
            'replacement_category' => 'ats',
        ]);
        $this->assertSame('ats', $atsSession->replacement_category);

        // Valid UAS session on Thursday 2027-01-07
        $uasSession = $original->createReplacement($lecturer, [
            'session_type' => 'praktikum',
            'delivery_mode' => 'online',
            'title' => 'Pengganti UAS Valid',
            'starts_at' => '2027-01-07 13:00:00',
            'ends_at' => '2027-01-07 15:00:00',
            'attendance_opens_at' => '2027-01-07 12:45:00',
            'attendance_closes_at' => '2027-01-07 15:15:00',
            'allowed_methods' => ['qr_code'],
            'replacement_category' => 'uas',
        ]);
        $this->assertSame('uas', $uasSession->replacement_category);
    }

    public function test_completed_sessions_remain_unaffected_when_academic_period_calendar_is_updated(): void
    {
        $period = $this->createAcademicPeriod([
            'ats_replacement_starts_on' => '2026-10-19',
            'ats_replacement_ends_on' => '2026-10-23',
        ]);
        $section = $this->createCourseSection($period);
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $original = $this->createAttendanceSession($section);

        $replacement = $original->createReplacement($lecturer, [
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pengganti Selesai',
            'starts_at' => '2026-10-20 08:00:00',
            'ends_at' => '2026-10-20 10:00:00',
            'attendance_opens_at' => '2026-10-20 07:45:00',
            'attendance_closes_at' => '2026-10-20 10:15:00',
            'allowed_methods' => ['qr_code'],
            'replacement_category' => 'ats',
        ]);

        // Sesi dibuka dan ditutup (selesai) dengan waktu simulasi saat sesi berlangsung
        $sessionTime = Carbon::parse('2026-10-20 08:05:00');
        $replacement->open($lecturer, $sessionTime);
        $replacement->close($lecturer, $sessionTime->copy()->addHour());

        $this->assertSame('ditutup', $replacement->fresh()->status);
        $originalStartsAt = $replacement->fresh()->starts_at;

        // Admin memperbarui periode akademik dengan kalender baru
        $period->update([
            'ats_replacement_starts_on' => '2026-10-26',
            'ats_replacement_ends_on' => '2026-10-30',
        ]);

        $freshReplacement = $replacement->fresh();
        // Sesi yang sudah selesai tidak berubah status atau tanggalnya
        $this->assertSame('ditutup', $freshReplacement->status);
        $this->assertEquals($originalStartsAt, $freshReplacement->starts_at);
        $this->assertSame('ats', $freshReplacement->replacement_category);
    }

    private function createAcademicPeriod(array $overrides = []): AcademicPeriod
    {
        return AcademicPeriod::query()->create(array_merge([
            'name' => 'Semester Ganjil 2026/2027',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'starts_on' => '2026-08-01',
            'ends_on' => '2027-01-31',
            'ats_starts_on' => '2026-10-05',
            'ats_ends_on' => '2026-10-16',
            'uas_starts_on' => '2027-01-11',
            'uas_ends_on' => '2027-01-22',
            'replacement_deadline' => '2027-01-30',
            'is_active' => true,
        ], $overrides));
    }

    private function createCourseSection(AcademicPeriod $period, string $name = 'Kelas A'): CourseSection
    {
        $course = Course::query()->create([
            'code' => 'CS'.fake()->unique()->numerify('###'),
            'name' => 'Mata Kuliah '.fake()->unique()->numerify('###'),
        ]);

        return CourseSection::query()->create([
            'course_id' => $course->id,
            'academic_period_id' => $period->id,
            'name' => $name,
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);
    }

    private function createAttendanceSession(CourseSection $section, array $attributes = []): AttendanceSession
    {
        return AttendanceSession::query()->create(array_merge([
            'course_section_id' => $section->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pertemuan 1',
            'starts_at' => '2026-11-05 08:00:00',
            'ends_at' => '2026-11-05 10:00:00',
            'attendance_opens_at' => '2026-11-05 07:45:00',
            'attendance_closes_at' => '2026-11-05 10:15:00',
            'allowed_methods' => ['qr_code'],
            'status' => 'terjadwal',
        ], $attributes));
    }

    private function assignLecturer(CourseSection $section, User $lecturer, string $type = 'teori'): CourseSectionLecturer
    {
        return CourseSectionLecturer::query()->create([
            'course_section_id' => $section->id,
            'user_id' => $lecturer->id,
            'teaching_type' => $type,
        ]);
    }
}
