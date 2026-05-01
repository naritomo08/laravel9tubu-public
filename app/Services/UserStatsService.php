<?php

namespace App\Services;

use App\Models\Like;
use App\Models\Tweet;
use App\Models\User;
use Illuminate\Support\Collection;

class UserStatsService
{
    public function getUsersWithStats(): Collection
    {
        return User::query()
            ->withCount('tweets')
            ->addSelect([
                'received_likes_count' => Like::query()
                    ->selectRaw('count(*)')
                    ->join('tweets', 'likes.tweet_id', '=', 'tweets.id')
                    ->whereColumn('tweets.user_id', 'users.id'),
            ])
            ->orderBy('id')
            ->get();
    }

    public function buildAdminStatsPayload(Collection $users): array
    {
        return [
            'totals' => [
                'label' => 'トータル',
                'tweet_count' => Tweet::count(),
                'like_count' => Like::count(),
            ],
            'users' => $users->map(function (User $user): array {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'tweet_count' => (int) $user->tweets_count,
                    'like_count' => (int) ($user->received_likes_count ?? 0),
                ];
            })->values()->all(),
        ];
    }

    public function buildAccountStatsPayload(User $user): array
    {
        return [
            'label' => 'あなたの集計',
            'tweet_count' => $user->tweets()->count(),
            'like_count' => Like::query()
                ->join('tweets', 'likes.tweet_id', '=', 'tweets.id')
                ->where('tweets.user_id', $user->id)
                ->count(),
        ];
    }
}
