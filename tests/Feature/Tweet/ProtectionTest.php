<?php

namespace Tests\Feature\Tweet;

use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_admin_can_protect_non_seed_admin_tweet_from_list()
    {
        $seedAdmin = User::factory()->create([
            'is_admin' => true,
            'is_seed_admin' => true,
        ]);
        $user = User::factory()->create();
        $tweet = Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'protected target',
            'is_protected' => false,
        ]);

        $this->actingAs($seedAdmin)
            ->from('/tweet')
            ->put(route('tweet.protection', $tweet), [
                'is_protected' => '1',
            ])
            ->assertRedirect('/tweet')
            ->assertSessionHas('feedback.success', 'つぶやきを保護しました');

        $this->assertTrue($tweet->refresh()->is_protected);

        $this->actingAs($seedAdmin)
            ->get('/tweet')
            ->assertOk()
            ->assertSee('protected target')
            ->assertSee('保護');
    }

    public function test_non_seed_admin_can_not_protect_tweet()
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_seed_admin' => false,
        ]);
        $user = User::factory()->create();
        $tweet = Tweet::factory()->create([
            'user_id' => $user->id,
            'is_protected' => false,
        ]);

        $this->actingAs($admin)
            ->put(route('tweet.protection', $tweet), [
                'is_protected' => '1',
            ])
            ->assertForbidden();

        $this->assertFalse($tweet->refresh()->is_protected);
    }

    public function test_seed_admin_tweet_can_not_be_protected()
    {
        $seedAdmin = User::factory()->create([
            'is_admin' => true,
            'is_seed_admin' => true,
        ]);
        $tweet = Tweet::factory()->create([
            'user_id' => $seedAdmin->id,
            'is_protected' => false,
        ]);

        $this->actingAs($seedAdmin)
            ->from('/tweet')
            ->put(route('tweet.protection', $tweet), [
                'is_protected' => '1',
            ])
            ->assertRedirect('/tweet')
            ->assertSessionHas('feedback.error', 'Seeder管理者のつぶやきは保護設定の対象外です');

        $this->assertFalse($tweet->refresh()->is_protected);
    }

    public function test_protected_tweet_can_not_be_edited_or_deleted_by_non_seed_admins()
    {
        $author = User::factory()->create();
        $admin = User::factory()->create([
            'is_admin' => true,
            'is_seed_admin' => false,
        ]);
        $tweet = Tweet::factory()->create([
            'user_id' => $author->id,
            'content' => 'before',
            'is_protected' => true,
        ]);

        $this->actingAs($author)
            ->get('/tweet/update/' . $tweet->id)
            ->assertForbidden();

        $this->actingAs($author)
            ->put('/tweet/update/' . $tweet->id, [
                'tweet' => 'after',
            ])
            ->assertForbidden();

        $this->actingAs($author)
            ->delete('/tweet/delete/' . $tweet->id)
            ->assertSessionHas('feedback.error', '保護されたつぶやきは削除できません');

        $this->actingAs($admin)
            ->delete('/tweet/delete/' . $tweet->id)
            ->assertSessionHas('feedback.error', '保護されたつぶやきは削除できません');

        $this->assertDatabaseHas('tweets', [
            'id' => $tweet->id,
            'content' => 'before',
        ]);
    }

    public function test_seed_admin_can_delete_but_not_edit_protected_tweet()
    {
        $seedAdmin = User::factory()->create([
            'is_admin' => true,
            'is_seed_admin' => true,
        ]);
        $author = User::factory()->create();
        $tweet = Tweet::factory()->create([
            'user_id' => $author->id,
            'content' => 'before',
            'is_protected' => true,
        ]);

        $this->actingAs($seedAdmin)
            ->put('/tweet/update/' . $tweet->id, [
                'tweet' => 'after',
            ])
            ->assertForbidden();

        $this->assertSame('before', $tweet->refresh()->content);

        $this->actingAs($seedAdmin)
            ->delete('/tweet/delete/' . $tweet->id)
            ->assertRedirect('/tweet?page=1');

        $this->assertDatabaseMissing('tweets', ['id' => $tweet->id]);
    }

    public function test_seed_admin_menu_does_not_show_edit_for_protected_tweet()
    {
        $seedAdmin = User::factory()->create([
            'is_admin' => true,
            'is_seed_admin' => true,
        ]);
        $author = User::factory()->create();
        $tweet = Tweet::factory()->create([
            'user_id' => $author->id,
            'content' => 'protected menu target',
            'is_protected' => true,
        ]);

        $response = $this->actingAs($seedAdmin)
            ->get('/tweet')
            ->assertOk()
            ->assertSee('protected menu target')
            ->assertSee('保護解除')
            ->assertSee('削除');

        $this->assertStringNotContainsString(
            route('tweet.update.index', ['tweetId' => $tweet->id]),
            $response->getContent()
        );
    }
}
