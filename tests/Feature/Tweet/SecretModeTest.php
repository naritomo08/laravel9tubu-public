<?php

namespace Tests\Feature\Tweet;

use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecretModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_secret_tweet_is_visible_to_author_and_admin_but_hidden_from_others()
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $secretTweet = Tweet::factory()->create([
            'user_id' => $author->id,
            'content' => 'secret tweet body',
            'is_secret' => true,
        ]);

        $this->get('/tweet')
            ->assertOk()
            ->assertDontSee('secret tweet body');

        $this->actingAs($viewer)->get('/tweet')
            ->assertOk()
            ->assertDontSee('secret tweet body');

        $this->actingAs($author)->get('/tweet')
            ->assertOk()
            ->assertSee('secret tweet body')
            ->assertSee('シークレット');

        $this->actingAs($admin)->get('/tweet')
            ->assertOk()
            ->assertSee('secret tweet body')
            ->assertSee('シークレット');
    }

    public function test_create_and_update_save_secret_mode()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/tweet/create', [
            'tweet' => 'created secret',
            'is_secret' => '1',
        ])->assertRedirect('/tweet?page=1');

        $tweet = Tweet::where('content', 'created secret')->firstOrFail();
        $this->assertTrue($tweet->is_secret);

        $this->actingAs($user)->put('/tweet/update/' . $tweet->id, [
            'tweet' => 'updated public',
            'is_secret' => '0',
        ])->assertRedirect('/tweet?page=1');

        $tweet->refresh();
        $this->assertSame('updated public', $tweet->content);
        $this->assertFalse($tweet->is_secret);
    }

    public function test_search_and_latest_do_not_return_secret_tweets_to_other_users()
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $secretTweet = Tweet::factory()->create([
            'user_id' => $author->id,
            'content' => 'hidden searchable keyword',
            'is_secret' => true,
        ]);

        $searchResponse = $this->actingAs($viewer)->getJson('/tweet/search/results?q=hidden');
        $latestResponse = $this->actingAs($viewer)->getJson('/tweet/latest?' . http_build_query([
            'after_id' => 0,
            'tweet_versions' => json_encode([
                $secretTweet->id => $secretTweet->version(),
            ]),
        ]));

        $searchResponse->assertOk()
            ->assertJsonPath('count', 0);
        $this->assertStringNotContainsString('hidden searchable keyword', (string) $searchResponse->json('html'));

        $latestResponse->assertOk();
        $this->assertStringNotContainsString('hidden searchable keyword', (string) $latestResponse->json('html'));
        $this->assertSame([], $latestResponse->json('updated_html'));
    }

    public function test_like_status_removes_secret_tweets_hidden_from_viewer()
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $secretTweet = Tweet::factory()->create([
            'user_id' => $author->id,
            'is_secret' => true,
        ]);

        $this->actingAs($viewer)->getJson('/like/status?tweet_ids=' . $secretTweet->id)
            ->assertOk()
            ->assertJsonPath('likes', []);

        $this->actingAs($viewer)->postJson('/like', [
            'tweet_id' => $secretTweet->id,
        ])->assertNotFound();
    }
}
