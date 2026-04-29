<?php

namespace Tests\Feature\Admin;

use Database\Seeders\UsersSeeder;
use App\Models\Like;
use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_list_displays_emails_without_update_form()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['email' => 'old@example.com']);

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('old@example.com');

        $this->assertStringNotContainsString('name="email"', $response->getContent());
        $this->assertStringNotContainsString('/admin/users/'.$user->id.'/email', $response->getContent());
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
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create(['name' => '動的更新ユーザー']);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.users.list'))
            ->assertOk()
            ->assertJsonStructure(['html']);

        $this->assertStringContainsString('動的更新ユーザー', $response->json('html'));
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

    public function test_admin_can_promote_non_admin_user()
    {
        $admin = User::factory()->create(['is_admin' => true]);
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
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->getJson(route('account.admin.status'))
            ->assertOk()
            ->assertJson(['is_admin' => false]);

        $this->actingAs($admin)
            ->put(route('admin.users.admin.update', $user), [
                'is_admin' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->actingAs($user->refresh())
            ->getJson(route('account.admin.status'))
            ->assertOk()
            ->assertJson(['is_admin' => true]);
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

    public function test_admin_can_demote_other_non_seed_admin()
    {
        $admin = User::factory()->create(['is_admin' => true]);
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
        $admin = User::factory()->create(['is_admin' => true]);

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
        $admin = User::factory()->create(['is_admin' => true]);
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

        $this->assertSame('admin', $seedAdmin->name);
        $this->assertSame('admin@tubuyaki.com', $seedAdmin->email);
        $this->assertTrue($seedAdmin->is_admin);
        $this->assertTrue($seedAdmin->is_seed_admin);
        $this->assertSame(1, User::where('is_seed_admin', true)->count());
    }

    public function test_same_name_user_is_overwritten_by_users_seeder()
    {
        $sameNameUser = User::factory()->create([
            'name' => 'admin',
            'email' => 'same-name@example.com',
            'is_admin' => false,
            'is_seed_admin' => false,
        ]);

        $this->seed(UsersSeeder::class);

        $sameNameUser->refresh();

        $this->assertSame('admin', $sameNameUser->name);
        $this->assertSame('admin@tubuyaki.com', $sameNameUser->email);
        $this->assertTrue($sameNameUser->is_admin);
        $this->assertTrue($sameNameUser->is_seed_admin);
        $this->assertSame(1, User::where('is_seed_admin', true)->count());
    }

    public function test_admin_user_can_not_be_deleted()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $targetAdmin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $targetAdmin))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', '管理者は削除できません');

        $this->assertDatabaseHas('users', ['id' => $targetAdmin->id]);
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
