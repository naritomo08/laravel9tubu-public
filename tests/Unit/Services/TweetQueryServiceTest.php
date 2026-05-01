<?php

namespace Tests\Unit\Services;

use App\Models\Like;
use App\Models\Tweet;
use App\Models\User;
use App\Services\TweetQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TweetQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_tweets_attaches_like_attributes_without_per_tweet_like_queries(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $tweets = Tweet::factory()->count(3)->create([
            'user_id' => $author->id,
        ]);

        Like::create(['user_id' => $viewer->id, 'tweet_id' => $tweets[0]->id]);
        Like::create(['user_id' => $author->id, 'tweet_id' => $tweets[0]->id]);
        Like::create(['user_id' => $author->id, 'tweet_id' => $tweets[1]->id]);

        $this->actingAs($viewer);

        DB::enableQueryLog();

        $result = (new TweetQueryService)->getTweets();

        $queries = collect(DB::getQueryLog())
            ->pluck('query')
            ->map(fn (string $query) => str_replace(['`', '"'], '', $query));

        $this->assertCount(3, $result->items());
        $this->assertTrue($result->getCollection()->firstWhere('id', $tweets[0]->id)->is_liked);
        $this->assertSame(2, $result->getCollection()->firstWhere('id', $tweets[0]->id)->like_count);
        $this->assertFalse($result->getCollection()->firstWhere('id', $tweets[1]->id)->is_liked);
        $this->assertSame(1, $result->getCollection()->firstWhere('id', $tweets[1]->id)->like_count);
        $this->assertTrue(
            $queries->contains(fn (string $query) => str_contains($query, 'select count(*) from likes')),
            'Expected withCount("likes") to provide like counts.'
        );
        $this->assertFalse(
            $queries->contains(fn (string $query) => str_contains($query, 'exists') && str_contains($query, 'likes')),
            'Like status should be loaded in bulk instead of via per-tweet exists queries.'
        );
    }
}
