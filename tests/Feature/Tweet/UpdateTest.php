<?php

namespace Tests\Feature\Tweet;

use App\Models\Image;
use App\Models\Tweet;
use App\Models\User;
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

    private function fakePngUpload(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'tweet-image-');
        file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
        ));

        return new UploadedFile($path, $name, 'image/png', null, true);
    }
}
