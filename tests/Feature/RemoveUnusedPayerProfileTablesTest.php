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

class RemoveUnusedPayerProfileTablesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_cleanup_removes_only_empty_profiles_and_preserves_users_and_history(): void
    {
        $user = User::factory()->create();
        $migration = $this->cleanupMigration();
        $migration->down();
        $migrationCount = DB::table('migrations')->count();

        $migration->up();

        $this->assertFalse(Schema::hasTable('pemberi_zakat_fitrah'));
        $this->assertFalse(Schema::hasTable('pemberi_zakat_mal'));
        $this->assertModelExists($user);
        $this->assertDatabaseCount('migrations', $migrationCount);
        foreach (['mustahik', 'transaksi_zakat', 'transaksi_fitrah', 'transaksi_mal', 'subjek_zakat_maal'] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
        }
    }

    public function test_cleanup_can_continue_when_profile_tables_are_already_absent(): void
    {
        $user = User::factory()->create();

        $this->cleanupMigration()->up();

        $this->assertFalse(Schema::hasTable('pemberi_zakat_fitrah'));
        $this->assertFalse(Schema::hasTable('pemberi_zakat_mal'));
        $this->assertModelExists($user);
    }

    public function test_rollback_restores_empty_profiles_with_their_user_constraints(): void
    {
        $user = User::factory()->create();
        $migration = $this->cleanupMigration();

        $migration->down();

        $this->assertDatabaseCount('pemberi_zakat_fitrah', 0);
        $this->assertDatabaseCount('pemberi_zakat_mal', 0);
        DB::table('pemberi_zakat_fitrah')->insert(['id_kepala' => $user->id]);
        DB::table('pemberi_zakat_mal')->insert(['user_id' => $user->id, 'nominal_tabungan' => '100000.00']);
        $this->assertDatabaseHas('pemberi_zakat_fitrah', ['id_kepala' => $user->id, 'jum_anggota' => 1]);
        $this->assertDatabaseHas('pemberi_zakat_mal', ['user_id' => $user->id, 'nominal_tabungan' => '100000.00']);

        $user->delete();

        $this->assertDatabaseCount('pemberi_zakat_fitrah', 0);
        $this->assertDatabaseCount('pemberi_zakat_mal', 0);
    }

    #[TestWith(['pemberi_zakat_fitrah', 'id_kepala'])]
    #[TestWith(['pemberi_zakat_mal', 'user_id'])]
    public function test_populated_profile_stops_cleanup_before_either_table_is_dropped(string $table, string $userColumn): void
    {
        $user = User::factory()->create();
        $migration = $this->cleanupMigration();
        $migration->down();
        DB::table($table)->insert([$userColumn => $user->id]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Tabel {$table} masih berisi data");

        try {
            $migration->up();
        } finally {
            $this->assertTrue(Schema::hasTable('pemberi_zakat_fitrah'));
            $this->assertTrue(Schema::hasTable('pemberi_zakat_mal'));
            $this->assertDatabaseHas($table, [$userColumn => $user->id]);
        }
    }

    private function cleanupMigration(): Migration
    {
        DB::table('migrations')->count();

        return require database_path('migrations/2026_10_09_080247_remove_unused_payer_profile_tables.php');
    }
}
