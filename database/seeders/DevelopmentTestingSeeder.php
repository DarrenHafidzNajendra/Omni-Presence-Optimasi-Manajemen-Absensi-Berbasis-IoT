<?php

namespace Database\Seeders;

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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevelopmentTestingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeder ini didesain idempotent (dapat dijalankan berulang kali tanpa duplikasi data)
     * menggunakan metode firstOrCreate dan updateOrCreate dengan kunci identitas unik.
     */
    public function run(): void
    {
        $defaultPassword = Hash::make('password123');

        // =========================================================================
        // 1. PENGGUNA (ADMIN, DOSEN, MAHASISWA)
        // =========================================================================

        // 1.1 Akun Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@omnipresence.local'],
            [
                'name' => 'Administrator Omni-Presence',
                'password' => $defaultPassword,
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // 1.2 Akun Dosen (Password default = NIDN)
        // Dosen 1: Dr. Budi Santoso (mengampu Kelas A & B Pemrograman Web)
        $dosenBudi = User::firstOrCreate(
            ['nidn' => '0001018501'],
            [
                'name' => 'Dr. Budi Santoso, M.Kom.',
                'email' => 'budi.santoso@omnipresence.local',
                'password' => Hash::make('0001018501'),
                'role' => 'dosen',
                'is_active' => true,
                'must_change_password' => true,
            ]
        );

        // Dosen 2: Dewi Lestari (mengampu Kelas A Dasar Pemrograman)
        $dosenDewi = User::firstOrCreate(
            ['nidn' => '0002028802'],
            [
                'name' => 'Dewi Lestari, S.T., M.T.',
                'email' => 'dewi.lestari@omnipresence.local',
                'password' => Hash::make('0002028802'),
                'role' => 'dosen',
                'is_active' => true,
                'must_change_password' => true,
            ]
        );

        // Dosen 3: Fajar Pratama (tanpa kelas, untuk verifikasi empty state "Kelas Saya")
        $dosenFajar = User::firstOrCreate(
            ['nidn' => '0003039003'],
            [
                'name' => 'Fajar Pratama, M.Cs.',
                'email' => 'fajar.pratama@omnipresence.local',
                'password' => Hash::make('0003039003'),
                'role' => 'dosen',
                'is_active' => true,
                'must_change_password' => true,
            ]
        );

        // 1.3 Akun Mahasiswa (12 Mahasiswa, Password default = NIM)
        $studentsData = [
            ['nim' => '3312501001', 'name' => 'Ahmad Fauzi', 'active' => true],
            ['nim' => '3312501002', 'name' => 'Bella Safitri', 'active' => true],
            ['nim' => '3312501003', 'name' => 'Candra Wijaya', 'active' => true],
            ['nim' => '3312501004', 'name' => 'Dina Mariana', 'active' => true],
            ['nim' => '3312501005', 'name' => 'Eko Prasetyo', 'active' => true],
            ['nim' => '3312501006', 'name' => 'Fitri Handayani', 'active' => true],
            ['nim' => '3312501007', 'name' => 'Gilang Ramadhan', 'active' => true],
            ['nim' => '3312501008', 'name' => 'Hani Puspitasari', 'active' => true],
            ['nim' => '3312501009', 'name' => 'Indra Gunawan', 'active' => true],
            ['nim' => '3312501010', 'name' => 'Joko Susilo', 'active' => true],
            ['nim' => '3312501011', 'name' => 'Kiki Amalia', 'active' => true], // Terdaftar namun enrollment nonaktif
            ['nim' => '3312501012', 'name' => 'Lutfi Hakim', 'active' => false], // Mahasiswa status nonaktif di master user
        ];

        $students = [];
        foreach ($studentsData as $st) {
            $students[$st['nim']] = User::firstOrCreate(
                ['nim' => $st['nim']],
                [
                    'name' => $st['name'],
                    'password' => Hash::make($st['nim']),
                    'role' => 'mahasiswa',
                    'is_active' => $st['active'],
                    'must_change_password' => true,
                ]
            );
        }

        // =========================================================================
        // 2. PERIODE AKADEMIK
        // =========================================================================
        // Mengikuti aturan bisnis final:
        // Minggu pengganti ATS: 28 Sep - 02 Okt 2026 (Senin-Jumat sebelum ATS)
        // ATS: 05 Okt - 16 Okt 2026
        // Minggu pengganti UAS: 04 Jan - 08 Jan 2027 (Senin-Jumat sebelum UAS)
        // UAS: 11 Jan - 22 Jan 2027
        $academicPeriod = AcademicPeriod::firstOrCreate(
            ['name' => 'Semester Ganjil 2026/2027'],
            [
                'academic_year' => '2026/2027',
                'semester' => 'ganjil',
                'starts_on' => '2026-08-01',
                'ends_on' => '2027-01-31',
                'ats_replacement_starts_on' => '2026-09-28',
                'ats_replacement_ends_on' => '2026-10-02',
                'ats_starts_on' => '2026-10-05',
                'ats_ends_on' => '2026-10-16',
                'uas_replacement_starts_on' => '2027-01-04',
                'uas_replacement_ends_on' => '2027-01-08',
                'uas_starts_on' => '2027-01-11',
                'uas_ends_on' => '2027-01-22',
                'replacement_deadline' => '2027-01-29',
                'is_active' => true,
            ]
        );

        // =========================================================================
        // 3. MATA KULIAH & KELAS MATA KULIAH
        // =========================================================================
        $courseWeb = Course::firstOrCreate(
            ['code' => 'IF101'],
            [
                'name' => 'Pemrograman Web',
                'description' => 'Mata kuliah dasar pengembangan aplikasi web responsif dan modern.',
                'is_active' => true,
            ]
        );

        $courseDaspro = Course::firstOrCreate(
            ['code' => 'IF102'],
            [
                'name' => 'Dasar Pemrograman',
                'description' => 'Logika pemrograman dan algoritma dasar.',
                'is_active' => true,
            ]
        );

        // 3.1 Kelas Utama Pengujian: IF101 - Kelas A (Diampu Dr. Budi)
        $sectionWebA = CourseSection::firstOrCreate(
            [
                'course_id' => $courseWeb->id,
                'name' => 'Kelas A',
                'academic_year' => '2026/2027',
                'semester' => 'ganjil',
            ],
            [
                'academic_period_id' => $academicPeriod->id,
                'is_active' => true,
            ]
        );

        // 3.2 Kelas Kedua: IF101 - Kelas B (Diampu Dr. Budi, kelas kosong tanpa mahasiswa)
        $sectionWebB = CourseSection::firstOrCreate(
            [
                'course_id' => $courseWeb->id,
                'name' => 'Kelas B',
                'academic_year' => '2026/2027',
                'semester' => 'ganjil',
            ],
            [
                'academic_period_id' => $academicPeriod->id,
                'is_active' => true,
            ]
        );

        // 3.3 Kelas Ketiga: IF102 - Kelas A (Diampu Dewi Lestari)
        $sectionDasproA = CourseSection::firstOrCreate(
            [
                'course_id' => $courseDaspro->id,
                'name' => 'Kelas A',
                'academic_year' => '2026/2027',
                'semester' => 'ganjil',
            ],
            [
                'academic_period_id' => $academicPeriod->id,
                'is_active' => true,
            ]
        );

        // =========================================================================
        // 4. PENUGASAN DOSEN
        // =========================================================================
        // Dr. Budi -> Kelas Web A & B
        CourseSectionLecturer::firstOrCreate([
            'course_section_id' => $sectionWebA->id,
            'user_id' => $dosenBudi->id,
            'teaching_type' => 'teori',
        ]);
        CourseSectionLecturer::firstOrCreate([
            'course_section_id' => $sectionWebB->id,
            'user_id' => $dosenBudi->id,
            'teaching_type' => 'teori',
        ]);

        // Dewi Lestari -> Kelas Daspro A
        CourseSectionLecturer::firstOrCreate([
            'course_section_id' => $sectionDasproA->id,
            'user_id' => $dosenDewi->id,
            'teaching_type' => 'teori',
        ]);

        // =========================================================================
        // 5. JADWAL PERKULIAHAN
        // =========================================================================
        // Jadwal Web A: Hari Selasa (2), 08:00 - 10:00
        $scheduleWebA = ClassSchedule::firstOrCreate(
            [
                'course_section_id' => $sectionWebA->id,
                'day_of_week' => 2,
                'starts_at' => '08:00:00',
            ],
            [
                'session_type' => 'teori',
                'delivery_mode' => 'onsite',
                'ends_at' => '10:00:00',
                'timezone' => 'Asia/Jakarta',
                'location' => 'Laboratorium Komputer 3',
                'valid_from' => '2026-08-01',
                'valid_until' => '2027-01-31',
                'is_active' => true,
            ]
        );

        // Jadwal Daspro A: Hari Kamis (4), 13:00 - 15:00
        ClassSchedule::firstOrCreate(
            [
                'course_section_id' => $sectionDasproA->id,
                'day_of_week' => 4,
                'starts_at' => '13:00:00',
            ],
            [
                'session_type' => 'teori',
                'delivery_mode' => 'onsite',
                'ends_at' => '15:00:00',
                'timezone' => 'Asia/Jakarta',
                'location' => 'Ruang Teori 201',
                'valid_from' => '2026-08-01',
                'valid_until' => '2027-01-31',
                'is_active' => true,
            ]
        );

        // =========================================================================
        // 6. ENROLLMENT MAHASISWA (KELAS WEB A)
        // =========================================================================
        // Mahasiswa 1 s/d 10: Enrollment aktif & User aktif (Total 10 Mahasiswa Aktif Rekap)
        for ($i = 1; $i <= 10; $i++) {
            $nim = sprintf('33125010%02d', $i);
            Enrollment::firstOrCreate([
                'course_section_id' => $sectionWebA->id,
                'student_id' => $students[$nim]->id,
            ], [
                'status' => 'aktif',
            ]);
        }

        // Mahasiswa 11: Terdaftar namun status enrollment nonaktif
        Enrollment::firstOrCreate([
            'course_section_id' => $sectionWebA->id,
            'student_id' => $students['3312501011']->id,
        ], [
            'status' => 'nonaktif',
        ]);

        // Mahasiswa 12: Terdaftar aktif namun user is_active = false
        Enrollment::firstOrCreate([
            'course_section_id' => $sectionWebA->id,
            'student_id' => $students['3312501012']->id,
        ], [
            'status' => 'aktif',
        ]);

        // =========================================================================
        // 7. SESI ABSENSI & CATATAN KEHADIRAN (SKENARIO DETERMINISTIK UNTUK REKAP)
        // =========================================================================
        //
        // Skenario Pertemuan pada Kelas Web A:
        // - Total Sesi Terlaksana yang Memenuhi Kriteria (Penyebut / Denominator) = 3 Sesi:
        //     1. Sesi 1 (Reguler, Ditutup): 2026-09-08
        //     2. Sesi 2 (Reguler, Ditutup): 2026-09-15
        //     3. Sesi 4 (Pengganti ATS, Ditutup): 2026-09-29 (Menggantikan Sesi 3)
        //
        // Sesi yang Dikecualikan dari Penyebut:
        //     - Sesi 3 (Reguler, Dibatalkan & Digantikan oleh Sesi 4): Tidak dihitung ganda.
        //     - Sesi 5 (Reguler, Terbuka hari ini): Belum ditutup.
        //     - Sesi 6 (Reguler, Terjadwal di masa depan): Belum terlaksana.

        // --- Sesi 1: Reguler Ditutup (2026-09-08) ---
        $session1 = AttendanceSession::firstOrCreate(
            [
                'course_section_id' => $sectionWebA->id,
                'title' => 'Pertemuan 1 - Pengenalan HTML & CSS',
            ],
            [
                'class_schedule_id' => $scheduleWebA->id,
                'session_type' => 'teori',
                'delivery_mode' => 'onsite',
                'starts_at' => '2026-09-08 08:00:00',
                'ends_at' => '2026-09-08 10:00:00',
                'attendance_opens_at' => '2026-09-08 07:45:00',
                'attendance_closes_at' => '2026-09-08 10:15:00',
                'allowed_methods' => ['qr_code', 'manual'],
                'status' => 'ditutup',
                'opened_at' => '2026-09-08 07:50:00',
                'opened_by' => $dosenBudi->id,
                'closed_at' => '2026-09-08 10:15:00',
                'closed_by' => $dosenBudi->id,
            ]
        );

        // Catatan Sesi 1: Seluruh 10 mahasiswa aktif hadir tepat waktu ('hadir')
        for ($i = 1; $i <= 10; $i++) {
            $nim = sprintf('33125010%02d', $i);
            AttendanceRecord::firstOrCreate(
                [
                    'attendance_session_id' => $session1->id,
                    'student_id' => $students[$nim]->id,
                ],
                [
                    'method' => 'manual',
                    'status' => 'hadir',
                    'checked_in_at' => '2026-09-08 07:55:00',
                    'recorded_by' => $dosenBudi->id,
                ]
            );
        }

        // --- Sesi 2: Reguler Ditutup (2026-09-15) dengan Variasi Status Lengkap ---
        $session2 = AttendanceSession::firstOrCreate(
            [
                'course_section_id' => $sectionWebA->id,
                'title' => 'Pertemuan 2 - Layouting Responsif & Flexbox',
            ],
            [
                'class_schedule_id' => $scheduleWebA->id,
                'session_type' => 'teori',
                'delivery_mode' => 'onsite',
                'starts_at' => '2026-09-15 08:00:00',
                'ends_at' => '2026-09-15 10:00:00',
                'attendance_opens_at' => '2026-09-15 07:45:00',
                'attendance_closes_at' => '2026-09-15 10:15:00',
                'allowed_methods' => ['qr_code', 'manual'],
                'status' => 'ditutup',
                'opened_at' => '2026-09-15 07:50:00',
                'opened_by' => $dosenBudi->id,
                'closed_at' => '2026-09-15 10:15:00',
                'closed_by' => $dosenBudi->id,
            ]
        );

        // Catatan Sesi 2: Variasi deterministik untuk 10 mahasiswa aktif:
        // Mahasiswa 1: 'hadir'
        // Mahasiswa 2: 'terlambat' (dihitung hadir dalam persentase)
        // Mahasiswa 3: 'izin'
        // Mahasiswa 4: 'sakit'
        // Mahasiswa 5: 'tidak_hadir'
        // Mahasiswa 6 s/d 10: Tidak memiliki record (belum dicatat)
        $session2Records = [
            '3312501001' => ['status' => 'hadir', 'checked_in_at' => '2026-09-15 07:55:00', 'notes' => null],
            '3312501002' => ['status' => 'terlambat', 'checked_in_at' => '2026-09-15 08:25:00', 'notes' => 'Terlambat 25 menit'],
            '3312501003' => ['status' => 'izin', 'checked_in_at' => null, 'notes' => 'Surat izin dinas ormawa'],
            '3312501004' => ['status' => 'sakit', 'checked_in_at' => null, 'notes' => 'Surat keterangan dokter terlampir'],
            '3312501005' => ['status' => 'tidak_hadir', 'checked_in_at' => null, 'notes' => 'Tanpa keterangan'],
        ];

        foreach ($session2Records as $nim => $recData) {
            AttendanceRecord::firstOrCreate(
                [
                    'attendance_session_id' => $session2->id,
                    'student_id' => $students[$nim]->id,
                ],
                [
                    'method' => 'manual',
                    'status' => $recData['status'],
                    'checked_in_at' => $recData['checked_in_at'],
                    'notes' => $recData['notes'],
                    'recorded_by' => $dosenBudi->id,
                ]
            );
        }

        // --- Sesi 3: Reguler Dibatalkan (2026-09-22) ---
        $session3 = AttendanceSession::firstOrCreate(
            [
                'course_section_id' => $sectionWebA->id,
                'title' => 'Pertemuan 3 - JavaScript Dasar & DOM',
            ],
            [
                'class_schedule_id' => $scheduleWebA->id,
                'session_type' => 'teori',
                'delivery_mode' => 'onsite',
                'starts_at' => '2026-09-22 08:00:00',
                'ends_at' => '2026-09-22 10:00:00',
                'attendance_opens_at' => '2026-09-22 07:45:00',
                'attendance_closes_at' => '2026-09-22 10:15:00',
                'allowed_methods' => ['qr_code'],
                'status' => 'terjadwal',
                'cancelled_at' => '2026-09-21 20:00:00',
                'cancelled_by' => $dosenBudi->id,
                'cancellation_reason' => 'Dosen bertugas menghadiri seminar internasional',
            ]
        );

        // --- Sesi 4: Sesi Pengganti ATS Ditutup (2026-09-29) Menggantikan Sesi 3 ---
        // Tanggal 29 Sep 2026 berada dalam rentang minggu pengganti ATS (28 Sep - 02 Okt 2026)
        $session4 = AttendanceSession::firstOrCreate(
            [
                'course_section_id' => $sectionWebA->id,
                'title' => 'Pertemuan 3 (Pengganti) - JavaScript Dasar & DOM',
            ],
            [
                'session_type' => 'teori',
                'delivery_mode' => 'onsite',
                'session_kind' => 'replacement',
                'replacement_category' => 'pengganti_ats',
                'original_session_id' => $session3->id,
                'starts_at' => '2026-09-29 08:00:00',
                'ends_at' => '2026-09-29 10:00:00',
                'attendance_opens_at' => '2026-09-29 07:45:00',
                'attendance_closes_at' => '2026-09-29 10:15:00',
                'allowed_methods' => ['qr_code', 'manual'],
                'status' => 'ditutup',
                'opened_at' => '2026-09-29 07:50:00',
                'opened_by' => $dosenBudi->id,
                'closed_at' => '2026-09-29 10:15:00',
                'closed_by' => $dosenBudi->id,
            ]
        );

        // Catatan Sesi 4: Mahasiswa 1 s/d 8 hadir, Mahasiswa 9 izin, Mahasiswa 10 sakit
        for ($i = 1; $i <= 8; $i++) {
            $nim = sprintf('33125010%02d', $i);
            AttendanceRecord::firstOrCreate(
                [
                    'attendance_session_id' => $session4->id,
                    'student_id' => $students[$nim]->id,
                ],
                [
                    'method' => 'manual',
                    'status' => 'hadir',
                    'checked_in_at' => '2026-09-29 07:55:00',
                    'recorded_by' => $dosenBudi->id,
                ]
            );
        }
        AttendanceRecord::firstOrCreate(
            [
                'attendance_session_id' => $session4->id,
                'student_id' => $students['3312501009']->id,
            ],
            [
                'method' => 'manual',
                'status' => 'izin',
                'notes' => 'Izin keperluan keluarga',
                'recorded_by' => $dosenBudi->id,
            ]
        );
        AttendanceRecord::firstOrCreate(
            [
                'attendance_session_id' => $session4->id,
                'student_id' => $students['3312501010']->id,
            ],
            [
                'method' => 'manual',
                'status' => 'sakit',
                'notes' => 'Demam berdarah',
                'recorded_by' => $dosenBudi->id,
            ]
        );

        // --- Sesi 5: Reguler Terbuka (Hari Ini) untuk Interaksi Live Dosen ---
        $now = Carbon::now('Asia/Jakarta');
        AttendanceSession::firstOrCreate(
            [
                'course_section_id' => $sectionWebA->id,
                'title' => 'Pertemuan 4 - Modern CSS Framework & Tailwind',
            ],
            [
                'class_schedule_id' => $scheduleWebA->id,
                'session_type' => 'teori',
                'delivery_mode' => 'onsite',
                'starts_at' => $now->copy()->setTime(8, 0, 0),
                'ends_at' => $now->copy()->setTime(10, 0, 0),
                'attendance_opens_at' => $now->copy()->setTime(7, 45, 0),
                'attendance_closes_at' => $now->copy()->setTime(10, 15, 0),
                'allowed_methods' => ['qr_code', 'face_recognition', 'manual'],
                'status' => 'terbuka',
                'opened_at' => $now->copy()->setTime(7, 50, 0),
                'opened_by' => $dosenBudi->id,
            ]
        );

        // --- Sesi 6: Reguler Terjadwal di Masa Depan (Minggu Depan) ---
        $nextWeek = Carbon::now('Asia/Jakarta')->addWeek();
        AttendanceSession::firstOrCreate(
            [
                'course_section_id' => $sectionWebA->id,
                'title' => 'Pertemuan 5 - Integrasi REST API & Asynchronous Fetch',
            ],
            [
                'class_schedule_id' => $scheduleWebA->id,
                'session_type' => 'teori',
                'delivery_mode' => 'onsite',
                'starts_at' => $nextWeek->copy()->setTime(8, 0, 0),
                'ends_at' => $nextWeek->copy()->setTime(10, 0, 0),
                'attendance_opens_at' => $nextWeek->copy()->setTime(7, 45, 0),
                'attendance_closes_at' => $nextWeek->copy()->setTime(10, 15, 0),
                'allowed_methods' => ['qr_code', 'manual'],
                'status' => 'terjadwal',
            ]
        );
    }
}
