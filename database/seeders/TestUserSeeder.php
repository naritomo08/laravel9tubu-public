<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'test-user',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_admin' => false,
                'is_seed_admin' => false,
            ]
        );
    }
}
