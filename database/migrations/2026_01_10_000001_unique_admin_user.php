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
        DB::statement('CREATE UNIQUE INDEX unique_admin ON users ((CASE WHEN is_admin = 1 THEN 1 ELSE NULL END))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX unique_admin ON users');
    }
};
