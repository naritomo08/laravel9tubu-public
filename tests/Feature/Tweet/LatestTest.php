<?php

namespace Tests\Feature\Tweet;

use App\Models\Tweet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LatestTest extends TestCase
{
    use RefreshDatabase;

    public function test_latest_returns_only_newer_tweets()
    {
        $user = User::factory()->create();
        $oldTweet = Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'old tweet',
        ]);
        $newTweet = Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'new tweet',
        ]);

        $response = $this->getJson('/tweet/latest?after_id=' . $oldTweet->id);

        $response->assertOk()
            ->assertJsonPath('latest_id', $newTweet->id)
            ->assertSee('new tweet')
            ->assertDontSee('old tweet');
    }

    public function test_latest_returns_updated_tweet_when_user_name_changed()
    {
        $oldTime = Carbon::now()->subMinutes(10);
        $newTime = Carbon::now();
        $user = User::factory()->create([
            'name' => 'Old Name',
            'created_at' => $oldTime,
            'updated_at' => $oldTime,
        ]);
        $tweet = Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'same tweet',
            'created_at' => $oldTime,
            'updated_at' => $oldTime,
        ]);

        $user->forceFill([
            'name' => 'New Name',
            'updated_at' => $newTime,
        ])->save();

        $response = $this->getJson('/tweet/latest?' . http_build_query([
            'after_id' => $tweet->id,
            'tweet_versions' => json_encode([
                $tweet->id => $oldTime->toJSON(),
            ]),
        ]));

        $response->assertOk()
            ->assertJsonPath('latest_id', $tweet->id)
            ->assertSee('New Name')
            ->assertSee('same tweet')
            ->assertDontSee('Old Name');
    }
}
