<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class RemoveUnusedFrameworkTablesTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const INFRASTRUCTURE_TABLES = [
        'sessions', 'password_reset_tokens', 'cache', 'cache_locks',
        'jobs', 'job_batches', 'failed_jobs',
    ];

    public function test_cleanup_preserves_business_records_and_migration_history(): void
    {
        $user = User::factory()->create();
        $migration = $this->cleanupMigration();
        $migration->down();
        $migrationCount = DB::table('migrations')->count();

        $migration->up();

        foreach (self::INFRASTRUCTURE_TABLES as $table) {
            $this->assertFalse(Schema::hasTable($table), $table);
        }
        $this->assertModelExists($user);
        $this->assertDatabaseCount('migrations', $migrationCount);
        $this->assertTrue(Schema::hasTable('transaksi_zakat'));
        $this->assertTrue(Schema::hasTable('reservasi_stok'));
    }

    public function test_rollback_recreates_empty_tables_without_changing_business_records(): void
    {
        $user = User::factory()->create();
        $migration = $this->cleanupMigration();

        $migration->down();

        foreach (self::INFRASTRUCTURE_TABLES as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertTrue(Schema::hasColumns('sessions', ['id', 'user_id', 'payload', 'last_activity']));
        $this->assertTrue(Schema::hasColumns('job_batches', ['id', 'total_jobs', 'failed_job_ids']));
        $this->assertTrue(Schema::hasIndex('failed_jobs', ['connection', 'queue', 'failed_at']));
        $this->assertModelExists($user);
    }

    public function test_populated_table_stops_cleanup_before_any_table_is_dropped(): void
    {
        $migration = $this->cleanupMigration();
        $migration->down();
        DB::table('failed_jobs')->insert([
            'uuid' => 'existing-failed-job',
            'connection' => 'sync',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Existing failure',
        ]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tabel failed_jobs masih berisi data');

        try {
            $migration->up();
        } finally {
            foreach (self::INFRASTRUCTURE_TABLES as $table) {
                $this->assertTrue(Schema::hasTable($table), $table);
            }
            $this->assertDatabaseHas('failed_jobs', ['uuid' => 'existing-failed-job']);
        }
    }

    #[TestWith(['session.driver', 'database'])]
    #[TestWith(['cache.default', 'database'])]
    #[TestWith(['queue.default', 'database'])]
    #[TestWith(['queue.default', 'failover'])]
    #[TestWith(['queue.failed.driver', 'database-uuids'])]
    #[TestWith(['queue.failed.driver', 'database'])]
    public function test_database_driver_stops_cleanup_before_any_table_is_dropped(string $key, string $value): void
    {
        $migration = $this->cleanupMigration();
        $migration->down();
        config([$key => $value]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Gunakan session/cache file');

        try {
            $migration->up();
        } finally {
            foreach (self::INFRASTRUCTURE_TABLES as $table) {
                $this->assertTrue(Schema::hasTable($table), $table);
            }
        }
    }

    private function cleanupMigration(): Migration
    {
        DB::table('migrations')->count();

        return require database_path('migrations/2026_10_09_071031_remove_unused_framework_tables.php');
    }
}
