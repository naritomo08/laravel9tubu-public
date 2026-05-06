<?php

namespace Tests\Feature\Admin;

use App\Jobs\DeleteUserJob;
use Database\Seeders\UsersSeeder;
use App\Models\Like;
use App\Models\Tweet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Fortify\Fortify;
use RuntimeException;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function twoFactorEnabledAttributes(): array
    {
        return [
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt('ABCDEFGHIJKLMNOP'),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['recovery-code'])),
            'two_factor_confirmed_at' => now(),
        ];
    }

    private function adminWithTwoFactor(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'is_admin' => true,
        ], $this->twoFactorEnabledAttributes(), $attributes));
    }

    public function test_admin_user_list_displays_emails_without_update_form_when_admin_has_no_two_factor()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['email' => 'old@example.com']);
        User::factory()->create([
            'email' => 'pending@example.com',
            'deletion_requested_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('old@example.com')
            ->assertDontSee('pending@example.com');

        $this->assertStringNotContainsString('name="email"', $response->getContent());
        $this->assertStringNotContainsString('/admin/users/'.$user->id.'/email', $response->getContent());
    }

    public function test_admin_user_list_displays_email_update_form_when_admin_has_two_factor()
    {
        $admin = $this->adminWithTwoFactor();
        $user = User::factory()->create([
            'email' => 'member@example.com',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('member@example.com')
            ->assertSee('メール変更')
            ->assertSee(route('admin.users.email.update', $user), false)
            ->assertSee('name="email"', false);
    }

    public function test_admin_stats_are_displayed_on_user_management_screen()
    {
        $admin = User::factory()->create(['is_admin' => true, 'name' => '管理者']);
        $user = User::factory()->create(['name' => 'ユーザー1']);
        $otherUser = User::factory()->create(['name' => 'ユーザー2']);

        $tweet1 = Tweet::factory()->create(['user_id' => $user->id]);
        $tweet2 = Tweet::factory()->create(['user_id' => $user->id]);
        $tweet3 = Tweet::factory()->create(['user_id' => $otherUser->id]);

        Like::create(['user_id' => $admin->id, 'tweet_id' => $tweet1->id]);
        Like::create(['user_id' => $otherUser->id, 'tweet_id' => $tweet1->id]);
        Like::create(['user_id' => $admin->id, 'tweet_id' => $tweet3->id]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('つぶやき・いいね集計')
            ->assertSee('トータル')
            ->assertSee('ユーザー1')
            ->assertSee('ユーザー2')
            ->assertSeeInOrder(['トータル', '3', '3'])
            ->assertSeeInOrder(['ユーザー1', '2', '2'])
            ->assertSeeInOrder(['ユーザー2', '1', '1']);
    }

    public function test_admin_can_fetch_stats_json()
    {
        $admin = User::factory()->create(['is_admin' => true, 'name' => '管理者']);
        $user = User::factory()->create(['name' => 'ユーザー1']);
        $tweet = Tweet::factory()->create(['user_id' => $user->id]);

        Like::create(['user_id' => $admin->id, 'tweet_id' => $tweet->id]);

        $this->actingAs($admin)
            ->getJson(route('admin.users.stats'))
            ->assertOk()
            ->assertJson([
                'totals' => [
                    'label' => 'トータル',
                    'tweet_count' => 1,
                    'like_count' => 1,
                ],
            ])
            ->assertJsonFragment([
                'name' => 'ユーザー1',
                'tweet_count' => 1,
                'like_count' => 1,
            ]);
    }

    public function test_admin_can_fetch_user_list_html_json()
    {
        $admin = $this->adminWithTwoFactor();
        User::factory()->create(['name' => '動的更新ユーザー']);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.users.list'))
            ->assertOk()
            ->assertJsonStructure(['html']);

        $this->assertStringContainsString('動的更新ユーザー', $response->json('html'));
    }

    public function test_admin_screen_displays_only_upcoming_scheduled_tweets()
    {
        Carbon::setTestNow('2026-05-01 10:00:00');

        $admin = $this->adminWithTwoFactor();
        $user = User::factory()->create(['name' => '予約ユーザー']);

        Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'future scheduled admin tweet',
            'scheduled_at' => Carbon::parse('2026-05-01 11:00:00'),
        ]);
        Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'expired scheduled admin tweet',
            'scheduled_at' => Carbon::parse('2026-05-01 09:00:00'),
        ]);
        Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'normal admin tweet',
            'scheduled_at' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('予約投稿一覧')
            ->assertSee('予約ユーザー')
            ->assertSee('future scheduled admin tweet')
            ->assertSee('2026-05-01 11:00:00')
            ->assertSee('削除')
            ->assertDontSee('expired scheduled admin tweet')
            ->assertDontSee('normal admin tweet');
    }

    public function test_admin_can_fetch_upcoming_scheduled_tweets_html_json()
    {
        Carbon::setTestNow('2026-05-01 10:00:00');

        $admin = $this->adminWithTwoFactor();
        $user = User::factory()->create(['name' => '動的予約ユーザー']);

        Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'dynamic future scheduled admin tweet',
            'scheduled_at' => Carbon::parse('2026-05-01 11:00:00'),
        ]);
        Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'dynamic expired scheduled admin tweet',
            'scheduled_at' => Carbon::parse('2026-05-01 09:00:00'),
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.users.scheduled-tweets'))
            ->assertOk()
            ->assertJsonStructure(['html']);

        $this->assertStringContainsString('動的予約ユーザー', $response->json('html'));
        $this->assertStringContainsString('dynamic future scheduled admin tweet', $response->json('html'));
        $this->assertStringContainsString('削除', $response->json('html'));
        $this->assertStringNotContainsString('dynamic expired scheduled admin tweet', $response->json('html'));
    }

    public function test_admin_can_delete_scheduled_tweet_from_admin_screen()
    {
        Carbon::setTestNow('2026-05-01 10:00:00');

        $admin = $this->adminWithTwoFactor();
        $user = User::factory()->create();
        $tweet = Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'admin deletable scheduled tweet',
            'scheduled_at' => Carbon::parse('2026-05-01 11:00:00'),
        ]);

        $this->actingAs($admin)
            ->delete(route('tweet.delete', $tweet), [
                'return_url' => route('admin.users.index', [], false),
            ])
            ->assertRedirect(route('admin.users.index', [], false));

        $this->assertDatabaseMissing('tweets', [
            'id' => $tweet->id,
        ]);
    }

    public function test_admin_without_two_factor_does_not_see_scheduled_tweet_delete_action()
    {
        Carbon::setTestNow('2026-05-01 10:00:00');

        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['name' => '2FA未設定予約ユーザー']);
        $tweet = Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'two factor required scheduled tweet',
            'scheduled_at' => Carbon::parse('2026-05-01 11:00:00'),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('two factor required scheduled tweet')
            ->assertDontSee('2FA有効化後に操作可');

        $this->assertStringNotContainsString(
            route('tweet.delete', ['tweetId' => $tweet->id]),
            $response->getContent()
        );
    }

    public function test_admin_without_two_factor_can_not_delete_scheduled_tweet_from_admin_screen()
    {
        Carbon::setTestNow('2026-05-01 10:00:00');

        $admin = User::factory()->create(['is_admin' => true]);
        $tweet = Tweet::factory()->create([
            'user_id' => $admin->id,
            'content' => 'blocked own scheduled tweet from admin screen',
            'scheduled_at' => Carbon::parse('2026-05-01 11:00:00'),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.users.index', [], false))
            ->delete(route('tweet.delete', $tweet), [
                'return_url' => route('admin.users.index', [], false),
            ])
            ->assertRedirect(route('admin.users.index', [], false))
            ->assertSessionHas('feedback.error', '管理者画面の予約投稿を操作するには、管理者自身の2段階認証を有効化してください');

        $this->assertDatabaseHas('tweets', [
            'id' => $tweet->id,
        ]);
    }

    public function test_admin_user_list_is_ordered_by_user_id()
    {
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Charlie Admin']);
        $firstUser = User::factory()->create(['name' => 'Zulu User']);
        $secondUser = User::factory()->create(['name' => 'Alpha User']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSeeInOrder([
                $admin->name,
                $firstUser->name,
                $secondUser->name,
            ]);
    }

    public function test_admin_user_list_displays_google_auth_status()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create([
            'name' => 'Google連携ユーザー',
            'google_id' => 'google-user-123',
        ]);
        User::factory()->create([
            'name' => '通常ユーザー',
            'google_id' => null,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Google連携')
            ->assertSee('Google連携ユーザー')
            ->assertSee('通常ユーザー');

        $html = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/Google連携ユーザー<\/td>.*?<span class="text-green-600 font-bold">✔<\/span>/s',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/通常ユーザー<\/td>.*?<td class="py-2 px-4 border-b text-center dark:border-gray-700">\s*<\/td>/s',
            $html
        );
    }

    public function test_admin_user_list_displays_two_factor_status()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create([
            'name' => '2FA有効ユーザー',
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt('ABCDEFGHIJKLMNOP'),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['recovery-code'])),
            'two_factor_confirmed_at' => now(),
        ]);
        User::factory()->create([
            'name' => '2FA無効ユーザー',
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('2FA')
            ->assertSee('2FA有効ユーザー')
            ->assertSee('2FA無効ユーザー');

        $html = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/2FA有効ユーザー<\/td>.*?<span class="text-green-600 font-bold">✔<\/span>/s',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/2FA無効ユーザー<\/td>.*?<td class="py-2 px-4 border-b text-center dark:border-gray-700">\s*<\/td>/s',
            $html
        );
    }

    public function test_admin_user_list_displays_notification_mail_status()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create([
            'name' => '通知受信ユーザー',
            'receives_notification_mail' => true,
        ]);
        User::factory()->create([
            'name' => '通知停止ユーザー',
            'receives_notification_mail' => false,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('通知メール')
            ->assertSee('通知受信ユーザー')
            ->assertSee('通知停止ユーザー')
            ->assertSee('停止');

        $html = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/通知受信ユーザー<\/td>.*?<span class="text-green-600 font-bold">✔<\/span>/s',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/通知停止ユーザー<\/td>.*?<span class="text-gray-400">停止<\/span>/s',
            $html
        );
    }

    public function test_admin_can_fetch_user_list_html_with_notification_mail_status()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create([
            'name' => '通知停止ユーザー',
            'receives_notification_mail' => false,
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.users.list'))
            ->assertOk()
            ->assertJsonStructure(['html']);

        $this->assertStringContainsString('通知停止ユーザー', $response->json('html'));
        $this->assertStringContainsString('停止', $response->json('html'));
    }

    public function test_admin_can_fetch_user_list_html_with_two_factor_status()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create([
            'name' => '2FA有効ユーザー',
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt('ABCDEFGHIJKLMNOP'),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['recovery-code'])),
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.users.list'))
            ->assertOk()
            ->assertJsonStructure(['html']);

        $this->assertStringContainsString('2FA有効ユーザー', $response->json('html'));
        $this->assertStringContainsString('text-green-600 font-bold', $response->json('html'));
    }

    public function test_admin_can_reset_user_two_factor_authentication()
    {
        $admin = $this->adminWithTwoFactor();
        $user = User::factory()->create([
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt('ABCDEFGHIJKLMNOP'),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['recovery-code'])),
            'two_factor_confirmed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.two-factor.reset', $user))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', '2段階認証をリセットしました');

        $user->refresh();

        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);
    }

    public function test_admin_can_not_reset_own_two_factor_authentication()
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt('ABCDEFGHIJKLMNOP'),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['recovery-code'])),
            'two_factor_confirmed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.two-factor.reset', $admin))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', '自分自身の2段階認証は管理者画面からリセットできません');

        $this->assertTrue($admin->refresh()->hasEnabledTwoFactorAuthentication());
    }

    public function test_admin_can_not_reset_seed_admin_two_factor_authentication()
    {
        $admin = $this->adminWithTwoFactor();
        $seedAdmin = User::factory()->create(array_merge([
            'is_admin' => true,
            'is_seed_admin' => true,
        ], $this->twoFactorEnabledAttributes()));

        $this->actingAs($admin)
            ->put(route('admin.users.two-factor.reset', $seedAdmin))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'Seederで作成した管理者の2段階認証はリセットできません');

        $this->assertTrue($seedAdmin->refresh()->hasEnabledTwoFactorAuthentication());
    }

    public function test_admin_can_update_user_email_and_send_verification_notification()
    {
        Notification::fake();

        $admin = $this->adminWithTwoFactor();
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.email.update', $user), [
                'email' => 'new@example.com',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'メールアドレスを変更し、認証メールを送信しました');

        $user->refresh();

        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_admin_without_two_factor_can_not_update_user_email()
    {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.email.update', $user), [
                'email' => 'new@example.com',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'ユーザー関連操作を行うには、管理者自身の2段階認証を有効化してください');

        $user->refresh();

        $this->assertSame('old@example.com', $user->email);
        $this->assertNotNull($user->email_verified_at);
        Notification::assertNothingSent();
    }

    public function test_admin_can_not_update_own_email_from_admin_screen()
    {
        Notification::fake();

        $admin = $this->adminWithTwoFactor([
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.email.update', $admin), [
                'email' => 'changed-admin@example.com',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', '自分自身のメールアドレスは管理者画面から変更できません');

        $this->assertSame('admin@example.com', $admin->refresh()->email);
        Notification::assertNothingSent();
    }

    public function test_admin_can_not_update_seed_admin_email()
    {
        Notification::fake();

        $admin = $this->adminWithTwoFactor();
        $seedAdmin = User::factory()->create([
            'is_admin' => true,
            'is_seed_admin' => true,
            'email' => 'seed@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.email.update', $seedAdmin), [
                'email' => 'changed-seed@example.com',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'Seederで作成した管理者のメールアドレスは変更できません');

        $this->assertSame('seed@example.com', $seedAdmin->refresh()->email);
        Notification::assertNothingSent();
    }

    public function test_admin_can_promote_non_admin_user()
    {
        $admin = $this->adminWithTwoFactor();
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($admin)
            ->put(route('admin.users.admin.update', $user), [
                'is_admin' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue($user->refresh()->is_admin);

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_admin_status_endpoint_reflects_promoted_user()
    {
        $admin = $this->adminWithTwoFactor();
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->getJson(route('account.admin.status'))
            ->assertOk()
            ->assertJson([
                'is_admin' => false,
                'has_two_factor_enabled' => false,
            ]);

        $this->actingAs($admin)
            ->put(route('admin.users.admin.update', $user), [
                'is_admin' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->actingAs($user->refresh())
            ->getJson(route('account.admin.status'))
            ->assertOk()
            ->assertJson([
                'is_admin' => true,
                'has_two_factor_enabled' => false,
            ]);
    }

    public function test_admin_status_endpoint_reflects_two_factor_enabled_status()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $enabledAdmin = $this->adminWithTwoFactor();

        $this->actingAs($admin)
            ->getJson(route('account.admin.status'))
            ->assertOk()
            ->assertJson([
                'is_admin' => true,
                'has_two_factor_enabled' => false,
            ]);

        $this->actingAs($enabledAdmin)
            ->getJson(route('account.admin.status'))
            ->assertOk()
            ->assertJson([
                'is_admin' => true,
                'has_two_factor_enabled' => true,
            ]);
    }

    public function test_tweet_index_renders_admin_nav_watch_for_non_admin_user()
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('tweet.index'))
            ->assertOk()
            ->assertSee('data-admin-nav-watch', false)
            ->assertSee(route('account.admin.status', [], false), false)
            ->assertDontSee('管理者画面');
    }

    public function test_admin_user_list_renders_admin_access_watch()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('data-admin-access-watch', false)
            ->assertSee(route('account.admin.status', [], false), false)
            ->assertSee(route('tweet.index', [], false), false);
    }

    public function test_admin_without_two_factor_sees_user_operation_warning_and_no_user_operation_buttons()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create(['is_admin' => false]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('ユーザー関連操作を行うには、管理者自身の2段階認証を有効化してください')
            ->assertDontSee('管理者にする')
            ->assertDontSee('2FAリセット')
            ->assertDontSee('削除');
    }

    public function test_admin_without_two_factor_can_not_manage_users()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($admin)
            ->put(route('admin.users.admin.update', $user), [
                'is_admin' => '1',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'ユーザー関連操作を行うには、管理者自身の2段階認証を有効化してください');

        $this->assertFalse($user->refresh()->is_admin);
    }

    public function test_admin_can_demote_other_non_seed_admin()
    {
        $admin = $this->adminWithTwoFactor();
        $targetAdmin = User::factory()->create(['is_admin' => true, 'is_seed_admin' => false]);

        $this->actingAs($admin)
            ->put(route('admin.users.admin.update', $targetAdmin), [
                'is_admin' => '0',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertFalse($targetAdmin->refresh()->is_admin);
    }

    public function test_admin_can_not_change_own_admin_role()
    {
        $admin = $this->adminWithTwoFactor();

        $this->actingAs($admin)
            ->put(route('admin.users.admin.update', $admin), [
                'is_admin' => '0',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', '自分自身の管理者権限は変更できません');

        $this->assertTrue($admin->refresh()->is_admin);
    }

    public function test_seed_admin_can_not_be_demoted()
    {
        $admin = $this->adminWithTwoFactor();
        $seedAdmin = User::factory()->create([
            'is_admin' => true,
            'is_seed_admin' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.admin.update', $seedAdmin), [
                'is_admin' => '0',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'Seederで作成した管理者は外せません');

        $this->assertTrue($seedAdmin->refresh()->is_admin);
    }

    public function test_seed_admin_is_overwritten_and_kept_to_one_user()
    {
        $seedAdmin = User::factory()->create([
            'name' => 'changed-admin',
            'email' => 'changed-admin@example.com',
            'is_admin' => true,
            'is_seed_admin' => true,
        ]);
        User::factory()->create([
            'is_admin' => true,
            'is_seed_admin' => true,
        ]);

        $this->seed(UsersSeeder::class);

        $seedAdmin->refresh();

        $this->assertSame(config('seed.admin.name'), $seedAdmin->name);
        $this->assertSame(config('seed.admin.email'), $seedAdmin->email);
        $this->assertTrue($seedAdmin->is_admin);
        $this->assertTrue($seedAdmin->is_seed_admin);
        $this->assertSame(1, User::where('is_seed_admin', true)->count());
    }

    public function test_same_name_non_seed_user_is_not_overwritten_by_users_seeder()
    {
        $sameNameUser = User::factory()->create([
            'name' => config('seed.admin.name'),
            'email' => 'same-name@example.com',
            'is_admin' => false,
            'is_seed_admin' => false,
        ]);

        try {
            $this->seed(UsersSeeder::class);
            $this->fail('UsersSeeder should stop when seed admin name conflicts with a non-seed user.');
        } catch (RuntimeException $e) {
            $this->assertSame('Seeder固定管理者のユーザー名またはメールアドレスが既存ユーザーと重複しています。', $e->getMessage());
        }

        $sameNameUser->refresh();

        $this->assertSame(config('seed.admin.name'), $sameNameUser->name);
        $this->assertSame('same-name@example.com', $sameNameUser->email);
        $this->assertFalse($sameNameUser->is_admin);
        $this->assertFalse($sameNameUser->is_seed_admin);
        $this->assertSame(0, User::where('is_seed_admin', true)->count());
    }

    public function test_users_seeder_uses_seed_admin_config_values()
    {
        config([
            'seed.admin.name' => 'env-admin',
            'seed.admin.email' => 'env-admin@example.com',
            'seed.admin.password' => 'env-password',
        ]);

        $this->seed(UsersSeeder::class);

        $seedAdmin = User::where('is_seed_admin', true)->firstOrFail();

        $this->assertSame('env-admin', $seedAdmin->name);
        $this->assertSame('env-admin@example.com', $seedAdmin->email);
        $this->assertTrue(password_verify('env-password', $seedAdmin->password));
        $this->assertTrue($seedAdmin->is_admin);
    }

    public function test_admin_user_can_not_be_deleted()
    {
        $admin = $this->adminWithTwoFactor();
        $targetAdmin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $targetAdmin))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', '管理者は削除できません');

        $this->assertDatabaseHas('users', ['id' => $targetAdmin->id]);
    }

    public function test_admin_can_request_non_admin_user_deletion()
    {
        Queue::fake();

        $admin = $this->adminWithTwoFactor();
        $user = User::factory()->create(['is_admin' => false]);
        Tweet::factory()->create(['user_id' => $user->id]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'ユーザー削除を受け付けました');

        $this->assertNotNull($user->refresh()->deletion_requested_at);
        $this->assertDatabaseHas('tweets', ['user_id' => $user->id]);
        Queue::assertPushed(DeleteUserJob::class, fn (DeleteUserJob $job) => $job->userId === $user->id);
    }

    public function test_non_admin_can_not_fetch_stats_json()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('admin.users.stats'))
            ->assertForbidden();
    }

    public function test_non_admin_can_not_fetch_user_list_html_json()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('admin.users.list'))
            ->assertForbidden();
    }
}
