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
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('DROP INDEX unique_admin ON users');
        } elseif ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS unique_admin');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_seed_admin')->default(false)->after('is_admin');
        });

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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_seed_admin');
        });

        DB::statement('CREATE UNIQUE INDEX unique_admin ON users ((CASE WHEN is_admin = 1 THEN 1 ELSE NULL END))');
    }
};
