<?php

namespace Tests\Feature\Tweet;

use App\Models\Image;
use App\Models\Tweet;
use App\Models\User;
use App\Services\TweetService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_tweet_order_does_not_change_after_update(): void
    {
        $user = User::factory()->create();
        $oldTime = Carbon::now()->subMinutes(30);
        $newTime = Carbon::now()->subMinutes(10);
        $olderTweet = Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'older tweet',
            'created_at' => $oldTime,
            'updated_at' => $oldTime,
        ]);
        $newerTweet = Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'newer tweet',
            'created_at' => $newTime,
            'updated_at' => $newTime,
        ]);

        Carbon::setTestNow(Carbon::now());
        $this->actingAs($user)
            ->put(route('tweet.update.put', $olderTweet), [
                'tweet' => 'edited older tweet',
            ])
            ->assertRedirect('/tweet?page=1');

        $tweets = app(TweetService::class)->getTweets(1)->getCollection();

        $this->assertSame([$newerTweet->id, $olderTweet->id], $tweets->pluck('id')->all());
        Carbon::setTestNow();
    }

    public function test_user_can_delete_and_add_tweet_images_when_updating(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $tweet = Tweet::factory()->create(['user_id' => $user->id]);
        $oldImage = new Image();
        $oldImage->name = 'old-image.jpg';
        $oldImage->save();
        Storage::put('public/images/old-image.jpg', 'old');
        $tweet->images()->attach($oldImage->id);

        $newImage = $this->fakePng('new-image.png');
        $newImageName = $newImage->hashName();

        $this->actingAs($user)
            ->put(route('tweet.update.put', $tweet), [
                'tweet' => 'edited with image',
                'delete_image_ids' => [$oldImage->id],
                'images' => [$newImage],
            ])
            ->assertRedirect('/tweet?page=1');

        $this->assertDatabaseHas('tweets', [
            'id' => $tweet->id,
            'content' => 'edited with image',
        ]);
        $this->assertDatabaseMissing('images', ['id' => $oldImage->id]);
        $this->assertDatabaseHas('images', ['name' => $newImageName]);
        Storage::assertMissing('public/images/old-image.jpg');
        Storage::assertExists('public/images/' . $newImageName);
    }

    public function test_latest_returns_updated_tweet_html_after_image_update(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $oldTime = Carbon::now()->subMinutes(10);
        $tweet = Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'image tweet',
            'created_at' => $oldTime,
            'updated_at' => $oldTime,
        ]);
        $oldImage = new Image();
        $oldImage->name = 'old-image.jpg';
        $oldImage->save();
        Storage::put('public/images/old-image.jpg', 'old');
        $tweet->images()->attach($oldImage->id);

        $newImage = $this->fakePng('new-image.png');
        $newImageName = $newImage->hashName();

        $this->actingAs($user)
            ->put(route('tweet.update.put', $tweet), [
                'tweet' => 'image tweet',
                'delete_image_ids' => [$oldImage->id],
                'images' => [$newImage],
            ]);

        $response = $this->getJson('/tweet/latest?' . http_build_query([
            'after_id' => $tweet->id,
            'tweet_versions' => json_encode([
                $tweet->id => $oldTime->toJSON(),
            ]),
        ]));
        $updatedHtml = implode('', $response->json('updated_html', []));

        $response->assertOk();
        $this->assertStringContainsString($newImageName, $updatedHtml);
        $this->assertStringNotContainsString('old-image.jpg', $updatedHtml);
    }

    public function test_latest_detects_image_changes_even_when_tweet_timestamp_does_not_change(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $oldTime = Carbon::now()->subMinutes(10);
        $tweet = Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'image only version tweet',
            'created_at' => $oldTime,
            'updated_at' => $oldTime,
        ]);
        $oldImage = new Image();
        $oldImage->name = 'old-image.jpg';
        $oldImage->save();
        $tweet->images()->attach($oldImage->id);
        $initialVersion = $tweet->fresh()->version;

        $newImage = new Image();
        $newImage->name = 'new-image.jpg';
        $newImage->save();
        $tweet->images()->detach($oldImage->id);
        $tweet->images()->attach($newImage->id);

        $response = $this->getJson('/tweet/latest?' . http_build_query([
            'after_id' => $tweet->id,
            'tweet_versions' => json_encode([
                $tweet->id => $initialVersion,
            ]),
        ]));
        $updatedHtml = implode('', $response->json('updated_html', []));

        $response->assertOk();
        $this->assertStringContainsString('new-image.jpg', $updatedHtml);
        $this->assertStringNotContainsString('old-image.jpg', $updatedHtml);
    }

    private function fakePng(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=')
        );
    }
}
