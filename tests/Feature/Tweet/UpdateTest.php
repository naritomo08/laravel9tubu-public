<?php

namespace Tests\Feature\Tweet;

use App\Models\Image;
use App\Models\Tweet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_can_add_and_delete_images()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $tweet = Tweet::factory()->create(['user_id' => $user->id, 'content' => 'before']);
        $deleteImage = Image::factory()->create(['name' => 'delete.jpg']);
        $keepImage = Image::factory()->create(['name' => 'keep.jpg']);

        Storage::disk('public')->put('images/delete.jpg', 'delete');
        Storage::disk('public')->put('images/keep.jpg', 'keep');
        $tweet->images()->attach([$deleteImage->id, $keepImage->id]);

        $response = $this->actingAs($user)->put('/tweet/update/' . $tweet->id, [
            'tweet' => 'after',
            'page' => 1,
            'delete_image_ids' => [$deleteImage->id],
            'images' => [
                $this->fakePngUpload('new.png'),
            ],
        ]);

        $response->assertRedirect('/tweet?page=1');
        $this->assertDatabaseHas('tweets', [
            'id' => $tweet->id,
            'content' => 'after',
        ]);
        $this->assertDatabaseMissing('images', ['id' => $deleteImage->id]);
        Storage::disk('public')->assertMissing('images/delete.jpg');
        Storage::disk('public')->assertExists('images/keep.jpg');

        $tweet->refresh();
        $this->assertTrue($tweet->images()->where('images.id', $keepImage->id)->exists());
        $this->assertSame(2, $tweet->images()->count());

        $newImage = $tweet->images()->where('images.id', '!=', $keepImage->id)->firstOrFail();
        Storage::disk('public')->assertExists('images/' . $newImage->name);
    }

    public function test_update_rejects_more_than_four_images()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $tweet = Tweet::factory()->create(['user_id' => $user->id]);
        $images = Image::factory()->count(4)->create();
        $tweet->images()->attach($images->pluck('id'));

        $response = $this->actingAs($user)->from('/tweet/update/' . $tweet->id)->put('/tweet/update/' . $tweet->id, [
            'tweet' => 'after',
            'images' => [
                $this->fakePngUpload('extra.png'),
            ],
        ]);

        $response->assertRedirect('/tweet/update/' . $tweet->id);
        $response->assertSessionHasErrors('images');
        $this->assertSame(4, $tweet->images()->count());
    }

    public function test_update_redirects_back_to_search_page_with_feedback()
    {
        $user = User::factory()->create();
        $tweet = Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'before',
        ]);
        $returnUrl = '/tweet/search?' . http_build_query([
            'q' => 'before',
            'user_search' => 0,
            'page' => 2,
        ]);

        $response = $this->actingAs($user)->put('/tweet/update/' . $tweet->id, [
            'tweet' => 'after',
            'page' => 2,
            'return_url' => $returnUrl,
        ]);

        $response->assertRedirect($returnUrl)
            ->assertSessionHas('feedback.success', 'つぶやきを編集しました');
    }

    public function test_published_tweet_cannot_be_changed_to_scheduled_tweet()
    {
        Carbon::setTestNow('2026-05-01 10:00:00');

        $user = User::factory()->create();
        $tweet = Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'published tweet',
            'scheduled_at' => null,
        ]);

        $this->actingAs($user)->get('/tweet/update/' . $tweet->id)
            ->assertOk()
            ->assertDontSee('name="scheduled_at"', false);

        $this->actingAs($user)->put('/tweet/update/' . $tweet->id, [
            'tweet' => 'attempted reschedule',
            'scheduled_at' => '2026-05-01T12:00',
        ])->assertRedirect('/tweet?page=1');

        $tweet->refresh();
        $this->assertSame('attempted reschedule', $tweet->content);
        $this->assertNull($tweet->scheduled_at);
    }

    public function test_unpublished_scheduled_tweet_can_update_scheduled_time()
    {
        Carbon::setTestNow('2026-05-01 10:00:00');

        $user = User::factory()->create();
        $tweet = Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'future tweet',
            'scheduled_at' => Carbon::parse('2026-05-01 11:00:00'),
        ]);

        $this->actingAs($user)->get('/tweet/update/' . $tweet->id)
            ->assertOk()
            ->assertSee('name="scheduled_at"', false)
            ->assertSee('value="2026-05-01T11:00"', false);

        $this->actingAs($user)->put('/tweet/update/' . $tweet->id, [
            'tweet' => 'rescheduled future tweet',
            'scheduled_at' => '2026-05-01T12:00',
        ])->assertRedirect('/tweet?page=1');

        $tweet->refresh();
        $this->assertSame('rescheduled future tweet', $tweet->content);
        $this->assertSame('2026-05-01 12:00:00', $tweet->scheduled_at->format('Y-m-d H:i:s'));
    }

    private function fakePngUpload(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'tweet-image-');
        file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
        ));

        return new UploadedFile($path, $name, 'image/png', null, true);
    }
}
