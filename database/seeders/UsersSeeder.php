<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::transaction(function () {
            $seedAdmin = [
                'name' => 'admin',
                'email' => 'admin@ntubuyaki.com',
                'password' => bcrypt('test'),
                'email_verified_at' => now(),
                'is_admin' => true,
                'is_seed_admin' => true,
            ];

            $seedAdminUser = User::where('is_seed_admin', true)->orderBy('id')->first();
            $emailUser = User::where('email', $seedAdmin['email'])->first();
            $nameUser = User::where('name', $seedAdmin['name'])->first();

            // email/name はユニークなので、既存の同じ情報を持つユーザーを優先して上書きする
            $user = $emailUser ?? $nameUser ?? $seedAdminUser ?? new User();

            User::where('is_seed_admin', true)
                ->when($user->exists, fn ($query) => $query->whereKeyNot($user->getKey()))
                ->update(['is_seed_admin' => false]);

            $user->forceFill($seedAdmin);
            $user->save();
        });
    }
}
