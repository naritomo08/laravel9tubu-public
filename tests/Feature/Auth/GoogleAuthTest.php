<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.google.client_id', 'google-client-id');
        config()->set('services.google.client_secret', 'google-client-secret');
        config()->set('services.google.redirect_uri', 'http://127.0.0.1:8080/auth/google/callback');
    }

    public function test_verified_user_can_link_google_account_from_account_settings()
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-token',
            ], 200),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'google-user-123',
                'email' => 'linked@gmail.com',
                'email_verified' => true,
                'picture' => 'https://example.com/avatar.png',
            ], 200),
        ]);

        $user = User::factory()->create();

        $redirectResponse = $this->actingAs($user)
            ->get(route('account.google.connect'));

        $state = session('google_oauth_state');

        $redirectResponse->assertStatus(302);
        $this->assertStringContainsString(
            'https://accounts.google.com/o/oauth2/v2/auth',
            (string) $redirectResponse->headers->get('Location')
        );

        $this->actingAs($user)
            ->get(route('auth.google.callback', [
                'state' => $state,
                'code' => 'google-auth-code',
            ]))
            ->assertRedirect(route('account.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'google_id' => 'google-user-123',
            'google_email' => 'linked@gmail.com',
        ]);
    }

    public function test_google_login_authenticates_user_when_account_is_already_linked()
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-token',
            ], 200),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'google-user-123',
                'email' => 'linked@gmail.com',
                'email_verified' => true,
            ], 200),
        ]);

        $user = User::factory()->create([
            'google_id' => 'google-user-123',
            'google_email' => 'linked@gmail.com',
        ]);

        $redirectResponse = $this->get(route('auth.google.redirect'));

        $state = session('google_oauth_state');

        $redirectResponse->assertStatus(302);
        $this->assertStringContainsString(
            'https://accounts.google.com/o/oauth2/v2/auth',
            (string) $redirectResponse->headers->get('Location')
        );

        $response = $this->get(route('auth.google.callback', [
            'state' => $state,
            'code' => 'google-auth-code',
        ]));

        $response->assertRedirect(RouteServiceProvider::HOME);
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_login_does_not_auto_link_existing_email_account()
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-token',
            ], 200),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'google-user-123',
                'email' => 'member@example.com',
                'email_verified' => true,
            ], 200),
        ]);

        User::factory()->create([
            'email' => 'member@example.com',
            'google_id' => null,
        ]);

        $this->get(route('auth.google.redirect'));

        $response = $this->get(route('auth.google.callback', [
            'state' => session('google_oauth_state'),
            'code' => 'google-auth-code',
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('google');
        $this->assertGuest();
    }

    public function test_user_can_disconnect_google_account()
    {
        $user = User::factory()->create([
            'google_id' => 'google-user-123',
            'google_email' => 'linked@gmail.com',
            'google_connected_at' => now(),
        ]);

        $this->actingAs($user)
            ->delete(route('account.google.disconnect'))
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'google_id' => null,
            'google_email' => null,
            'google_connected_at' => null,
        ]);
    }

    public function test_google_login_redirects_back_with_error_when_google_api_fails()
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([], 500),
        ]);

        $this->get(route('auth.google.redirect'));

        $response = $this->get(route('auth.google.callback', [
            'state' => session('google_oauth_state'),
            'code' => 'google-auth-code',
        ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'google' => 'Google認証との通信に失敗しました。時間をおいて再度お試しください。',
        ]);
    }
}
