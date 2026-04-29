<?php

namespace Tests\Feature\Tweet;

use Database\Seeders\MarkSeededTweetsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Tweet;

class DeleteTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_delete_successed()
    {
        $user = User::factory()->create();

        $tweet = Tweet::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->delete('/tweet/delete/' . $tweet->id);

        $response->assertRedirect('/tweet?page=1');
    }

    public function test_delete_redirects_back_to_search_page_with_feedback()
    {
        $user = User::factory()->create();
        $tweet = Tweet::factory()->create(['user_id' => $user->id]);
        $returnUrl = '/tweet/search?' . http_build_query([
            'q' => 'keyword',
            'user_search' => 1,
            'page' => 3,
        ]);

        $response = $this->actingAs($user)->delete('/tweet/delete/' . $tweet->id, [
            'page' => 3,
            'return_url' => $returnUrl,
        ]);

        $response->assertRedirect($returnUrl)
            ->assertSessionHas('feedback.success', 'つぶやきを削除しました');
    }

    public function test_admin_can_not_delete_seeded_tweet()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $tweet = Tweet::factory()->create([
            'user_id' => $admin->id,
            'is_seeded' => true,
        ]);

        $response = $this->actingAs($admin)->delete('/tweet/delete/' . $tweet->id);

        $response->assertSessionHas('feedback.error', 'Seederで作成したつぶやきは削除できません');
        $this->assertDatabaseHas('tweets', ['id' => $tweet->id]);
    }

    public function test_seed_admin_can_delete_own_seeded_tweet()
    {
        $seedAdmin = User::factory()->create([
            'is_admin' => true,
            'is_seed_admin' => true,
        ]);
        $tweet = Tweet::factory()->create([
            'user_id' => $seedAdmin->id,
            'is_seeded' => true,
        ]);

        $response = $this->actingAs($seedAdmin)->delete('/tweet/delete/' . $tweet->id);

        $response->assertRedirect('/tweet?page=1')
            ->assertSessionHas('feedback.success', 'つぶやきを削除しました');
        $this->assertDatabaseMissing('tweets', ['id' => $tweet->id]);
    }

    public function test_mark_seeded_tweets_seeder_marks_seed_admin_tweets_without_ids()
    {
        $seedAdmin = User::factory()->create([
            'is_admin' => true,
            'is_seed_admin' => true,
        ]);
        $user = User::factory()->create();
        $seedAdminTweet = Tweet::factory()->create([
            'user_id' => $seedAdmin->id,
            'is_seeded' => false,
        ]);
        $userTweet = Tweet::factory()->create([
            'user_id' => $user->id,
            'is_seeded' => false,
        ]);

        $this->seed(MarkSeededTweetsSeeder::class);

        $this->assertTrue($seedAdminTweet->refresh()->is_seeded);
        $this->assertFalse($userTweet->refresh()->is_seeded);
    }
}
