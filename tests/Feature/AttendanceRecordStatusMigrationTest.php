<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttendanceRecordStatusMigrationTest extends TestCase
{
    public function test_migration_adds_tidak_hadir_to_attendance_records_status_enum(): void
    {
        $connectionName = 'attendance_record_status_migration_test';
        $connectionConfigKey = "database.connections.{$connectionName}";
        $originalDefaultConnection = DB::getDefaultConnection();
        $originalConnectionConfiguration = config($connectionConfigKey);

        try {
            $sqliteConfiguration = config('database.connections.sqlite');
            $sqliteConfiguration['url'] = null;
            $sqliteConfiguration['database'] = ':memory:';
            $sqliteConfiguration['foreign_key_constraints'] = true;
            config()->set($connectionConfigKey, $sqliteConfiguration);

            DB::purge($connectionName);
            $connection = DB::connection($connectionName);

            Schema::connection($connectionName)->create('users', function (Blueprint $table): void {
                $table->id();
            });

            DB::setDefaultConnection($connectionName);

            $baseMigrations = [
                '2026_10_26_161011_create_courses_table.php',
                '2026_10_26_161525_create_course_sections_table.php',
                '2026_10_26_161821_create_enrollments_table.php',
                '2026_10_26_161928_create_class_schedules_table.php',
                '2026_10_26_162217_create_attendance_sessions_table.php',
                '2026_10_26_162352_create_attendance_records_table.php',
            ];

            foreach ($baseMigrations as $file) {
                $migration = require database_path("migrations/{$file}");
                $migration->up();
            }

            $connection->table('users')->insert(['id' => 1]);
            $connection->table('courses')->insert(['id' => 1, 'code' => 'CS101', 'name' => 'Dasar Pemrograman']);
            $connection->table('course_sections')->insert([
                'id' => 1,
                'course_id' => 1,
                'name' => 'A',
                'academic_year' => '2026/2027',
                'semester' => 'ganjil',
                'is_active' => true,
            ]);
            $connection->table('attendance_sessions')->insert([
                'id' => 1,
                'course_section_id' => 1,
                'session_type' => 'teori',
                'delivery_mode' => 'onsite',
                'title' => 'Pertemuan 1',
                'starts_at' => '2026-11-05 08:00:00',
                'ends_at' => '2026-11-05 10:00:00',
                'attendance_opens_at' => '2026-11-05 07:45:00',
                'attendance_closes_at' => '2026-11-05 10:15:00',
                'allowed_methods' => '["qr_code"]',
                'status' => 'ditutup',
            ]);

            // Seed existing statuses
            $connection->table('attendance_records')->insert([
                'attendance_session_id' => 1,
                'student_id' => 1,
                'method' => 'manual',
                'status' => 'hadir',
            ]);

            $migration = require database_path('migrations/2026_10_28_143000_add_tidak_hadir_to_attendance_records_status_enum.php');
            $migration->up();

            // Insert new status: tidak_hadir
            $connection->table('users')->insert(['id' => 2]);
            $connection->table('attendance_records')->insert([
                'attendance_session_id' => 1,
                'student_id' => 2,
                'method' => 'manual',
                'status' => 'tidak_hadir',
            ]);

            $this->assertSame(2, $connection->table('attendance_records')->count());
            $this->assertSame(
                'hadir',
                $connection->table('attendance_records')->where('student_id', 1)->value('status')
            );
            $this->assertSame(
                'tidak_hadir',
                $connection->table('attendance_records')->where('student_id', 2)->value('status')
            );

            // Run migration for adding 'terlambat'
            $terlambatMigration = require database_path('migrations/2026_10_28_150000_add_terlambat_to_attendance_records_status_enum.php');
            $terlambatMigration->up();

            // Insert new status: terlambat
            $connection->table('users')->insert(['id' => 3]);
            $connection->table('attendance_records')->insert([
                'attendance_session_id' => 1,
                'student_id' => 3,
                'method' => 'manual',
                'status' => 'terlambat',
            ]);

            $this->assertSame(3, $connection->table('attendance_records')->count());
            $this->assertSame(
                'terlambat',
                $connection->table('attendance_records')->where('student_id', 3)->value('status')
            );

            // Rollback terlambat migration
            $connection->table('attendance_records')->where('student_id', 3)->delete();
            $terlambatMigration->down();

            // Verify rollback capability
            $connection->table('attendance_records')->where('student_id', 2)->delete();
            $migration->down();

            $this->assertSame(1, $connection->table('attendance_records')->count());
        } finally {
            DB::setDefaultConnection($originalDefaultConnection);
            DB::purge($connectionName);
            config()->set($connectionConfigKey, $originalConnectionConfiguration);
        }
    }
}
