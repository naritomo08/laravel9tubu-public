<?php

namespace Tests\Unit\Services;

use App\Models\Image;
use App\Models\Tweet;
use App\Models\User;
use App\Services\TweetImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TweetImageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_attach_image_stores_file_and_links_image_to_tweet(): void
    {
        Storage::fake('public');

        $tweet = Tweet::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        (new TweetImageService)->attachImage(
            $tweet,
            UploadedFile::fake()->create('tweet.png', 10, 'image/png')
        );

        $image = $tweet->images()->firstOrFail();

        $this->assertDatabaseHas('tweet_images', [
            'tweet_id' => $tweet->id,
            'image_id' => $image->id,
        ]);
        Storage::disk('public')->assertExists('images/'.$image->name);
    }

    public function test_delete_image_removes_file_relation_and_record(): void
    {
        Storage::fake('public');

        $tweet = Tweet::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);
        $image = Image::factory()->create(['name' => 'delete.jpg']);
        $tweet->images()->attach($image->id);
        Storage::disk('public')->put('images/delete.jpg', 'delete');

        Storage::disk('public')->assertExists('images/delete.jpg');

        (new TweetImageService)->deleteImage($tweet, $image);

        Storage::disk('public')->assertMissing('images/delete.jpg');
        $this->assertDatabaseMissing('tweet_images', [
            'tweet_id' => $tweet->id,
            'image_id' => $image->id,
        ]);
        $this->assertDatabaseMissing('images', ['id' => $image->id]);
    }
}
