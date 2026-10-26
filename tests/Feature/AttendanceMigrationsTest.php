<?php

namespace Tests\Feature;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttendanceMigrationsTest extends TestCase
{
    public function test_attendance_migrations_create_expected_schema_and_roll_back_in_reverse_order(): void
    {
        $connectionName = 'attendance_migrations_test';
        $connectionConfigKey = "database.connections.{$connectionName}";
        $originalDefaultConnection = DB::getDefaultConnection();
        $originalConnectionConfiguration = config($connectionConfigKey);
        $migrationFiles = [
            '2026_10_26_161011_create_courses_table.php',
            '2026_10_26_161525_create_course_sections_table.php',
            '2026_10_26_161706_create_course_section_lecturers_table.php',
            '2026_10_26_161821_create_enrollments_table.php',
            '2026_10_26_161928_create_class_schedules_table.php',
            '2026_10_26_162217_create_attendance_sessions_table.php',
            '2026_10_26_162352_create_attendance_records_table.php',
        ];
        $migrationTables = [
            'users',
            'courses',
            'course_sections',
            'course_section_lecturers',
            'enrollments',
            'class_schedules',
            'attendance_sessions',
            'attendance_records',
        ];
        $executedMigrations = [];
        $testException = null;

        try {
            $sqliteConfiguration = config('database.connections.sqlite');
            $sqliteConfiguration['url'] = null;
            $sqliteConfiguration['database'] = ':memory:';
            $sqliteConfiguration['foreign_key_constraints'] = true;
            config()->set($connectionConfigKey, $sqliteConfiguration);

            DB::purge($connectionName);

            $connection = DB::connection($connectionName);

            $this->assertSame('sqlite', $connection->getDriverName());
            $this->assertSame(':memory:', $connection->getDatabaseName());
            $this->assertSame(
                'sqlite',
                $connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME)
            );

            $connection->statement('PRAGMA foreign_keys = ON');
            $this->assertSame(1, (int) $connection->selectOne('PRAGMA foreign_keys')->foreign_keys);

            Schema::connection($connectionName)->create('users', function (Blueprint $table): void {
                $table->id();
            });

            DB::setDefaultConnection($connectionName);

            foreach ($migrationFiles as $migrationFile) {
                $migration = require database_path("migrations/{$migrationFile}");
                $migration->up();
                $executedMigrations[] = $migration;
            }

            $schema = Schema::connection($connectionName);

            foreach (array_slice($migrationTables, 1) as $table) {
                $this->assertTrue($schema->hasTable($table), "Expected table [{$table}] to exist.");
            }

            $this->assertUniqueIndex($connection, 'courses', ['code']);
            $this->assertUniqueIndex(
                $connection,
                'course_sections',
                ['course_id', 'name', 'academic_year', 'semester']
            );
            $this->assertUniqueIndex(
                $connection,
                'course_section_lecturers',
                ['course_section_id', 'user_id', 'teaching_type'],
                'csl_section_user_type_unique'
            );
            $this->assertUniqueIndex($connection, 'enrollments', ['course_section_id', 'student_id']);
            $this->assertUniqueIndex(
                $connection,
                'attendance_records',
                ['attendance_session_id', 'student_id']
            );

            $this->assertIndex(
                $connection,
                'class_schedules',
                ['course_section_id', 'session_type', 'is_active'],
                false
            );
            $this->assertIndex(
                $connection,
                'attendance_sessions',
                ['course_section_id', 'status', 'starts_at'],
                false
            );
            $this->assertIndex($connection, 'attendance_records', ['student_id', 'status'], false);

            $this->assertForeignKey($connection, 'course_sections', 'course_id', 'courses', 'id', 'CASCADE');
            $this->assertForeignKey(
                $connection,
                'course_section_lecturers',
                'course_section_id',
                'course_sections',
                'id',
                'CASCADE'
            );
            $this->assertForeignKey(
                $connection,
                'course_section_lecturers',
                'user_id',
                'users',
                'id',
                'CASCADE'
            );
            $this->assertForeignKey(
                $connection,
                'enrollments',
                'course_section_id',
                'course_sections',
                'id',
                'CASCADE'
            );
            $this->assertForeignKey($connection, 'enrollments', 'student_id', 'users', 'id', 'CASCADE');
            $this->assertForeignKey(
                $connection,
                'class_schedules',
                'course_section_id',
                'course_sections',
                'id',
                'CASCADE'
            );
            $this->assertForeignKey(
                $connection,
                'attendance_sessions',
                'class_schedule_id',
                'class_schedules',
                'id',
                'SET NULL'
            );
            $this->assertForeignKey(
                $connection,
                'attendance_sessions',
                'course_section_id',
                'course_sections',
                'id',
                'CASCADE'
            );
            $this->assertForeignKey(
                $connection,
                'attendance_sessions',
                'opened_by',
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
                'CASCADE'
            );
            $this->assertForeignKey(
                $connection,
                'attendance_records',
                'student_id',
                'users',
                'id',
                'CASCADE'
            );
            $this->assertForeignKey(
                $connection,
                'attendance_records',
                'recorded_by',
                'users',
                'id',
                'SET NULL'
            );

            foreach (array_reverse($executedMigrations) as $migration) {
                $migration->down();
            }
            $executedMigrations = [];

            foreach (array_slice($migrationTables, 1) as $table) {
                $this->assertFalse($schema->hasTable($table), "Expected table [{$table}] to be rolled back.");
            }

            $this->assertTrue($schema->hasTable('users'));
        } catch (\Throwable $exception) {
            $testException = $exception;
        }

