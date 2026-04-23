<?php

namespace Tests\Feature\Admin;

use App\Models\Like;
use App\Models\Tweet;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_user_email()
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
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();

        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_admin_can_not_update_user_email_to_existing_email()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['email' => 'old@example.com']);
        $otherUser = User::factory()->create(['email' => 'existing@example.com']);

        $this->actingAs($admin)
            ->put(route('admin.users.email.update', $user), [
                'email' => $otherUser->email,
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame('old@example.com', $user->refresh()->email);
    }

    public function test_non_admin_can_not_update_user_email()
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create(['email' => 'old@example.com']);

        $this->actingAs($user)
            ->put(route('admin.users.email.update', $targetUser), [
                'email' => 'new@example.com',
            ])
            ->assertForbidden();

        $this->assertSame('old@example.com', $targetUser->refresh()->email);
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

    public function test_non_admin_can_not_fetch_stats_json()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('admin.users.stats'))
            ->assertForbidden();
    }
}
