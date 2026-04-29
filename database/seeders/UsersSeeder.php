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
        // Seederで作成した管理者は、名前やメールアドレスが変わっても固定管理者として扱う
        $user = User::where('is_seed_admin', true)->first()
            ?? User::where('email', 'admin@tubuyaki.com')->first()
            ?? new User([
                'name' => 'admin',
                'email' => 'admin@tubuyaki.com',
                'password' => bcrypt('test'),
                'email_verified_at' => now(),
            ]);

        $user->is_admin = true;
        $user->is_seed_admin = true;
        $user->save();

    }
}
