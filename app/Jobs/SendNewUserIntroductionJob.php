<?php

namespace App\Jobs;

use App\Mail\NewUserIntroduction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendNewUserIntroductionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const CHUNK_SIZE = 100;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public readonly int $newUserId,
    ) {}

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(Mailer $mailer): void
    {
        $newUser = User::query()->find($this->newUserId);

        if (! $newUser) {
            Log::info('New user introduction job skipped because the new user was not found.', [
                'new_user_id' => $this->newUserId,
            ]);

            return;
        }

        User::query()
            ->notPendingDeletion()
            ->whereKeyNot($newUser->getKey())
            ->whereNotNull('email_verified_at')
            ->where('receives_notification_mail', true)
            ->chunkById(self::CHUNK_SIZE, function ($users) use ($mailer, $newUser): void {
                foreach ($users as $user) {
                    $mailer->to($user->email)
                        ->send(new NewUserIntroduction($user, $newUser));
                }
            });
    }

    public function failed(Throwable $exception): void
    {
        Log::error('New user introduction job failed.', [
            'new_user_id' => $this->newUserId,
            'exception' => $exception,
        ]);
    }
}
