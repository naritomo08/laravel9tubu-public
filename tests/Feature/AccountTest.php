<?php

namespace Tests\Feature;

use App\Models\Like;
use App\Models\Tweet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_link_is_only_shown_to_verified_users()
    {
        $verifiedUser = User::factory()->create();
        $unverifiedUser = User::factory()->unverified()->create();

        $this->actingAs($unverifiedUser)
            ->get('/tweet')
            ->assertDontSee('アカウント設定');

        $this->actingAs($verifiedUser)
            ->get('/tweet')
            ->assertSee('アカウント設定');
    }

    public function test_account_screen_requires_verified_user()
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/account')
            ->assertRedirect('/verify-email');
    }

    public function test_verified_user_can_update_own_profile()
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $this->actingAs($user)
            ->put('/account/profile', [
                'name' => 'New Name',
                'email' => 'old@example.com',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'old@example.com',
        ]);
        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_email_change_requires_verification_and_sends_notification()
    {
        Notification::fake();

        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $this->actingAs($user)
            ->put('/account/profile', [
                'name' => 'New Name',
                'email' => 'new@example.com',
            ])
            ->assertRedirect('/verify-email');

        $user->refresh();

        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_profile_update_requires_unique_name_and_email()
    {
        $otherUser = User::factory()->create([
            'name' => 'Existing Name',
            'email' => 'existing@example.com',
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/account/profile', [
                'name' => $otherUser->name,
                'email' => $otherUser->email,
            ])
            ->assertSessionHasErrors(['name', 'email']);
    }

    public function test_verified_user_can_update_own_password()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/account/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_admin_can_update_own_password()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->put('/account/password', [
                'current_password' => 'password',
                'password' => 'new-admin-password',
                'password_confirmation' => 'new-admin-password',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('new-admin-password', $admin->refresh()->password));
    }

    public function test_user_can_delete_own_account_and_is_logged_out()
    {
        $user = User::factory()->create();
        Tweet::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete('/account', [
                'current_password' => 'password',
            ])
            ->assertRedirect('/tweet');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('tweets', ['user_id' => $user->id]);
    }

    public function test_admin_account_screen_does_not_have_delete_section()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/account')
            ->assertSee('プロフィール変更')
            ->assertSee('パスワード変更')
            ->assertDontSee('アカウント削除');
    }

    public function test_account_screen_displays_google_link_section()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/account')
            ->assertOk()
            ->assertSee('Google連携')
            ->assertSee('Googleアカウントを連携する');
    }

    public function test_verified_user_can_update_mail_settings()
    {
        $user = User::factory()->create([
            'receives_notification_mail' => true,
        ]);

        $this->actingAs($user)
            ->put('/account/mail-settings', [])
            ->assertRedirect();

        $this->assertFalse($user->refresh()->receives_notification_mail);

        $this->actingAs($user)
            ->put('/account/mail-settings', [
                'receives_notification_mail' => '1',
            ])
            ->assertRedirect();

        $this->assertTrue($user->refresh()->receives_notification_mail);
    }

    public function test_account_screen_displays_own_stats()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownTweetA = Tweet::factory()->create(['user_id' => $user->id]);
        $ownTweetB = Tweet::factory()->create(['user_id' => $user->id]);
        $otherTweet = Tweet::factory()->create(['user_id' => $otherUser->id]);

        Like::create(['tweet_id' => $ownTweetA->id, 'user_id' => $otherUser->id]);
        Like::create(['tweet_id' => $ownTweetB->id, 'user_id' => $otherUser->id]);
        Like::create(['tweet_id' => $otherTweet->id, 'user_id' => $user->id]);

        $this->actingAs($user)
            ->get('/account')
            ->assertOk()
            ->assertSee('あなたのつぶやき・いいね集計')
            ->assertSee('あなたの集計')
            ->assertSee('2')
            ->assertSee('2');
    }

    public function test_account_screen_displays_only_own_upcoming_scheduled_tweets()
    {
        Carbon::setTestNow('2026-05-01 10:00:00');

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'own future scheduled tweet',
            'scheduled_at' => Carbon::parse('2026-05-01 11:00:00'),
        ]);
        Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'own expired scheduled tweet',
            'scheduled_at' => Carbon::parse('2026-05-01 09:00:00'),
        ]);
        Tweet::factory()->create([
            'user_id' => $otherUser->id,
            'content' => 'other future scheduled tweet',
            'scheduled_at' => Carbon::parse('2026-05-01 11:30:00'),
        ]);

        $this->actingAs($user)
            ->get('/account')
            ->assertOk()
            ->assertSee('あなたの予約投稿一覧')
            ->assertSee('own future scheduled tweet')
            ->assertSee('2026-05-01 11:00:00')
            ->assertSee('編集')
            ->assertSee('削除')
            ->assertDontSee('own expired scheduled tweet')
            ->assertDontSee('other future scheduled tweet');
    }

    public function test_account_can_fetch_own_upcoming_scheduled_tweets_html_json()
    {
        Carbon::setTestNow('2026-05-01 10:00:00');

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'dynamic own future scheduled tweet',
            'scheduled_at' => Carbon::parse('2026-05-01 11:00:00'),
        ]);
        Tweet::factory()->create([
            'user_id' => $otherUser->id,
            'content' => 'dynamic other future scheduled tweet',
            'scheduled_at' => Carbon::parse('2026-05-01 11:30:00'),
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('account.scheduled-tweets'))
            ->assertOk()
            ->assertJsonStructure(['html']);

        $this->assertStringContainsString('dynamic own future scheduled tweet', $response->json('html'));
        $this->assertStringContainsString('編集', $response->json('html'));
        $this->assertStringContainsString('削除', $response->json('html'));
        $this->assertStringNotContainsString('dynamic other future scheduled tweet', $response->json('html'));
    }

    public function test_user_can_edit_own_scheduled_tweet_from_account_screen()
    {
        Carbon::setTestNow('2026-05-01 10:00:00');

        $user = User::factory()->create();
        $tweet = Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'before scheduled account edit',
            'scheduled_at' => Carbon::parse('2026-05-01 11:00:00'),
        ]);

        $this->actingAs($user)
            ->get(route('tweet.update.index', [
                'tweetId' => $tweet->id,
                'return_url' => route('account.index', [], false),
            ]))
            ->assertOk()
            ->assertSee('value="2026-05-01T11:00"', false);

        $this->actingAs($user)
            ->put(route('tweet.update.put', $tweet), [
                'tweet' => 'after scheduled account edit',
                'scheduled_at' => '2026-05-01T12:00',
                'return_url' => route('account.index', [], false),
            ])
            ->assertRedirect(route('account.index', [], false));

        $tweet->refresh();
        $this->assertSame('after scheduled account edit', $tweet->content);
        $this->assertSame('2026-05-01 12:00:00', $tweet->scheduled_at->format('Y-m-d H:i:s'));
    }

    public function test_user_can_delete_own_scheduled_tweet_from_account_screen()
    {
        Carbon::setTestNow('2026-05-01 10:00:00');

        $user = User::factory()->create();
        $tweet = Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'deletable scheduled account tweet',
            'scheduled_at' => Carbon::parse('2026-05-01 11:00:00'),
        ]);

        $this->actingAs($user)
            ->delete(route('tweet.delete', $tweet), [
                'return_url' => route('account.index', [], false),
            ])
            ->assertRedirect(route('account.index', [], false));

        $this->assertDatabaseMissing('tweets', [
            'id' => $tweet->id,
        ]);
    }

    public function test_account_stats_endpoint_returns_authenticated_user_stats()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownTweetA = Tweet::factory()->create(['user_id' => $user->id]);
        $ownTweetB = Tweet::factory()->create(['user_id' => $user->id]);
        $otherTweet = Tweet::factory()->create(['user_id' => $otherUser->id]);

        Like::create(['tweet_id' => $ownTweetA->id, 'user_id' => $otherUser->id]);
        Like::create(['tweet_id' => $ownTweetB->id, 'user_id' => $otherUser->id]);
        Like::create(['tweet_id' => $otherTweet->id, 'user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson('/account/stats')
            ->assertOk()
            ->assertExactJson([
                'label' => 'あなたの集計',
                'tweet_count' => 2,
                'like_count' => 2,
            ]);
    }

    public function test_admin_can_not_delete_own_account()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->delete('/account', [
                'current_password' => 'password',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertAuthenticatedAs($admin);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
