<?php

namespace App\Services;

use App\Models\Like;
use App\Models\Tweet;
use Illuminate\Support\Facades\Auth;

class LikeService
{
    /**
     * いいねを付ける or 外す
     * 既にいいねしていれば解除、なければ付ける
     *
     * @param int $tweetId
     * @return bool いいね状態（true: いいね中, false: いいね解除）
     */
    public function toggleLike(int $tweetId): bool
    {
        $userId = Auth::id();
        
        $existingLike = Like::where('user_id', $userId)
            ->where('tweet_id', $tweetId)
            ->first();

        if ($existingLike) {
            // いいねを解除
            $existingLike->delete();
            return false;
        } else {
            // いいねを付ける
            $like = new Like();
            $like->user_id = $userId;
            $like->tweet_id = $tweetId;
            $like->save();
            return true;
        }
    }

    /**
     * 特定の投稿のいいね数を取得
     *
     * @param int $tweetId
     * @return int
     */
    public function getLikeCount(int $tweetId): int
    {
        return Like::where('tweet_id', $tweetId)
            ->whereHas('user', fn ($query) => $query->notPendingDeletion())
            ->count();
    }

    /**
     * 複数投稿のいいね数とログインユーザーのいいね状態を取得
     *
     * @param array<int> $tweetIds
     * @param int|null $userId
     * @return array<int, array{like_count: int, is_liked: bool}>
     */
    public function getStatuses(array $tweetIds, ?int $userId = null): array
    {
        if (empty($tweetIds)) {
            return [];
        }

        $existingTweetIds = Tweet::whereIn('id', $tweetIds)
            ->visibleTo(Auth::user())
            ->pluck('id')
            ->all();

        if (empty($existingTweetIds)) {
            return [];
        }

        $existingTweetIdMap = array_flip($existingTweetIds);

        $likeCounts = Like::selectRaw('tweet_id, count(*) as like_count')
            ->whereIn('tweet_id', $existingTweetIds)
            ->whereHas('user', fn ($query) => $query->notPendingDeletion())
            ->groupBy('tweet_id')
            ->pluck('like_count', 'tweet_id');

        $likedTweetIds = $userId
            ? Like::where('user_id', $userId)
                ->whereIn('tweet_id', $existingTweetIds)
                ->whereHas('user', fn ($query) => $query->notPendingDeletion())
                ->pluck('tweet_id')
                ->all()
            : [];

        $likedTweetIdMap = array_flip($likedTweetIds);
        $statuses = [];

        foreach ($tweetIds as $tweetId) {
            if (!isset($existingTweetIdMap[$tweetId])) {
                continue;
            }

            $statuses[$tweetId] = [
                'like_count' => (int) ($likeCounts[$tweetId] ?? 0),
                'is_liked' => isset($likedTweetIdMap[$tweetId]),
            ];
        }

        return $statuses;
    }

    /**
     * 特定の投稿をユーザーがいいねしているかどうか
     *
     * @param int $tweetId
     * @param int|null $userId
     * @return bool
     */
    public function isLiked(int $tweetId, ?int $userId = null): bool
    {
        if (!$userId) {
            $userId = Auth::id();
        }

        if (!$userId) {
            return false;
        }

        return Like::where('user_id', $userId)
            ->whereHas('user', fn ($query) => $query->notPendingDeletion())
            ->where('tweet_id', $tweetId)
            ->exists();
    }

    /**
     * 特定のユーザーのすべてのいいねを削除（ユーザー削除時）
     *
     * @param int $userId
     * @return void
     */
    public function deleteLikesByUser(int $userId)
    {
        Like::where('user_id', $userId)->delete();
    }
}
