<?php

namespace Tests\Unit\Services;

use App\Models\Tweet;
use App\Models\User;
use App\Services\TweetImageService;
use App\Services\TweetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TweetServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_own_tweet(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $tweet = Tweet::factory()->create([
            'user_id' => $user->id,
        ]);

        $tweetService = new TweetService(new TweetImageService);

        $this->assertTrue($tweetService->checkOwnTweet($user->id, $tweet->id));
        $this->assertFalse($tweetService->checkOwnTweet($otherUser->id, $tweet->id));
        $this->assertFalse($tweetService->checkOwnTweet($user->id, $tweet->id + 1));
    }
}
