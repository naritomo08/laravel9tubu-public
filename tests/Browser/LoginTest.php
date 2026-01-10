<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
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
                        ->type('email', $user->email)
                        ->type('password', 'password')
                        ->press('LOG IN')
                        ->assertPathIs('/tweet')
                        ->assertSee('つぶやきアプリ');
            } catch (\Exception $e) {
                $browser->screenshot('login-test-error');  // エラー時にスクリーンショットを取得
                throw $e;
            }
        });
    }
}
