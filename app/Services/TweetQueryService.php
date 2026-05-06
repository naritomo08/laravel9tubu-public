<?php

namespace App\Services;

use App\Models\Like;
use App\Models\Tweet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class TweetQueryService
{
    public const TWEETS_PER_PAGE = 50;
    public const LATEST_TWEETS_LIMIT = self::TWEETS_PER_PAGE;

    public function getTweets(int $page = 1): LengthAwarePaginator
    {
        $tweets = Tweet::with(['user', 'images'])
            ->withCount(['activeLikes as likes_count'])
            ->visibleTo(Auth::user())
            ->orderByRaw('COALESCE(scheduled_at, created_at) DESC')
            ->orderBy('id', 'DESC')
            ->paginate(self::TWEETS_PER_PAGE, ['*'], 'page', $page);
        $this->attachLikeAttributes($tweets->getCollection());

        return $tweets;
    }

    public function getTweetsNewerThan(int $tweetId)
    {
        $tweets = Tweet::with(['user', 'images'])
            ->withCount(['activeLikes as likes_count'])
            ->visibleTo(Auth::user())
            ->where('id', '>', $tweetId)
            ->orderBy('id', 'DESC')
            ->limit(self::LATEST_TWEETS_LIMIT)
            ->get();

        $this->attachLikeAttributes($tweets);

        return $tweets;
    }

    public function getChangedTweets(array $tweetVersions)
    {
        if (empty($tweetVersions)) {
            return collect();
        }

        $tweets = Tweet::with(['user', 'images'])
            ->withCount(['activeLikes as likes_count'])
            ->visibleTo(Auth::user())
            ->whereIn('id', array_keys($tweetVersions))
            ->get()
            ->filter(function ($tweet) use ($tweetVersions) {
                return $this->getTweetVersion($tweet) !== ($tweetVersions[$tweet->id] ?? null);
            })
            ->values();

        $this->attachLikeAttributes($tweets);

        return $tweets;
    }

    public function searchTweets(string $query, bool $userSearch = false, int $page = 1, ?int $userId = null): LengthAwarePaginator
    {
        $keyword = trim(preg_replace('/\s+/u', ' ', $query) ?? $query);

        if ((! $userSearch && $keyword === '') || ($userSearch && ! $userId)) {
            $tweets = Tweet::whereRaw('0 = 1')
                ->paginate(self::TWEETS_PER_PAGE, ['*'], 'page', $page);

            $this->attachLikeAttributes($tweets->getCollection());

            return $tweets;
        }

        $tweets = Tweet::with(['user', 'images'])
            ->withCount(['activeLikes as likes_count'])
            ->visibleTo(Auth::user())
            ->when(! $userSearch, function ($tweetQuery) use ($keyword) {
                $tweetQuery->where('content', 'like', '%'.$keyword.'%');
            })
            ->when($userSearch && $userId, function ($tweetQuery) use ($userId) {
                $tweetQuery->where('user_id', $userId);
            })
            ->orderByRaw('COALESCE(scheduled_at, created_at) DESC')
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
        $tweetIds = $tweets->pluck('id')->all();
        $likedTweetIdMap = $userId && ! empty($tweetIds)
            ? Like::where('user_id', $userId)
                ->whereIn('tweet_id', $tweetIds)
                ->pluck('tweet_id')
                ->flip()
            : collect();

        $tweets->each(function ($tweet) use ($likedTweetIdMap) {
            $tweet->is_liked = $likedTweetIdMap->has($tweet->id);
            $tweet->like_count = (int) ($tweet->likes_count ?? 0);
        });
    }
}
