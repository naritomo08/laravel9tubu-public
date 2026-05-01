<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserDeletionService
{
    public function __construct(
        private readonly TweetService $tweetService,
    ) {
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->tweets()
                ->pluck('id')
                ->each(fn (int $tweetId) => $this->tweetService->deleteTweet($tweetId, allowSeeded: true));

            $user->likes()->delete();
            $user->delete();
        });
    }
}
