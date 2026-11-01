<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AcademicPeriod;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\CourseSectionLecturer;
use App\Models\Enrollment;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AttendanceSessionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_session_opening_records_opened_at_and_opened_by(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $now = Carbon::parse('2026-11-05 08:00:00', 'Asia/Jakarta');
        $session = $this->createAttendanceSession($section, [
            'status' => 'terjadwal',
            'attendance_opens_at' => '2026-11-05 07:45:00',
            'attendance_closes_at' => '2026-11-05 08:30:00',
        ]);

        $session->open($lecturer, $now);

        $session->refresh();
        $this->assertSame('terbuka', $session->status);
        $this->assertTrue($session->isOpen());
        $this->assertSame($lecturer->id, $session->opened_by);
        $this->assertSame('2026-11-05 08:00:00', $session->opened_at->format('Y-m-d H:i:s'));
    }

    public function test_opening_session_before_attendance_opens_at_is_rejected(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $now = Carbon::parse('2026-11-05 07:30:00', 'Asia/Jakarta');
        $session = $this->createAttendanceSession($section, [
            'status' => 'terjadwal',
            'attendance_opens_at' => '2026-11-05 07:45:00',
            'attendance_closes_at' => '2026-11-05 08:30:00',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('belum dapat dibuka');

        $session->open($lecturer, $now);
    }

    public function test_opening_session_after_attendance_closes_at_is_rejected(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $now = Carbon::parse('2026-11-05 08:35:00', 'Asia/Jakarta');
        $session = $this->createAttendanceSession($section, [
            'status' => 'terjadwal',
            'attendance_opens_at' => '2026-11-05 07:45:00',
            'attendance_closes_at' => '2026-11-05 08:30:00',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('telah berakhir');

        $session->open($lecturer, $now);
    }

    public function test_opening_cancelled_or_closed_session_is_rejected(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $now = Carbon::parse('2026-11-05 08:00:00', 'Asia/Jakarta');

        $closedSession = $this->createAttendanceSession($section, [
            'status' => 'ditutup',
            'attendance_opens_at' => '2026-11-05 07:45:00',
            'attendance_closes_at' => '2026-11-05 08:30:00',
        ]);

        try {
            $closedSession->open($lecturer, $now);
            $this->fail('Closing session should not be openable.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('sudah ditutup', $e->getMessage());
        }

        $cancelledSession = $this->createAttendanceSession($section, [
            'status' => 'terjadwal',
            'attendance_opens_at' => '2026-11-05 07:45:00',
            'attendance_closes_at' => '2026-11-05 08:30:00',
        ]);
        $cancelledSession->cancelled_at = '2026-11-04 10:00:00';
        $cancelledSession->cancellation_reason = 'Dibatalkan';
        $cancelledSession->save();

        try {
            $cancelledSession->open($lecturer, $now);
            $this->fail('Cancelled session should not be openable.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('dibatalkan', $e->getMessage());
        }
    }

    public function test_opening_replacement_session_after_replacement_period_ended_is_rejected(): void
    {
        // Periode pengganti ATS berakhir pada 2026-10-23
        $period = AcademicPeriod::query()->create([
            'name' => 'Semester Ganjil 2026/2027',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'starts_on' => '2026-08-01',
            'ends_on' => '2027-01-31',
            'ats_starts_on' => '2026-10-26',
            'ats_ends_on' => '2026-11-06',
            'uas_starts_on' => '2027-01-11',
            'uas_ends_on' => '2027-01-22',
            'ats_replacement_starts_on' => '2026-10-19',
            'ats_replacement_ends_on' => '2026-10-23',
            'replacement_deadline' => '2027-01-30',
            'is_active' => true,
        ]);

        $section = CourseSection::query()->create([
            'course_id' => Course::query()->create(['code' => 'CS999', 'name' => 'Testing Lifecycle'])->id,
            'academic_period_id' => $period->id,
            'name' => 'Kelas Pengganti Overdue',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $replacementSession = $this->createAttendanceSession($section, [
            'status' => 'terjadwal',
            'session_kind' => 'replacement',
            'replacement_category' => 'ats',
            'starts_at' => '2026-10-22 08:00:00',
            'ends_at' => '2026-10-22 10:00:00',
            'attendance_opens_at' => '2026-10-22 07:45:00',
            'attendance_closes_at' => '2026-10-25 10:00:00', // waktu absensi dibuat longgar
        ]);

        // Cek status overdue belum terlaksana
        $this->assertTrue($replacementSession->isUnexecutedOverdue(Carbon::parse('2026-10-24 09:00:00', 'Asia/Jakarta')));

        // Percobaan buka setelah 2026-10-23 (misal 2026-10-24) harus ditolak
        $now = Carbon::parse('2026-10-24 09:00:00', 'Asia/Jakarta');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Batas waktu periode pengganti ATS telah berakhir');

        $replacementSession->open($lecturer, $now);
    }

    public function test_unauthorized_user_cannot_open_session(): void
    {
        $section = $this->createCourseSection();
        $unassignedLecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $student = User::factory()->create(['role' => UserRole::Mahasiswa->value]);

        $now = Carbon::parse('2026-11-05 08:00:00', 'Asia/Jakarta');
        $session = $this->createAttendanceSession($section, [
            'status' => 'terjadwal',
            'attendance_opens_at' => '2026-11-05 07:45:00',
            'attendance_closes_at' => '2026-11-05 08:30:00',
        ]);

        try {
            $session->open($unassignedLecturer, $now);
            $this->fail('Unassigned lecturer should be rejected.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        try {
            $session->open($student, $now);
            $this->fail('Student should be rejected.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }
    }

    public function test_closing_session_records_closed_at_and_closed_by(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $now = Carbon::parse('2026-11-05 08:30:00', 'Asia/Jakarta');
        $session = $this->createAttendanceSession($section, [
            'status' => 'terbuka',
            'opened_at' => '2026-11-05 07:50:00',
            'opened_by' => $lecturer->id,
        ]);

        $session->close($lecturer, $now);

        $session->refresh();
        $this->assertSame('ditutup', $session->status);
        $this->assertTrue($session->isClosed());
        $this->assertSame($lecturer->id, $session->closed_by);
        $this->assertSame('2026-11-05 08:30:00', $session->closed_at->format('Y-m-d H:i:s'));
    }

    public function test_closing_session_repeatedly_is_rejected_and_safe_from_duplicate_modifications(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $firstCloseTime = Carbon::parse('2026-11-05 08:30:00', 'Asia/Jakarta');
        $session = $this->createAttendanceSession($section, [
            'status' => 'terbuka',
            'opened_at' => '2026-11-05 07:50:00',
            'opened_by' => $lecturer->id,
        ]);

        $session->close($lecturer, $firstCloseTime);

        $secondCloseTime = Carbon::parse('2026-11-05 09:00:00', 'Asia/Jakarta');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('sudah ditutup');

        $session->close($lecturer, $secondCloseTime);

        $session->refresh();
        $this->assertSame('2026-11-05 08:30:00', $session->closed_at->format('Y-m-d H:i:s'));
    }

    public function test_closing_session_preserves_existing_attendance_records(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $student1 = User::factory()->create(['role' => UserRole::Mahasiswa->value]);
        $student2 = User::factory()->create(['role' => UserRole::Mahasiswa->value]);
        $student3 = User::factory()->create(['role' => UserRole::Mahasiswa->value]);

        $this->enrollStudent($section, $student1);
        $this->enrollStudent($section, $student2);
        $this->enrollStudent($section, $student3);

        $session = $this->createAttendanceSession($section, [
            'status' => 'terbuka',
            'opened_at' => '2026-11-05 07:50:00',
            'opened_by' => $lecturer->id,
        ]);

        AttendanceRecord::query()->create([
            'attendance_session_id' => $session->id,
            'student_id' => $student1->id,
            'method' => 'qr_code',
            'status' => 'hadir',
            'checked_in_at' => '2026-11-05 08:05:00',
        ]);
        AttendanceRecord::query()->create([
            'attendance_session_id' => $session->id,
            'student_id' => $student2->id,
            'method' => 'manual',
            'status' => 'izin',
            'checked_in_at' => '2026-11-05 08:10:00',
        ]);

        $session->close($lecturer, Carbon::parse('2026-11-05 08:30:00', 'Asia/Jakarta'));

        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $session->id,
            'student_id' => $student1->id,
            'status' => 'hadir',
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $session->id,
            'student_id' => $student2->id,
            'status' => 'izin',
        ]);
    }

    public function test_unrecorded_active_students_are_identified_upon_session_closure(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $recordedStudent = User::factory()->create(['role' => UserRole::Mahasiswa->value]);
        $unrecordedStudent = User::factory()->create(['role' => UserRole::Mahasiswa->value]);
        $inactiveStudent = User::factory()->create(['role' => UserRole::Mahasiswa->value, 'is_active' => false]);
        $droppedStudent = User::factory()->create(['role' => UserRole::Mahasiswa->value]);

        $this->enrollStudent($section, $recordedStudent, 'aktif');
        $this->enrollStudent($section, $unrecordedStudent, 'aktif');
        $this->enrollStudent($section, $inactiveStudent, 'aktif');
        $this->enrollStudent($section, $droppedStudent, 'nonaktif');

        $session = $this->createAttendanceSession($section, [
            'status' => 'terbuka',
            'opened_at' => '2026-11-05 07:50:00',
            'opened_by' => $lecturer->id,
        ]);

        AttendanceRecord::query()->create([
            'attendance_session_id' => $session->id,
            'student_id' => $recordedStudent->id,
            'method' => 'qr_code',
            'status' => 'hadir',
            'checked_in_at' => '2026-11-05 08:05:00',
        ]);

        $unrecorded = $session->getUnrecordedActiveStudents();

        $this->assertCount(1, $unrecorded);
        $this->assertSame($unrecordedStudent->id, $unrecorded->first()->id);
    }

    public function test_closing_session_creates_tidak_hadir_records_for_unrecorded_active_students(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $recordedStudent = User::factory()->create(['role' => UserRole::Mahasiswa->value]);
        $unrecordedStudent = User::factory()->create(['role' => UserRole::Mahasiswa->value]);
        $inactiveStudent = User::factory()->create(['role' => UserRole::Mahasiswa->value, 'is_active' => false]);
        $droppedStudent = User::factory()->create(['role' => UserRole::Mahasiswa->value]);

        $this->enrollStudent($section, $recordedStudent, 'aktif');
        $this->enrollStudent($section, $unrecordedStudent, 'aktif');
        $this->enrollStudent($section, $inactiveStudent, 'aktif');
        $this->enrollStudent($section, $droppedStudent, 'nonaktif');

        $session = $this->createAttendanceSession($section, ['status' => 'terbuka']);
        $session->opened_at = '2026-11-05 07:50:00';
        $session->opened_by = $lecturer->id;
        $session->save();

        AttendanceRecord::query()->create([
            'attendance_session_id' => $session->id,
            'student_id' => $recordedStudent->id,
            'method' => 'qr_code',
            'status' => 'hadir',
            'checked_in_at' => '2026-11-05 08:05:00',
        ]);

        $session->close($lecturer, Carbon::parse('2026-11-05 08:30:00', 'Asia/Jakarta'));

        // Unrecorded active student gets tidak_hadir
        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $session->id,
            'student_id' => $unrecordedStudent->id,
            'status' => 'tidak_hadir',
            'method' => 'manual',
        ]);

        // Recorded student maintains their status 'hadir'
        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $session->id,
            'student_id' => $recordedStudent->id,
            'status' => 'hadir',
            'method' => 'qr_code',
        ]);

        // Inactive and dropped students do not get records
        $this->assertDatabaseMissing('attendance_records', [
            'attendance_session_id' => $session->id,
            'student_id' => $inactiveStudent->id,
        ]);
        $this->assertDatabaseMissing('attendance_records', [
            'attendance_session_id' => $session->id,
            'student_id' => $droppedStudent->id,
        ]);
    }

    public function test_cancelling_session_with_empty_reason_is_rejected(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $session = $this->createAttendanceSession($section);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak boleh kosong');

        $session->cancel($lecturer, '   ');
    }

    public function test_cancelling_session_records_audit_fields_and_does_not_physically_delete_session(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $session = $this->createAttendanceSession($section);
        $cancelTime = Carbon::parse('2026-11-04 14:00:00', 'Asia/Jakarta');

        $session->cancel($lecturer, 'Dosen berhalangan karena tugas luar kota', $cancelTime);

        $session->refresh();
        $this->assertModelExists($session);
        $this->assertTrue($session->isCancelled());
        $this->assertSame($lecturer->id, $session->cancelled_by);
        $this->assertSame('Dosen berhalangan karena tugas luar kota', $session->cancellation_reason);
        $this->assertSame('2026-11-04 14:00:00', $session->cancelled_at->format('Y-m-d H:i:s'));
    }

    public function test_cancelling_closed_or_opened_session_is_rejected(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $closedSession = $this->createAttendanceSession($section, ['status' => 'ditutup']);

        try {
            $closedSession->cancel($lecturer, 'Alasan');
            $this->fail('Closed session should not be cancellable.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('sudah ditutup', $e->getMessage());
        }

        $openedSession = $this->createAttendanceSession($section, [
            'status' => 'terbuka',
        ]);
        $openedSession->opened_at = '2026-11-05 08:00:00';
        $openedSession->save();

        try {
            $openedSession->cancel($lecturer, 'Alasan');
            $this->fail('Opened/executed session should not be cancellable.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('dilaksanakan atau dibuka', $e->getMessage());
        }
    }

    public function test_cancelling_session_with_existing_attendance_records_is_rejected(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $student = User::factory()->create(['role' => UserRole::Mahasiswa->value]);
        $this->enrollStudent($section, $student);

        $session = $this->createAttendanceSession($section, ['status' => 'terjadwal']);

        AttendanceRecord::query()->create([
            'attendance_session_id' => $session->id,
            'student_id' => $student->id,
            'method' => 'manual',
            'status' => 'hadir',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('catatan presensi');

        $session->cancel($lecturer, 'Alasan');
    }

    public function test_unauthorized_user_cannot_cancel_session(): void
    {
        $section = $this->createCourseSection();
        $session = $this->createAttendanceSession($section);

        $unassignedLecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $student = User::factory()->create(['role' => UserRole::Mahasiswa->value]);

        try {
            $session->cancel($unassignedLecturer, 'Alasan');
            $this->fail('Unassigned lecturer should not cancel.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        try {
            $session->cancel($student, 'Alasan');
            $this->fail('Student should not cancel.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }
    }

    public function test_creating_valid_replacement_session_links_to_original_without_altering_original(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $originalSession = $this->createAttendanceSession($section, [
            'title' => 'Pertemuan 5 Asli',
            'cancellation_reason' => 'Dosen bertugas',
        ]);
        $originalSession->cancelled_at = '2026-11-04 10:00:00';
        $originalSession->save();

        $replacement = $originalSession->createReplacement($lecturer, [
            'session_type' => 'teori',
            'delivery_mode' => 'online',
            'title' => 'Pertemuan 5 Pengganti',
            'starts_at' => '2026-11-10 13:00:00',
            'ends_at' => '2026-11-10 15:00:00',
            'attendance_opens_at' => '2026-11-10 12:45:00',
            'attendance_closes_at' => '2026-11-10 15:15:00',
            'allowed_methods' => ['qr_code'],
            'replacement_category' => 'ats',
        ]);

        $this->assertSame('replacement', $replacement->session_kind);
        $this->assertSame($originalSession->id, $replacement->original_session_id);
        $this->assertSame('ats', $replacement->replacement_category);
        $this->assertSame('terjadwal', $replacement->status);

        $originalSession->refresh();
        $this->assertSame('Dosen bertugas', $originalSession->cancellation_reason);
        $this->assertSame($replacement->id, $originalSession->replacementSessions->first()->id);
    }

    public function test_creating_replacement_session_with_mismatched_course_section_is_rejected(): void
    {
        $sectionA = $this->createCourseSection('Kelas A');
        $sectionB = $this->createCourseSection('Kelas B');
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($sectionA, $lecturer);

        $originalSession = $this->createAttendanceSession($sectionA);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CourseSection sesi pengganti harus sama');

        $originalSession->createReplacement($lecturer, [
            'course_section_id' => $sectionB->id,
            'session_type' => 'teori',
            'delivery_mode' => 'online',
            'title' => 'Pertemuan Pengganti',
            'starts_at' => '2026-11-10 13:00:00',
            'ends_at' => '2026-11-10 15:00:00',
            'attendance_opens_at' => '2026-11-10 12:45:00',
            'attendance_closes_at' => '2026-11-10 15:15:00',
            'allowed_methods' => ['qr_code'],
        ]);
    }

    public function test_close_expired_sessions_automatically_closes_overdue_sessions_in_jakarta_timezone(): void
    {
        $section = $this->createCourseSection();
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $activeSession = $this->createAttendanceSession($section, [
            'status' => 'terbuka',
            'attendance_opens_at' => '2026-11-05 08:00:00',
            'attendance_closes_at' => '2026-11-05 10:00:00',
        ]);

        $expiredSession = $this->createAttendanceSession($section, [
            'status' => 'terbuka',
            'attendance_opens_at' => '2026-11-05 06:00:00',
            'attendance_closes_at' => '2026-11-05 08:00:00',
        ]);

        $scheduledSession = $this->createAttendanceSession($section, [
            'status' => 'terjadwal',
            'attendance_opens_at' => '2026-11-05 06:00:00',
            'attendance_closes_at' => '2026-11-05 08:00:00',
        ]);

        $checkTime = Carbon::parse('2026-11-05 08:30:00', 'Asia/Jakarta');

        $closed = AttendanceSession::closeExpiredSessions($checkTime, $admin);

        $this->assertCount(1, $closed);
        $this->assertSame($expiredSession->id, $closed->first()->id);

        $expiredSession->refresh();
        $this->assertSame('ditutup', $expiredSession->status);
        $this->assertSame($admin->id, $expiredSession->closed_by);
        $this->assertSame('2026-11-05 08:30:00', $expiredSession->closed_at->format('Y-m-d H:i:s'));

        $activeSession->refresh();
        $this->assertSame('terbuka', $activeSession->status);

        $scheduledSession->refresh();
        $this->assertSame('terjadwal', $scheduledSession->status);
    }

    public function test_manually_closed_session_cannot_be_reopened_directly_or_via_schedule(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $schedule = ClassSchedule::query()->create([
            'course_section_id' => $section->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'day_of_week' => 1, // Senin
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'timezone' => 'Asia/Jakarta',
            'location' => 'Lab 1',
            'is_active' => true,
        ]);

        // 2026-11-02 adalah hari Senin
        $openTime = Carbon::parse('2026-11-02 08:05:00', 'Asia/Jakarta');
        $session = AttendanceSession::openFromSchedule($schedule, $lecturer, [], $openTime);

        $closeTime = Carbon::parse('2026-11-02 09:45:00', 'Asia/Jakarta');
        $session->close($lecturer, $closeTime);

        $this->assertSame('ditutup', $session->fresh()->status);
        $this->assertSame($lecturer->id, $session->fresh()->closed_by);

        // 1. Mencoba buka kembali langsung via open()
        try {
            $session->open($lecturer, Carbon::parse('2026-11-02 09:50:00', 'Asia/Jakarta'));
            $this->fail('Manually closed session should not be reopenable via open().');
        } catch (DomainException $e) {
            $this->assertStringContainsString('sudah ditutup', $e->getMessage());
        }

        // 2. Mencoba buka kembali via openFromSchedule() pada jadwal dan tanggal yang sama
        try {
            AttendanceSession::openFromSchedule($schedule, $lecturer, [], Carbon::parse('2026-11-02 09:55:00', 'Asia/Jakarta'));
            $this->fail('Manually closed session should not be reopenable via openFromSchedule().');
        } catch (DomainException $e) {
            $this->assertStringContainsString('sudah ditutup', $e->getMessage());
        }

        $this->assertSame('ditutup', $session->fresh()->status);
        $this->assertDatabaseCount('attendance_sessions', 1);
    }

    public function test_updating_class_schedule_does_not_alter_historical_sessions_or_attendance_records(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $studentQr = User::factory()->create(['role' => UserRole::Mahasiswa->value]);
        $studentFace = User::factory()->create(['role' => UserRole::Mahasiswa->value]);
        $this->enrollStudent($section, $studentQr);
        $this->enrollStudent($section, $studentFace);

        $schedule = ClassSchedule::query()->create([
            'course_section_id' => $section->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'day_of_week' => 1, // Senin
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'timezone' => 'Asia/Jakarta',
            'location' => 'Ruang 201',
            'is_active' => true,
        ]);

        $openTime = Carbon::parse('2026-11-02 08:10:00', 'Asia/Jakarta');
        $session = AttendanceSession::openFromSchedule($schedule, $lecturer, [], $openTime);

        AttendanceRecord::query()->create([
            'attendance_session_id' => $session->id,
            'student_id' => $studentQr->id,
            'method' => 'qr_code',
            'status' => 'hadir',
            'checked_in_at' => '2026-11-02 08:12:00',
        ]);
        AttendanceRecord::query()->create([
            'attendance_session_id' => $session->id,
            'student_id' => $studentFace->id,
            'method' => 'face_recognition',
            'status' => 'terlambat',
            'checked_in_at' => '2026-11-02 08:30:00',
        ]);

        $session->close($lecturer, Carbon::parse('2026-11-02 10:00:00', 'Asia/Jakarta'));

        // Admin mengubah jadwal kuliah (hari, jam mulai, jam selesai, tipe sesi, dan mode)
        $schedule->update([
            'day_of_week' => 4, // Kamis
            'starts_at' => '13:00',
            'ends_at' => '15:30',
            'session_type' => 'praktikum',
            'delivery_mode' => 'online',
            'location' => null,
            'meeting_url' => 'https://meet.example.com/iot',
        ]);

        // Pastikan sesi historis dan riwayat absensi sama sekali tidak berubah
        $session->refresh();
        $this->assertSame('2026-11-02 08:00:00', $session->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-02 10:00:00', $session->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame('teori', $session->session_type);
        $this->assertSame('onsite', $session->delivery_mode);
        $this->assertSame('ditutup', $session->status);

        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $session->id,
            'student_id' => $studentQr->id,
            'method' => 'qr_code',
            'status' => 'hadir',
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $session->id,
            'student_id' => $studentFace->id,
            'method' => 'face_recognition',
            'status' => 'terlambat',
        ]);
    }

    private function createCourseSection(string $name = 'Kelas A'): CourseSection
    {
        $course = Course::query()->create([
            'code' => 'CS'.fake()->unique()->numerify('###'),
            'name' => 'Mata Kuliah '.fake()->unique()->numerify('###'),
        ]);

        $period = AcademicPeriod::query()->create([
            'name' => 'Semester Ganjil 2026/2027',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'starts_on' => '2026-08-01',
            'ends_on' => '2027-01-31',
            'ats_starts_on' => '2026-10-05',
            'ats_ends_on' => '2026-10-16',
            'uas_starts_on' => '2027-01-11',
            'uas_ends_on' => '2027-01-22',
            'ats_replacement_starts_on' => '2026-11-01',
            'ats_replacement_ends_on' => '2026-11-15',
            'uas_replacement_starts_on' => '2027-01-25',
            'uas_replacement_ends_on' => '2027-01-30',
            'replacement_deadline' => '2027-01-30',
            'is_active' => true,
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

    /**
     * @param  array<string, mixed>  $attributes
     */
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

    private function enrollStudent(CourseSection $section, User $student, string $status = 'aktif'): Enrollment
    {
        return Enrollment::query()->create([
            'course_section_id' => $section->id,
            'student_id' => $student->id,
            'status' => $status,
        ]);
    }
}
