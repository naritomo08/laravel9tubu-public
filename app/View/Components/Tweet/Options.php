<?php

namespace App\View\Components\Tweet;

use Illuminate\View\Component;

class Options extends Component
{
    private int $tweetId;
    private int $userId;
    private ?int $currentPage;
    private ?string $returnUrl;
    private bool $isSeeded;
    private bool $isProtected;
    private bool $tweetUserIsSeedAdmin;

    public function __construct(
        int $tweetId,
        int $userId,
        ?int $currentPage = null,
        ?string $returnUrl = null,
        bool $isSeeded = false,
        bool $isProtected = false,
        bool $tweetUserIsSeedAdmin = false
    )
    {
        $this->tweetId = $tweetId;
        $this->userId = $userId;
        $this->currentPage = $currentPage;
        $this->returnUrl = $returnUrl;
        $this->isSeeded = $isSeeded;
        $this->isProtected = $isProtected;
        $this->tweetUserIsSeedAdmin = $tweetUserIsSeedAdmin;
    }

    public function render()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $isMyTweet = $user && $user->id === $this->userId;
        $isSeedAdmin = $user && $user->is_seed_admin;
        $canManageProtection = $isSeedAdmin && !$this->tweetUserIsSeedAdmin;
        $canEditTweet = $isMyTweet && !$this->isProtected;
        $canDeleteSeededTweet = !$this->isSeeded || ($isSeedAdmin && $isMyTweet);
        $canDeleteProtectedTweet = !$this->isProtected || $isSeedAdmin;
        $canDeleteTweet = $user
            && $canDeleteSeededTweet
            && $canDeleteProtectedTweet
            && ($user->is_admin || $isMyTweet);
        $isAllTweet = true; // 全てのTweetに対してtrue
        return view('components.tweet.options')
            ->with('tweetId', $this->tweetId)
            ->with('currentPage', $this->currentPage)
            ->with('returnUrl', $this->returnUrl)
            ->with('isMyTweet', $isMyTweet)
            ->with('isSeeded', $this->isSeeded)
            ->with('isProtected', $this->isProtected)
            ->with('canEditTweet', $canEditTweet)
            ->with('canDeleteTweet', $canDeleteTweet)
            ->with('canManageProtection', $canManageProtection)
            ->with('isAllTweet', $isAllTweet);
    }
}
