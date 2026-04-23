<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    /**
     * A Dusk test example.
     */
    public function testSuccessfullLogin()
    {
        $connection = $this->configureDuskApplicationDatabaseConnection();
        $password = 'password';
        $user = User::on($connection)->create([
            'name' => 'Dusk Login User',
            'email' => 'dusk-login-'.uniqid().'@example.com',
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'remember_token' => null,
            'is_admin' => false,
        ]);

        try {
            $this->browse(function (Browser $browser) use ($user, $password) {
                $browser->visit('/login')
                        ->waitFor('input[name="email"]')
                        ->type('input[name="email"]', $user->email)
                        ->type('input[name="password"]', $password)
                        ->click('@login-button')
                        ->waitUntilMissing('input[name="email"]', 15)
                        ->assertPathIs('/tweet')
                        ->waitForText('つぶやきアプリ')
                        ->assertSee('つぶやきアプリ');
            });
        } finally {
            $user->delete();
            DB::connection($connection)->disconnect();
        }
    }

    private function configureDuskApplicationDatabaseConnection(): string
    {
        $connection = 'dusk_application';

        Config::set("database.connections.{$connection}", [
            'driver' => 'mysql',
            'host' => env('DUSK_APP_DB_HOST', env('DB_HOST', 'db')),
            'port' => env('DUSK_APP_DB_PORT', '3306'),
            'database' => env('DUSK_APP_DB_DATABASE', env('DB_DATABASE', 'laravel_local')),
            'username' => env('DUSK_APP_DB_USERNAME', env('DB_USERNAME', 'phper')),
            'password' => env('DUSK_APP_DB_PASSWORD', env('DB_PASSWORD', 'secret')),
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ]);

        return $connection;
    }
}
