<?php

namespace Tests\Feature\Tweet;

use App\Models\Tweet;
use App\Models\User;
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
}
