<?php

namespace App\View\Components\Tweet;

use Illuminate\View\Component;

class Options extends Component
{
    private int $tweetId;
    private int $userId;

    public function __construct(int $tweetId, int $userId)
    {
        $this->tweetId = $tweetId;
        $this->userId = $userId;
    }

    public function render()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $isMyTweet = $user && $user->id === $this->userId;
        $isAdminTweet = $user && $user->is_admin && $user->id === $this->userId;
        $isUserTweet = $user && !$user->is_admin && $user->id === $this->userId;
        $isAllTweet = true; // 全てのTweetに対してtrue
        return view('components.tweet.options')
            ->with('tweetId', $this->tweetId)
            ->with('isMyTweet', $isMyTweet)
            ->with('isAdminTweet', $isAdminTweet)
            ->with('isUserTweet', $isUserTweet)
            ->with('isAllTweet', $isAllTweet);
    }
}