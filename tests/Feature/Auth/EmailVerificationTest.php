<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_verification_status_returns_current_user_status()
    {
        $unverifiedUser = User::factory()->create([
            'email_verified_at' => null,
        ]);
        $verifiedUser = User::factory()->create();

        $this->actingAs($unverifiedUser)
            ->getJson(route('verification.status'))
            ->assertOk()
            ->assertJson([
                'verified' => false,
                'pending_initial_email_verification' => true,
            ]);

        $this->actingAs($verifiedUser)
            ->getJson(route('verification.status'))
            ->assertOk()
            ->assertJson([
                'verified' => true,
                'pending_initial_email_verification' => false,
            ]);
    }

    public function test_tweet_screen_watches_email_verification_for_unverified_user()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('tweet.index'))
            ->assertOk()
            ->assertSee('data-email-verification-watch', false)
            ->assertSee(route('verification.status', [], false), false);
    }

    public function test_tweet_screen_watches_email_verification_for_verified_user()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('tweet.index'))
            ->assertOk()
            ->assertSee('data-email-verification-watch', false)
            ->assertSee('data-is-verified="true"', false)
            ->assertSee('data-requires-verified-email', false);
    }

    public function test_tweet_screen_does_not_render_post_form_for_unverified_user()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('tweet.index'))
            ->assertOk()
            ->assertDontSee('data-tweet-input', false)
            ->assertSee('data-is-verified="false"', false);
    }

    public function test_tweet_screen_warns_initial_unverified_user_about_account_deletion()
    {
        $registeredAt = now()->subMinutes(30);
        $user = User::factory()->unverified()->create([
            'created_at' => $registeredAt,
            'updated_at' => $registeredAt,
        ]);

        $this->actingAs($user)
            ->get(route('tweet.index'))
            ->assertOk()
            ->assertSee('登録時のメールアドレスに届いた認証メールをご確認ください。')
            ->assertSee('登録から1時間以内にメール認証が完了しない場合、アカウントは自動的に削除されます。');
    }

    public function test_tweet_screen_does_not_warn_existing_user_after_email_change_about_account_deletion()
    {
        $user = User::factory()->unverified()->create([
            'email' => 'changed@example.com',
            'created_at' => now()->subDay(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('tweet.index'))
            ->assertOk()
            ->assertSee('新しいメールアドレスに届いた認証メールをご確認ください。')
            ->assertDontSee('アカウントは自動的に削除されます。');
    }

    public function test_email_can_be_verified()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(RouteServiceProvider::HOME.'?verified=1');
    }

    public function test_email_can_be_verified_while_another_user_is_authenticated()
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);
        $user = User::factory()->create([
            'email' => 'changed@example.com',
            'email_verified_at' => null,
        ]);

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($admin)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertTrue($admin->fresh()->is_admin);
        $response->assertRedirect(RouteServiceProvider::HOME.'?verified=1');
    }

    public function test_email_can_be_verified_without_login()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(RouteServiceProvider::HOME.'?verified=1');
    }

    public function test_email_verification_links_are_not_throttled_by_shared_ip_noise()
    {
        $users = User::factory()->count(7)->create([
            'email_verified_at' => null,
        ]);

        foreach ($users as $user) {
            $verificationUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                ['id' => $user->id, 'hash' => sha1($user->email)]
            );

            $this->get($verificationUrl)->assertRedirect(RouteServiceProvider::HOME.'?verified=1');
        }

        $users->each(function (User $user) {
            $this->assertTrue($user->fresh()->hasVerifiedEmail());
        });
    }

    public function test_email_can_be_verified_when_request_host_differs_from_generated_link()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        Event::fake();

        $verificationPath = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
            false
        );

        $response = $this
            ->withServerVariables(['HTTP_HOST' => 'example.test'])
            ->get($verificationPath);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(RouteServiceProvider::HOME.'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
