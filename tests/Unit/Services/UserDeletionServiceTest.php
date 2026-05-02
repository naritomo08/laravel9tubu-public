<?php

namespace Tests\Unit\Services;

use App\Models\Image;
use App\Jobs\DeleteUserJob;
use App\Models\Like;
use App\Models\Tweet;
use App\Models\User;
use App\Services\TweetImageService;
use App\Services\TweetService;
use App\Services\UserDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserDeletionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_removes_user_tweets_likes_and_tweet_images(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $tweet = Tweet::factory()->create([
            'user_id' => $user->id,
            'is_seeded' => true,
        ]);
        $otherTweet = Tweet::factory()->create([
            'user_id' => $otherUser->id,
        ]);
        $image = Image::factory()->create();
        $tweet->images()->attach($image->id);

        Like::create(['user_id' => $user->id, 'tweet_id' => $otherTweet->id]);
        Like::create(['user_id' => $otherUser->id, 'tweet_id' => $tweet->id]);

        $this->assertTrue(Storage::disk('public')->exists('images/'.$image->name));

        $service = new UserDeletionService(new TweetService(new TweetImageService));
        $service->delete($user);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('tweets', ['id' => $tweet->id]);
        $this->assertDatabaseMissing('images', ['id' => $image->id]);
        $this->assertDatabaseMissing('tweet_images', [
            'tweet_id' => $tweet->id,
            'image_id' => $image->id,
        ]);
        $this->assertDatabaseMissing('likes', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('likes', ['tweet_id' => $tweet->id]);
        $this->assertDatabaseHas('users', ['id' => $otherUser->id]);
        $this->assertDatabaseHas('tweets', ['id' => $otherTweet->id]);
        $this->assertFalse(Storage::disk('public')->exists('images/'.$image->name));
    }

    public function test_request_deletion_marks_user_and_dispatches_job_once(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $service = new UserDeletionService(new TweetService(new TweetImageService));

        $this->assertTrue($service->requestDeletion($user));
        $this->assertFalse($service->requestDeletion($user->refresh()));

        $this->assertNotNull($user->refresh()->deletion_requested_at);
        Queue::assertPushed(DeleteUserJob::class, 1);
        Queue::assertPushed(DeleteUserJob::class, fn (DeleteUserJob $job) => $job->userId === $user->id);
    }
}
