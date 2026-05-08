<?php

namespace Tests\Browser;

use App\Models\User;
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
        $password = 'password';
        $email = 'dusk-login-'.uniqid().'@example.com';
        $userName = 'Dusk Login User '.substr(md5($email), 0, 12);
        $user = $this->createDuskUser($email, $password, $userName);

        try {
            $this->browse(function (Browser $browser) use ($email, $password, $userName) {
                try {
                    $browser->visit('/login')
                            ->waitFor('input[name="email"]')
                            ->type('input[name="email"]', $email)
                            ->type('input[name="password"]', $password)
                            ->click('@login-button')
                            ->waitUntilMissing('input[name="email"]', 15)
                            ->assertPathIs('/tweet')
                            ->waitForText('ようこそ'.$userName.'さん')
                            ->assertSee('ようこそ'.$userName.'さん');
                } catch (\Throwable $e) {
                    throw new RuntimeException(
                        implode("\n", [
                            'Login Dusk test failed.',
                            'Current URL: '.$browser->driver->getCurrentURL(),
                            'Created user email: '.$email,
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
            DB::disconnect($user->getConnectionName());
        }
    }

    private function createDuskUser(string $email, string $password, string $name): User
    {
        User::query()->where('email', $email)->delete();

        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'remember_token' => null,
            'is_admin' => false,
        ]);
    }

    private function getPageText(Browser $browser): string
    {
        return (string) $browser->driver->executeScript(
            'return document.body ? document.body.innerText : "";'
        );
    }
}
