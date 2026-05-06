<?php

namespace Tests\Feature\Tweet;

use App\Models\Image;
use App\Models\Tweet;
use App\Models\User;
use App\Services\TweetQueryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

        $html = (string) $response->json('html');

        $response->assertOk()
            ->assertJsonPath('latest_id', $newTweet->id);

        $this->assertStringContainsString('new tweet', $html);
        $this->assertStringNotContainsString('old tweet', $html);
    }

    public function test_latest_limits_returned_tweets_when_after_id_is_zero()
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= TweetQueryService::LATEST_TWEETS_LIMIT + 1; $i++) {
            Tweet::factory()->create([
                'user_id' => $user->id,
                'content' => sprintf('limited tweet %03d', $i),
            ]);
        }

        $response = $this->getJson('/tweet/latest?after_id=0');

        $html = (string) $response->json('html');

        $response->assertOk();

        $this->assertSame(TweetQueryService::LATEST_TWEETS_LIMIT, substr_count($html, 'data-tweet-id='));
        $this->assertStringContainsString(sprintf('limited tweet %03d', TweetQueryService::LATEST_TWEETS_LIMIT + 1), $html);
        $this->assertStringNotContainsString('limited tweet 001', $html);
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

        $updatedHtml = implode('', $response->json('updated_html', []));

        $response->assertOk()
            ->assertJsonPath('latest_id', $tweet->id);

        $this->assertStringContainsString('New Name', $updatedHtml);
        $this->assertStringContainsString('same tweet', $updatedHtml);
        $this->assertStringNotContainsString('Old Name', $updatedHtml);
    }

    public function test_latest_returns_updated_tweet_when_images_changed()
    {
        Storage::fake('public');

        $oldTime = Carbon::now()->subMinutes(10);
        $user = User::factory()->create([
            'created_at' => $oldTime,
            'updated_at' => $oldTime,
        ]);
        $tweet = Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'image changed tweet',
            'created_at' => $oldTime,
            'updated_at' => $oldTime,
        ]);
        $oldVersion = $tweet->load(['user', 'images'])->version();
        $image = Image::factory()->create(['name' => 'changed.png']);

        Storage::disk('public')->put('images/changed.png', 'image');
        $tweet->images()->attach($image->id);

        $response = $this->getJson('/tweet/latest?' . http_build_query([
            'after_id' => $tweet->id,
            'tweet_versions' => json_encode([
                $tweet->id => $oldVersion,
            ]),
        ]));

        $updatedHtml = implode('', $response->json('updated_html', []));

        $response->assertOk()
            ->assertJsonPath('latest_id', $tweet->id);

        $this->assertStringContainsString('image changed tweet', $updatedHtml);
        $this->assertStringContainsString('changed.png', $updatedHtml);
    }
}
