<?php

namespace Tests\Unit\Services;

use App\Models\Like;
use App\Models\Tweet;
use App\Models\User;
use App\Services\LikeService;
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
        $pendingDeletionUser = User::factory()->create([
            'deletion_requested_at' => now(),
        ]);
        $tweets = Tweet::factory()->count(3)->create([
            'user_id' => $author->id,
        ]);

        Like::create(['user_id' => $viewer->id, 'tweet_id' => $tweets[0]->id]);
        Like::create(['user_id' => $author->id, 'tweet_id' => $tweets[0]->id]);
        Like::create(['user_id' => $pendingDeletionUser->id, 'tweet_id' => $tweets[0]->id]);
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
            'Expected withCount("activeLikes as likes_count") to provide like counts.'
        );
        $this->assertTrue(
            $queries->contains(fn (string $query) => str_contains($query, 'deletion_requested_at is null')),
            'Expected like counts to exclude pending-deletion users.'
        );
        $this->assertFalse(
            $queries->contains(fn (string $query) => str_starts_with($query, 'select exists') && str_contains($query, 'likes')),
            'Like status should be loaded in bulk instead of via per-tweet exists queries.'
        );
    }

    public function test_tweet_queries_and_like_status_count_only_active_user_likes(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $pendingDeletionUser = User::factory()->create([
            'deletion_requested_at' => now(),
        ]);
        $tweet = Tweet::factory()->create([
            'user_id' => $author->id,
            'content' => 'active like count target',
        ]);

        Like::create(['user_id' => $viewer->id, 'tweet_id' => $tweet->id]);
        Like::create(['user_id' => $pendingDeletionUser->id, 'tweet_id' => $tweet->id]);

        $this->actingAs($viewer);

        $tweetQueryService = new TweetQueryService;
        $likeService = new LikeService;

        $listTweet = $tweetQueryService->getTweets()->getCollection()->firstWhere('id', $tweet->id);
        $latestTweet = $tweetQueryService->getTweetsNewerThan(0)->firstWhere('id', $tweet->id);
        $searchTweet = $tweetQueryService->searchTweets('active like count target')->getCollection()->firstWhere('id', $tweet->id);
        $status = $likeService->getStatuses([$tweet->id], $viewer->id)[$tweet->id];

        $this->assertSame(1, $listTweet->like_count);
        $this->assertSame(1, $latestTweet->like_count);
        $this->assertSame(1, $searchTweet->like_count);
        $this->assertSame(1, $status['like_count']);
    }
}
