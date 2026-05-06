<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // is_admin=true のユーザーをキーに上書き・新規作成
        User::updateOrCreate(
            ['is_admin' => true],
            [
                'name' => 'admin',
                'email' => 'admin@tubuyaki.com',
                'password' => bcrypt('test'),
                'email_verified_at' => now(),
            ]
        );

    }
}
