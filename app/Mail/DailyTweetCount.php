<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DailyTweetCount extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $toUser;
    public int $count;
    public int $userTweetCount;
    public int $userLikeCount;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $toUser, int $count, int $userTweetCount, int $userLikeCount)
    {
        $this->toUser = $toUser;
        $this->count = $count;
        $this->userTweetCount = $userTweetCount;
        $this->userLikeCount = $userLikeCount;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("昨日は{$this->count}件のつぶやきが追加されました！")
            ->markdown('email.daily_tweet_count');
    }
}
