<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    /**
     * A Dusk test example.
     */
    public function testSuccessfullLogin()
    {
        $this->browse(function (Browser $browser) {
            try {
                $user = User::factory()->create();
                $browser->visit('/login')
                        ->waitFor('input[name="email"]')
                        ->type('input[name="email"]', $user->email)
                        ->type('input[name="password"]', 'password')
                        ->click('button[type="submit"]')
                        ->waitForLocation('/tweet')
                        ->waitForText('つぶやきアプリ')
                        ->assertSee('つぶやきアプリ');
            } catch (\Exception $e) {
                $browser->screenshot('login-test-error');
                throw $e;
            }
        });
    }
}
