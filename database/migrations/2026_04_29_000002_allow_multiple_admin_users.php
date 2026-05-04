<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->dropUniqueAdminIndex();

        if (! Schema::hasColumn('users', 'is_seed_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_seed_admin')->default(false);
            });
        }

        DB::table('users')
            ->where('email', 'admin@tubuyaki.org')
            ->where('is_admin', true)
            ->update(['is_seed_admin' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->createUniqueAdminIndex();
    }

    private function dropUniqueAdminIndex(): void
    {
        if (! $this->uniqueAdminIndexExists()) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('DROP INDEX unique_admin ON users');

            return;
        }

        DB::statement('DROP INDEX IF EXISTS unique_admin');
    }

    private function createUniqueAdminIndex(): void
    {
        if ($this->uniqueAdminIndexExists()) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX unique_admin ON users (is_admin) WHERE is_admin IS TRUE');

            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX unique_admin ON users (is_admin) WHERE is_admin = true');

            return;
        }

        DB::statement('CREATE UNIQUE INDEX unique_admin ON users ((CASE WHEN is_admin = 1 THEN 1 ELSE NULL END))');
    }

    private function uniqueAdminIndexExists(): bool
    {
        return match (DB::getDriverName()) {
            'mysql' => DB::table('information_schema.statistics')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', 'users')
                ->where('index_name', 'unique_admin')
                ->exists(),
            'pgsql' => DB::table('pg_indexes')
                ->where('schemaname', 'public')
                ->where('tablename', 'users')
                ->where('indexname', 'unique_admin')
                ->exists(),
            'sqlite' => collect(DB::select("PRAGMA index_list('users')"))
                ->contains(fn (object $index): bool => $index->name === 'unique_admin'),
            default => false,
        };
    }
};
