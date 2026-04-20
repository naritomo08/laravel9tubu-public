<?php

namespace Tests\Feature;

use App\Models\Tweet;
use App\Models\User;
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
