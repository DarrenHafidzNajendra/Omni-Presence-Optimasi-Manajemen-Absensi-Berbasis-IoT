<?php

namespace Tests\Feature;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttendanceSessionLifecycleMigrationTest extends TestCase
{
    public function test_lifecycle_migration_preserves_legacy_rows_and_adds_nullable_fields(): void
    {
        [$connectionName, $connectionConfigKey, $originalDefaultConnection, $originalConfiguration] =
            $this->prepareConnection('attendance_session_lifecycle_test');

        try {
            $connection = DB::connection($connectionName);
            $this->runBaseMigrations($connectionName);
            $this->seedCourseSection($connection);

            $connection->table('attendance_sessions')->insert($this->sessionPayload(1));

            $protectionMigration = require database_path(
                'migrations/2026_10_27_170505_protect_attendance_history_from_cascading_deletes.php'
            );
            $protectionMigration->up();

            $lifecycleMigration = require database_path(
                'migrations/2026_10_27_175222_add_attendance_session_lifecycle_fields.php'
            );
            $lifecycleMigration->up();

            $this->assertSame(1, $connection->table('attendance_sessions')->count());
            $this->assertNullableColumn($connection, 'session_kind');
            $this->assertNullableColumn($connection, 'original_session_id');
            $this->assertNullableColumn($connection, 'replacement_category');
            $this->assertNullableColumn($connection, 'cancelled_at');
            $this->assertNullableColumn($connection, 'cancelled_by');
            $this->assertNullableColumn($connection, 'cancellation_reason');
            $this->assertNullableColumn($connection, 'opened_at');
            $this->assertNullableColumn($connection, 'closed_at');
            $this->assertNullableColumn($connection, 'closed_by');
            $this->assertNullableColumn($connection, 'late_after_minutes');
            $this->assertColumnHasNoDefault($connection, 'late_after_minutes', ['10', '15']);

            $this->assertForeignKey(
                $connection,
                'attendance_sessions',
                'original_session_id',
                'attendance_sessions',
                'id',
                'RESTRICT'
            );
            $this->assertForeignKey(
                $connection,
                'attendance_sessions',
                'cancelled_by',
                'users',
                'id',
                'SET NULL'
            );
            $this->assertForeignKey(
                $connection,
                'attendance_sessions',
                'closed_by',
                'users',
                'id',
                'SET NULL'
            );
            $this->assertForeignKey(
                $connection,
                'attendance_records',
                'attendance_session_id',
                'attendance_sessions',
                'id',
                'RESTRICT'
            );
            $this->assertForeignKey(
                $connection,
                'attendance_records',
                'student_id',
                'users',
                'id',
                'RESTRICT'
            );
            $this->assertForeignKey(
                $connection,
                'attendance_sessions',
                'course_section_id',
                'course_sections',
                'id',
                'RESTRICT'
            );

            $connection->table('attendance_sessions')->insert($this->sessionPayload(2, 1));

            try {
                $connection->table('attendance_sessions')->where('id', 1)->delete();
                $this->fail('Deleting a referenced original session should be rejected.');
            } catch (QueryException) {
                $this->assertSame(2, $connection->table('attendance_sessions')->count());
            }

            $connection->table('attendance_sessions')->where('id', 2)->delete();
            $lifecycleMigration->down();

            $this->assertFalse($connection->getSchemaBuilder()->hasColumn('attendance_sessions', 'session_kind'));
            $this->assertSame(1, $connection->table('attendance_sessions')->count());
        } finally {
            $this->restoreConnection($connectionName, $connectionConfigKey, $originalDefaultConnection, $originalConfiguration);
        }
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: mixed}
     */
    private function prepareConnection(string $connectionName): array
    {
        $connectionConfigKey = "database.connections.{$connectionName}";
        $originalDefaultConnection = DB::getDefaultConnection();
        $originalConfiguration = config($connectionConfigKey);
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

        return [$connectionName, $connectionConfigKey, $originalDefaultConnection, $originalConfiguration];
    }

    private function runBaseMigrations(string $connectionName): void
    {
        foreach ([
            '2026_10_26_161011_create_courses_table.php',
            '2026_10_26_161525_create_course_sections_table.php',
            '2026_10_26_161821_create_enrollments_table.php',
            '2026_10_26_161928_create_class_schedules_table.php',
            '2026_10_26_162217_create_attendance_sessions_table.php',
            '2026_10_26_162352_create_attendance_records_table.php',
        ] as $migrationFile) {
            $migration = require database_path("migrations/{$migrationFile}");
            $migration->up();
        }
    }

    private function seedCourseSection(Connection $connection): void
    {
        $connection->table('users')->insert(['id' => 1]);
        $connection->table('courses')->insert([
            'id' => 1,
            'code' => 'LIFECYCLE',
            'name' => 'Lifecycle',
        ]);
        $connection->table('course_sections')->insert([
            'id' => 1,
            'course_id' => 1,
            'name' => 'A',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionPayload(int $id, ?int $originalSessionId = null): array
    {
        $payload = [
            'id' => $id,
            'course_section_id' => 1,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pertemuan '.$id,
            'starts_at' => '2026-10-27 08:00:00',
            'ends_at' => '2026-10-27 10:00:00',
            'attendance_opens_at' => '2026-10-27 07:45:00',
            'attendance_closes_at' => '2026-10-27 10:15:00',
            'allowed_methods' => '["manual"]',
            'status' => 'ditutup',
        ];

        if ($originalSessionId !== null) {
            $payload['original_session_id'] = $originalSessionId;
        }

        return $payload;
    }

    private function assertNullableColumn(Connection $connection, string $column): void
    {
        $columnDefinition = collect($connection->select("PRAGMA table_info('attendance_sessions')"))
            ->firstWhere('name', $column);

        $this->assertNotNull($columnDefinition);
        $this->assertSame(0, (int) $columnDefinition->notnull);
    }

    /**
     * @param  list<string>  $disallowedDefaults
     */
    private function assertColumnHasNoDefault(
        Connection $connection,
        string $column,
        array $disallowedDefaults
    ): void {
        $columnDefinition = collect($connection->select("PRAGMA table_info('attendance_sessions')"))
            ->firstWhere('name', $column);

        $this->assertNotNull($columnDefinition);
        $this->assertNotContains($columnDefinition->dflt_value, $disallowedDefaults);
    }

    private function assertForeignKey(
        Connection $connection,
        string $table,
        string $column,
        string $referencedTable,
        string $referencedColumn,
        string $onDelete
    ): void {
        $foreignKeys = $connection->select("PRAGMA foreign_key_list('{$table}')");
        $matchingKeys = array_values(array_filter(
            $foreignKeys,
            static fn (object $foreignKey): bool => $foreignKey->from === $column
                && $foreignKey->table === $referencedTable
                && $foreignKey->to === $referencedColumn
        ));

        $this->assertCount(1, $matchingKeys);
        $this->assertSame($onDelete, $matchingKeys[0]->on_delete);
    }

    private function restoreConnection(
        string $connectionName,
        string $connectionConfigKey,
        string $originalDefaultConnection,
        mixed $originalConfiguration
    ): void {
        DB::setDefaultConnection($originalDefaultConnection);
        DB::purge($connectionName);
        config()->set($connectionConfigKey, $originalConfiguration);
    }
}
