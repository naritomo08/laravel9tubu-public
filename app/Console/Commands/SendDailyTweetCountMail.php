<?php

namespace App\Console\Commands;

use App\Mail\DailyTweetCount;
use App\Models\Like;
use App\Models\User;
use App\Services\TweetService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Mail\Mailer;

class SendDailyTweetCountMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:send-daily-tweet-count-mail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '前日のつぶやき数を集計してつぶやきを促すメールを送ります。';

    private TweetService $tweetService;
    private Mailer $mailer;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(TweetService $tweetService, Mailer $mailer)
    {
        parent::__construct();
        $this->tweetService = $tweetService;
        $this->mailer = $mailer;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tweetCount = $this->tweetService->countYesterdayTweets();

        $users = User::query()
            ->notPendingDeletion()
            ->whereNotNull('email_verified_at')
            ->where('receives_notification_mail', true)
            ->withCount('tweets')
            ->addSelect([
                'received_likes_count' => Like::query()
                    ->selectRaw('count(*)')
                    ->join('tweets', 'likes.tweet_id', '=', 'tweets.id')
                    ->whereColumn('tweets.user_id', 'users.id'),
            ])
            ->get();

        foreach ($users as $user) {
            $this->mailer->to($user->email)
                ->send(new DailyTweetCount(
                    $user,
                    $tweetCount,
                    (int) $user->tweets_count,
                    (int) ($user->received_likes_count ?? 0)
                ));
        }

        return 0;
    }
}
