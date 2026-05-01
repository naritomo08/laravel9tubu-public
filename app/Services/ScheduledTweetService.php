<?php

namespace App\Services;

use App\Models\Tweet;
use Illuminate\Support\Collection;

class ScheduledTweetService
{
    public function getUpcomingTweets(?int $userId = null): Collection
    {
        return Tweet::with('user')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>', now())
            ->when($userId, function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->get();
    }
}
