<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Dusk\Browser;
use RuntimeException;
use Throwable;
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
        $email = 'dusk-login-'.uniqid().'@example.com';
        $users = $this->createDuskUsers($connection, $email, $password);

        try {
            $this->browse(function (Browser $browser) use ($connection, $email, $password) {
                try {
                    $browser->visit('/login')
                            ->waitFor('input[name="email"]')
                            ->type('input[name="email"]', $email)
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
                            'Created user email: '.$email,
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
            foreach ($users as $user) {
                $user->delete();
                DB::connection($user->getConnectionName())->disconnect();
            }
        }
    }

    /**
     * Create the login user in candidate databases because Dusk's CLI process
     * and the web-facing PHP-FPM process can be configured differently.
     */
    private function createDuskUsers(string $primaryConnection, string $email, string $password): array
    {
        $users = [];
        $createdTargets = [];

        foreach (array_unique([$primaryConnection, $this->configureLocalApplicationDatabaseConnection()]) as $connection) {
            if (!$this->hasUsersTable($connection)) {
                continue;
            }

            $target = $this->connectionTarget($connection);
            if (isset($createdTargets[$target])) {
                continue;
            }

            User::on($connection)->where('email', $email)->delete();

            $users[] = User::on($connection)->create([
                'name' => 'Dusk Login User '.substr(md5($email), 0, 12),
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'remember_token' => null,
                'is_admin' => false,
            ]);
            $createdTargets[$target] = true;
        }

        if (empty($users)) {
            throw new RuntimeException('Could not create a Dusk login user because no candidate database has a users table.');
        }

        return $users;
    }

    private function connectionTarget(string $connection): string
    {
        $config = Config::get("database.connections.{$connection}");

        return implode('|', [
            $config['driver'] ?? '',
            $config['host'] ?? '',
            $config['port'] ?? '',
            $config['database'] ?? '',
            $config['username'] ?? '',
        ]);
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

    private function configureLocalApplicationDatabaseConnection(): string
    {
        $connection = 'dusk_local_application';

        Config::set("database.connections.{$connection}", [
            'driver' => 'mysql',
            'host' => env('DUSK_LOCAL_DB_HOST', 'db'),
            'port' => env('DUSK_LOCAL_DB_PORT', '3306'),
            'database' => env('DUSK_LOCAL_DB_DATABASE', 'laravel_local'),
            'username' => env('DUSK_LOCAL_DB_USERNAME', 'phper'),
            'password' => env('DUSK_LOCAL_DB_PASSWORD', 'secret'),
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

    private function hasUsersTable(string $connection): bool
    {
        try {
            return Schema::connection($connection)->hasTable('users');
        } catch (Throwable $e) {
            return false;
        }
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
