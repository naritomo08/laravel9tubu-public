<?php

namespace App\View\Components\Tweet;

use Illuminate\View\Component;

class Options extends Component
{
    private int $tweetId;
    private int $userId;
    private ?int $currentPage;
    private ?string $returnUrl;

    public function __construct(int $tweetId, int $userId, ?int $currentPage = null, ?string $returnUrl = null)
    {
        $this->tweetId = $tweetId;
        $this->userId = $userId;
        $this->currentPage = $currentPage;
        $this->returnUrl = $returnUrl;
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
            ->with('currentPage', $this->currentPage)
            ->with('returnUrl', $this->returnUrl)
            ->with('isMyTweet', $isMyTweet)
            ->with('isAdminTweet', $isAdminTweet)
            ->with('isUserTweet', $isUserTweet)
            ->with('isAllTweet', $isAllTweet);
    }
}
