<?php

namespace App\Services;

use App\Jobs\DeleteUserJob;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserDeletionService
{
    public function __construct(
        private readonly TweetService $tweetService,
    ) {
    }

    public function requestDeletion(User $user): bool
    {
        if ($user->isDeletionRequested()) {
            return false;
        }

        $updated = User::query()
            ->whereKey($user->id)
            ->whereNull('deletion_requested_at')
            ->update(['deletion_requested_at' => now()]);

        if ($updated === 0) {
            return false;
        }

        DeleteUserJob::dispatch($user->id);

        return true;
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
