<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\UserDeletionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeleteUserJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public readonly int $userId,
    ) {
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function uniqueId(): string
    {
        return (string) $this->userId;
    }

    public function handle(UserDeletionService $userDeletionService): void
    {
        $user = User::query()
            ->whereKey($this->userId)
            ->whereNotNull('deletion_requested_at')
            ->first();

        if (! $user) {
            Log::info('User deletion job skipped because the user is already deleted or not marked.', [
                'user_id' => $this->userId,
            ]);

            return;
        }

        Log::info('User deletion job started.', ['user_id' => $this->userId]);

        $userDeletionService->delete($user);

        Log::info('User deletion job completed.', ['user_id' => $this->userId]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('User deletion job failed.', [
            'user_id' => $this->userId,
            'exception' => $exception,
        ]);
    }
}
