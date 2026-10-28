<?php

namespace Tests\Feature;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttendanceHistoryProtectionTest extends TestCase
{
    public function test_attendance_history_foreign_keys_reject_deletions_that_would_remove_history(): void
    {
        $connectionName = 'attendance_history_protection_test';
        $connectionConfigKey = "database.connections.{$connectionName}";
        $originalDefaultConnection = DB::getDefaultConnection();
        $originalConnectionConfiguration = config($connectionConfigKey);
        $migrations = [
            '2026_10_26_161011_create_courses_table.php',
            '2026_10_26_161525_create_course_sections_table.php',
            '2026_10_26_161821_create_enrollments_table.php',
            '2026_10_26_161928_create_class_schedules_table.php',
            '2026_10_26_162217_create_attendance_sessions_table.php',
            '2026_10_26_162352_create_attendance_records_table.php',
            '2026_10_27_170505_protect_attendance_history_from_cascading_deletes.php',
        ];
        try {
            $sqliteConfiguration = config('database.connections.sqlite');
            $sqliteConfiguration['url'] = null;
            $sqliteConfiguration['database'] = ':memory:';
            $sqliteConfiguration['foreign_key_constraints'] = true;
            config()->set($connectionConfigKey, $sqliteConfiguration);

            DB::purge($connectionName);
            $connection = DB::connection($connectionName);
            $connection->statement('PRAGMA foreign_keys = ON');
            Schema::connection($connectionName)->create('users', function (Blueprint $table): void {
                $table->id();
            });
            DB::setDefaultConnection($connectionName);

            foreach ($migrations as $migrationFile) {
                $migration = require database_path("migrations/{$migrationFile}");
                $migration->up();
                $executedMigrations[] = $migration;
            }

            $this->seedReferencedAttendanceData($connection);

            $deletionWasRejected = false;

            try {
                $connection->table('attendance_sessions')->where('id', 1)->delete();
            } catch (QueryException) {
                $deletionWasRejected = true;
            }

            $this->assertTrue($deletionWasRejected);
            $this->assertSame(1, $connection->table('attendance_records')->count());
        } finally {
            DB::setDefaultConnection($originalDefaultConnection);
            DB::purge($connectionName);
            config()->set($connectionConfigKey, $originalConnectionConfiguration);
        }
    }

    public function test_each_protected_parent_deletion_is_rejected_without_removing_attendance_record(): void
    {
        $connectionName = 'attendance_history_protection_parent_test';
        $connectionConfigKey = "database.connections.{$connectionName}";
        $originalDefaultConnection = DB::getDefaultConnection();
        $originalConnectionConfiguration = config($connectionConfigKey);
        $migrationFiles = [
            '2026_10_26_161011_create_courses_table.php',
            '2026_10_26_161525_create_course_sections_table.php',
            '2026_10_26_161821_create_enrollments_table.php',
            '2026_10_26_161928_create_class_schedules_table.php',
            '2026_10_26_162217_create_attendance_sessions_table.php',
            '2026_10_26_162352_create_attendance_records_table.php',
            '2026_10_27_170505_protect_attendance_history_from_cascading_deletes.php',
        ];
        try {
            $sqliteConfiguration = config('database.connections.sqlite');
            $sqliteConfiguration['url'] = null;
            $sqliteConfiguration['database'] = ':memory:';
            $sqliteConfiguration['foreign_key_constraints'] = true;
            config()->set($connectionConfigKey, $sqliteConfiguration);
            DB::purge($connectionName);
            $connection = DB::connection($connectionName);
            $connection->statement('PRAGMA foreign_keys = ON');
            Schema::connection($connectionName)->create('users', function (Blueprint $table): void {
                $table->id();
            });
            DB::setDefaultConnection($connectionName);

            foreach ($migrationFiles as $migrationFile) {
                $migration = require database_path("migrations/{$migrationFile}");
                $migration->up();
            }

            $this->seedReferencedAttendanceData($connection);

            foreach ([
                ['users', 2],
                ['course_sections', 1],
            ] as [$table, $id]) {
                try {
                    $connection->table($table)->where('id', $id)->delete();
                    $this->fail("Deleting {$table} should be rejected.");
                } catch (QueryException) {
                    $this->assertSame(1, $connection->table('attendance_records')->count());
                }
            }
        } finally {
            DB::setDefaultConnection($originalDefaultConnection);
            DB::purge($connectionName);
            config()->set($connectionConfigKey, $originalConnectionConfiguration);
        }
    }

    private function seedReferencedAttendanceData(Connection $connection): void
    {
        $connection->table('users')->insert([
            ['id' => 1],
            ['id' => 2],
        ]);
        $connection->table('courses')->insert([
            ['id' => 1, 'code' => 'AUD001', 'name' => 'Audit'],
        ]);
        $connection->table('course_sections')->insert([
            [
                'id' => 1,
                'course_id' => 1,
                'name' => 'A',
                'academic_year' => '2026/2027',
                'semester' => 'ganjil',
                'is_active' => true,
            ],
        ]);
        $connection->table('attendance_sessions')->insert([
            [
                'id' => 1,
                'course_section_id' => 1,
                'session_type' => 'teori',
                'delivery_mode' => 'onsite',
                'title' => 'Pertemuan Audit',
                'starts_at' => '2026-10-27 08:00:00',
                'ends_at' => '2026-10-27 10:00:00',
                'attendance_opens_at' => '2026-10-27 07:45:00',
                'attendance_closes_at' => '2026-10-27 10:15:00',
                'allowed_methods' => '["manual"]',
                'status' => 'ditutup',
            ],
        ]);
        $connection->table('attendance_records')->insert([
            [
                'id' => 1,
                'attendance_session_id' => 1,
                'student_id' => 2,
                'method' => 'manual',
                'status' => 'hadir',
            ],
        ]);
    }
}
