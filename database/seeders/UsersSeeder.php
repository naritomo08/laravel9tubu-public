<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

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
                'name' => config('seed.admin.name'),
                'email' => config('seed.admin.email'),
                'password' => bcrypt(config('seed.admin.password')),
                'email_verified_at' => now(),
                'is_admin' => true,
                'is_seed_admin' => true,
            ];

            $seedAdminUser = User::where('is_seed_admin', true)->orderBy('id')->first();
            $emailUser = User::where('is_seed_admin', true)->where('email', $seedAdmin['email'])->first();
            $nameUser = User::where('is_seed_admin', true)->where('name', $seedAdmin['name'])->first();

            $conflictingUser = User::where('is_seed_admin', false)
                ->where(function ($query) use ($seedAdmin) {
                    $query
                        ->where('email', $seedAdmin['email'])
                        ->orWhere('name', $seedAdmin['name']);
                })
                ->first();

            if ($conflictingUser) {
                throw new RuntimeException('Seeder固定管理者のユーザー名またはメールアドレスが既存ユーザーと重複しています。');
            }

            // 変更対象は必ず is_seed_admin=true の既存ユーザー、または新規ユーザーに限定する
            $user = $emailUser ?? $nameUser ?? $seedAdminUser ?? new User();

            User::where('is_seed_admin', true)
                ->when($user->exists, fn ($query) => $query->whereKeyNot($user->getKey()))
                ->update(['is_seed_admin' => false]);

            $user->forceFill($seedAdmin);
            $user->save();
        });
    }
}
