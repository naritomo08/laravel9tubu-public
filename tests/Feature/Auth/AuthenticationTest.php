<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Fortify;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen()
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_users_with_two_factor_authentication_are_redirected_to_challenge()
    {
        $user = User::factory()->create([
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt('ABCDEFGHIJKLMNOP'),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['recovery-code'])),
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('two-factor.login'));
        $response->assertSessionHas('login.id', $user->id);
    }

    public function test_users_can_finish_two_factor_challenge_with_recovery_code()
    {
        $user = User::factory()->create([
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt('ABCDEFGHIJKLMNOP'),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['recovery-code'])),
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this
            ->withSession([
                'login.id' => $user->id,
                'login.remember' => false,
            ])
            ->post('/two-factor-challenge', [
                'recovery_code' => 'recovery-code',
            ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_non_admin_two_factor_users_are_not_redirected_to_stale_admin_intended_url()
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt('ABCDEFGHIJKLMNOP'),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['recovery-code'])),
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this
            ->withSession([
                'login.id' => $user->id,
                'login.remember' => false,
                'url.intended' => route('admin.users.index'),
            ])
            ->post('/two-factor-challenge', [
                'recovery_code' => 'recovery-code',
            ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(RouteServiceProvider::HOME);
        $response->assertSessionMissing('url.intended');
    }

    public function test_non_admin_users_are_not_redirected_to_stale_admin_intended_url_after_login()
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this
            ->withSession(['url.intended' => route('admin.users.index')])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_users_can_not_authenticate_with_invalid_password()
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_deletion_requested_user_can_not_authenticate()
    {
        $user = User::factory()->create([
            'deletion_requested_at' => now(),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertSessionHasErrors(['email' => 'このアカウントは削除受付済みです。']);

        $this->assertGuest();
    }
}
