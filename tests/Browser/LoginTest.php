<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use RuntimeException;
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
            $this->browse(function (Browser $browser) use ($connection, $user, $password) {
                try {
                    $browser->visit('/login')
                            ->waitFor('input[name="email"]')
                            ->type('input[name="email"]', $user->email)
                            ->type('input[name="password"]', $password)
                            ->click('@login-button')
                            ->waitUntilMissing('input[name="email"]', 15)
                            ->assertPathIs('/tweet')
                            ->waitForText('つぶやきアプリ')
                            ->assertSee('つぶやきアプリ');
                } catch (\Throwable $e) {
                    throw new RuntimeException(
                        implode("\n", [
                            'Login Dusk test failed.',
                            'Current URL: '.$browser->driver->getCurrentURL(),
                            'Created user email: '.$user->email,
                            'Created user DB: '.$this->describeConnection($connection),
                            'Page text:',
                            mb_substr($this->getPageText($browser), 0, 1000),
                        ]),
                        0,
                        $e
                    );
                }
            });
        } finally {
            $user->delete();
            DB::connection($connection)->disconnect();
        }
    }

    private function configureDuskApplicationDatabaseConnection(): string
    {
        $connection = 'dusk_application';
        $appEnv = $this->getApplicationProcessEnvironment();

        Config::set("database.connections.{$connection}", [
            'driver' => 'mysql',
            'host' => env('DUSK_APP_DB_HOST', $appEnv['DB_HOST'] ?? env('DB_HOST', 'db')),
            'port' => env('DUSK_APP_DB_PORT', '3306'),
            'database' => env('DUSK_APP_DB_DATABASE', $appEnv['DB_DATABASE'] ?? env('DB_DATABASE', 'laravel_local')),
            'username' => env('DUSK_APP_DB_USERNAME', $appEnv['DB_USERNAME'] ?? env('DB_USERNAME', 'phper')),
            'password' => env('DUSK_APP_DB_PASSWORD', $appEnv['DB_PASSWORD'] ?? env('DB_PASSWORD', 'secret')),
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

    private function getApplicationProcessEnvironment(): array
    {
        $environmentFile = '/proc/1/environ';

        if (!is_readable($environmentFile)) {
            return [];
        }

        $environment = [];

        foreach (explode("\0", (string) file_get_contents($environmentFile)) as $entry) {
            if ($entry === '' || !str_contains($entry, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $entry, 2);
            $environment[$key] = $value;
        }

        return $environment;
    }

    private function describeConnection(string $connection): string
    {
        $config = Config::get("database.connections.{$connection}");

        return sprintf(
            '%s/%s@%s:%s',
            $config['username'],
            $config['database'],
            $config['host'],
            $config['port']
        );
    }

    private function getPageText(Browser $browser): string
    {
        return (string) $browser->driver->executeScript(
            'return document.body ? document.body.innerText : "";'
        );
    }
}
