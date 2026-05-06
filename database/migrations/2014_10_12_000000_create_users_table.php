<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('email')->unique();
            $table->boolean('is_admin')->default(false);
            $table->boolean('is_seed_admin')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('receives_notification_mail')->default(true);
            $table->string('password');
            $table->string('google_id')->nullable()->unique();
            $table->string('google_email')->nullable();
            $table->text('google_avatar')->nullable();
            $table->timestamp('google_connected_at')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->rememberToken();
            $table->timestamp('deletion_requested_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
