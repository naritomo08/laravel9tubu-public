<?php

namespace Tests\Feature\Console;

use App\Console\Commands\SendDailyTweetCountMail;
use App\Mail\DailyTweetCount;
use App\Models\Like;
use App\Models\Tweet;
use App\Models\User;
use App\Services\TweetService;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SendDailyTweetCountMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_tweet_count_mail_includes_each_users_tweet_and_like_counts()
    {
        $recipient = User::factory()->create([
            'name' => '山田太郎',
            'email' => 'recipient@example.com',
        ]);
        $otherUser = User::factory()->create();
        $liker = User::factory()->create();

        $recipientTweet1 = Tweet::factory()->create(['user_id' => $recipient->id]);
        $recipientTweet2 = Tweet::factory()->create(['user_id' => $recipient->id]);
        $otherTweet = Tweet::factory()->create(['user_id' => $otherUser->id]);

        Like::create(['user_id' => $liker->id, 'tweet_id' => $recipientTweet1->id]);
        Like::create(['user_id' => $otherUser->id, 'tweet_id' => $recipientTweet1->id]);
        Like::create(['user_id' => $liker->id, 'tweet_id' => $otherTweet->id]);

        $sentMailables = [];
        $mailer = Mockery::mock(Mailer::class);
        $mailer->shouldReceive('to')->times(3)->andReturnSelf();
        $mailer->shouldReceive('send')
            ->times(3)
            ->with(Mockery::on(function ($mailable) use (&$sentMailables) {
                if (! $mailable instanceof DailyTweetCount) {
                    return false;
                }

                $sentMailables[] = $mailable;

                return true;
            }));

        $command = new SendDailyTweetCountMail(
            app(TweetService::class),
            $mailer
        );

        $command->handle();

        $this->assertCount(3, $sentMailables);

        $recipientMail = collect($sentMailables)->first(fn (DailyTweetCount $mail) => $mail->toUser->is($recipient));
        $otherUserMail = collect($sentMailables)->first(fn (DailyTweetCount $mail) => $mail->toUser->is($otherUser));

        $this->assertNotNull($recipientMail);
        $this->assertSame(3, $recipientMail->count);
        $this->assertSame(2, $recipientMail->userTweetCount);
        $this->assertSame(2, $recipientMail->userLikeCount);

        $this->assertNotNull($otherUserMail);
        $this->assertSame(3, $otherUserMail->count);
        $this->assertSame(1, $otherUserMail->userTweetCount);
        $this->assertSame(1, $otherUserMail->userLikeCount);
    }
}
