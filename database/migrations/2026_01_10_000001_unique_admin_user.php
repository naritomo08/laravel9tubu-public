<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // is_admin=1のユーザーが1人だけになるようユニーク制約を追加
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX unique_admin ON users (is_admin) WHERE is_admin = true');

            return;
        }

        DB::statement('CREATE UNIQUE INDEX unique_admin ON users ((CASE WHEN is_admin = 1 THEN 1 ELSE NULL END))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('DROP INDEX unique_admin ON users');

            return;
        }

        DB::statement('DROP INDEX IF EXISTS unique_admin');
    }
};
