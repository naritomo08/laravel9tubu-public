<?php

namespace App\Services;

use App\Models\Tweet;
use App\Models\Like;
use Carbon\Carbon;
use App\Models\Image;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TweetService
{
    public const TWEETS_PER_PAGE = 50;

    public function getTweets(int $page = 1): LengthAwarePaginator
    {
        $tweets = Tweet::with(['user', 'images', 'likes'])
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->paginate(self::TWEETS_PER_PAGE, ['*'], 'page', $page);
        $this->attachLikeAttributes($tweets->getCollection());
        
        return $tweets;
    }

    public function getTweetsNewerThan(int $tweetId)
    {
        $tweets = Tweet::with(['user', 'images', 'likes'])
            ->where('id', '>', $tweetId)
            ->orderBy('id', 'DESC')
            ->get();

        $this->attachLikeAttributes($tweets);
        
        return $tweets;
    }

    public function getChangedTweets(array $tweetVersions)
    {
        if (empty($tweetVersions)) {
            return collect();
        }

        $tweets = Tweet::with(['user', 'images', 'likes'])
            ->whereIn('id', array_keys($tweetVersions))
            ->get()
            ->filter(function ($tweet) use ($tweetVersions) {
                return $this->getTweetVersion($tweet) !== ($tweetVersions[$tweet->id] ?? null);
            })
            ->values();

        $this->attachLikeAttributes($tweets);

        return $tweets;
    }

    public function searchTweets(string $query, bool $userSearch = false, int $page = 1): LengthAwarePaginator
    {
        $keyword = trim(preg_replace('/\s+/u', ' ', $query) ?? $query);

        if ($keyword === '') {
            $tweets = Tweet::whereRaw('0 = 1')
                ->paginate(self::TWEETS_PER_PAGE, ['*'], 'page', $page);

            $this->attachLikeAttributes($tweets->getCollection());

            return $tweets;
        }

        $tweets = Tweet::with(['user', 'images', 'likes'])
            ->when(!$userSearch, function ($tweetQuery) use ($keyword) {
                $tweetQuery->where('content', 'like', '%' . $keyword . '%');
            })
            ->when($userSearch, function ($tweetQuery) use ($keyword) {
                $tweetQuery->whereHas('user', function ($userQuery) use ($keyword) {
                    $userQuery->where('name', $keyword);
                });
            })
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->paginate(self::TWEETS_PER_PAGE, ['*'], 'page', $page);

        $this->attachLikeAttributes($tweets->getCollection());

        return $tweets;
    }

    private function getTweetVersion(Tweet $tweet): string
    {
        return $tweet->version();
    }

    private function attachLikeAttributes($tweets): void
    {
        $userId = Auth::id();
        $tweets->each(function ($tweet) use ($userId) {
            $tweet->is_liked = $userId ? $tweet->likes()->where('user_id', $userId)->exists() : false;
            $tweet->like_count = $tweet->likes()->count();
        });
    }

    // 自分のtweetかどうかをチェックするメソッド
    public function checkOwnTweet(int $userId, int $tweetId): bool
    {
        $tweet = Tweet::where('id', $tweetId)->first();
        if (!$tweet) {
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
    public function saveTweet(int $userId, string $content, array $images)
    {
        DB::transaction(function () use ($userId, $content, $images) {
            $tweet = new Tweet;
            $tweet->user_id = $userId;
            $tweet->content = $content;
            $tweet->save();
            foreach ($images as $image) {
                $this->attachImage($tweet, $image);
            }
        });
    }

    public function updateTweet(int $tweetId, string $content, array $images = [], array $deleteImageIds = []): void
    {
        DB::transaction(function () use ($tweetId, $content, $images, $deleteImageIds) {
            $tweet = Tweet::with('images')->where('id', $tweetId)->firstOrFail();
            $tweet->content = $content;
            $tweet->save();

            $tweet->images
                ->whereIn('id', $deleteImageIds)
                ->each(function ($image) use ($tweet) {
                    $this->deleteImage($tweet, $image);
                });

            foreach ($images as $image) {
                $this->attachImage($tweet, $image);
            }
        });
    }

    public function deleteTweet(int $tweetId)
    {
        DB::transaction(function () use ($tweetId) {
            $tweet = Tweet::where('id', $tweetId)->firstOrFail();
            $tweet->images()->each(function ($image) use ($tweet){
                $this->deleteImage($tweet, $image);
            });
    
            $tweet->delete();
        });
    }

    private function attachImage(Tweet $tweet, $image): void
    {
        $path = Storage::disk('public')->putFile('images', $image);
        $imageModel = new Image();
        $imageModel->name = basename($path);
        $imageModel->save();
        $tweet->images()->attach($imageModel->id);
    }

    private function deleteImage(Tweet $tweet, Image $image): void
    {
        $filePath = 'images/' . $image->name;
        if (Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }
        $tweet->images()->detach($image->id);
        $image->delete();
    }
}
