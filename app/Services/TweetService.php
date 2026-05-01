<?php

namespace App\Services;

use App\Models\Tweet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TweetService
{
    public function __construct(
        private readonly TweetImageService $tweetImageService,
    ) {}

    // 自分のtweetかどうかをチェックするメソッド
    public function checkOwnTweet(int $userId, int $tweetId): bool
    {
        $tweet = Tweet::where('id', $tweetId)->first();
        if (! $tweet) {
            return false;
        }

        return $tweet->user_id === $userId;
    }

    public function countYesterdayTweets(): int
    {
        return Tweet::whereDate('created_at', '>=', Carbon::yesterday()->toDateTimeString())
            ->whereDate('created_at', '<', Carbon::today()->toDateTimeString())
            ->count();
    }

    public function saveTweet(int $userId, string $content, array $images, bool $isSecret = false)
    {
        DB::transaction(function () use ($userId, $content, $images, $isSecret) {
            $tweet = new Tweet;
            $tweet->user_id = $userId;
            $tweet->content = $content;
            $tweet->is_secret = $isSecret;
            $tweet->save();
            foreach ($images as $image) {
                $this->tweetImageService->attachImage($tweet, $image);
            }
        });
    }

    public function updateTweet(int $tweetId, string $content, array $images = [], array $deleteImageIds = [], bool $isSecret = false): void
    {
        DB::transaction(function () use ($tweetId, $content, $images, $deleteImageIds, $isSecret) {
            $tweet = Tweet::with('images')->where('id', $tweetId)->firstOrFail();
            $tweet->content = $content;
            $tweet->is_secret = $isSecret;
            $tweet->save();

            $tweet->images
                ->whereIn('id', $deleteImageIds)
                ->each(function ($image) use ($tweet) {
                    $this->tweetImageService->deleteImage($tweet, $image);
                });

            foreach ($images as $image) {
                $this->tweetImageService->attachImage($tweet, $image);
            }
        });
    }

    public function deleteTweet(int $tweetId, bool $allowSeeded = false)
    {
        DB::transaction(function () use ($tweetId, $allowSeeded) {
            $tweet = Tweet::where('id', $tweetId)->firstOrFail();
            if ($tweet->is_seeded && ! $allowSeeded) {
                return;
            }

            $tweet->images()->each(function ($image) use ($tweet) {
                $this->tweetImageService->deleteImage($tweet, $image);
            });

            $tweet->delete();
        });
    }
}