        $cleanupException = null;

        try {
            DB::setDefaultConnection($originalDefaultConnection);
        } catch (\Throwable $exception) {
            $cleanupException = $exception;
        }

        try {
            DB::purge($connectionName);
        } catch (\Throwable $exception) {
            $cleanupException ??= $exception;
        }

        try {
            config()->set($connectionConfigKey, $originalConnectionConfiguration);
        } catch (\Throwable $exception) {
            $cleanupException ??= $exception;
        }

        if ($testException !== null) {
            throw $testException;
        }

        if ($cleanupException !== null) {
            throw $cleanupException;
        }
    }

    /**
     * @param  list<string>  $columns
     */
    private function assertUniqueIndex(
        Connection $connection,
        string $table,
        array $columns,
        ?string $expectedName = null
    ): void {
        $this->assertIndex($connection, $table, $columns, true, $expectedName);
    }

    /**
     * @param  list<string>  $columns
     */
    private function assertIndex(
        Connection $connection,
        string $table,
        array $columns,
        bool $unique,
        ?string $expectedName = null
    ): void {
        $indexes = $connection->select("PRAGMA index_list('{$table}')");
        $matchingIndexes = array_values(array_filter(
            $indexes,
            function (object $index) use ($connection, $columns, $expectedName, $unique): bool {
                if ((bool) $index->unique !== $unique) {
                    return false;
                }

                if ($expectedName !== null && $index->name !== $expectedName) {
                    return false;
                }

                $quotedName = str_replace("'", "''", (string) $index->name);
                $indexColumns = array_map(
                    static fn (object $column): string => (string) $column->name,
                    $connection->select("PRAGMA index_info('{$quotedName}')")
                );

                return $indexColumns === $columns;
            }
        ));

        $this->assertCount(
            1,
            $matchingIndexes,
            'Expected '.($unique ? 'unique ' : '')."index on [{$table}] for [".implode(', ', $columns).']'.
                ($expectedName === null ? '.' : " named [{$expectedName}].")
        );
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

        $this->assertCount(
            1,
            $matchingKeys,
            "Expected foreign key [{$table}.{$column}] to reference [{$referencedTable}.{$referencedColumn}]."
        );
        $this->assertSame($onDelete, $matchingKeys[0]->on_delete);
    }
}
