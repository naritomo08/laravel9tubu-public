<?php

namespace Tests\Feature\Tweet;

use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ContentLengthTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_uses_configured_tweet_content_max_length(): void
    {
        Config::set('tweet.content_max_length', 5);

        $user = User::factory()->create();

        $this->actingAs($user)->post('/tweet/create', [
            'tweet' => '12345',
        ])->assertRedirect('/tweet?page=1');

        $this->assertDatabaseHas('tweets', [
            'user_id' => $user->id,
            'content' => '12345',
        ]);

        $this->actingAs($user)->from('/tweet')->post('/tweet/create', [
            'tweet' => '123456',
        ])->assertRedirect('/tweet')
            ->assertSessionHasErrors('tweet');
    }

    public function test_update_uses_configured_tweet_content_max_length(): void
    {
        Config::set('tweet.content_max_length', 5);

        $user = User::factory()->create();
        $tweet = Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'before',
        ]);

        $this->actingAs($user)->put('/tweet/update/'.$tweet->id, [
            'tweet' => '12345',
        ])->assertRedirect('/tweet?page=1');

        $this->assertDatabaseHas('tweets', [
            'id' => $tweet->id,
            'content' => '12345',
        ]);

        $this->actingAs($user)->from('/tweet/update/'.$tweet->id)->put('/tweet/update/'.$tweet->id, [
            'tweet' => '123456',
        ])->assertRedirect('/tweet/update/'.$tweet->id)
            ->assertSessionHasErrors('tweet');
    }

    public function test_forms_show_configured_tweet_content_max_length(): void
    {
        Config::set('tweet.content_max_length', 5);

        $user = User::factory()->create();
        $tweet = Tweet::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get('/tweet')
            ->assertOk()
            ->assertSee('maxlength="5"', false)
            ->assertSee('5文字まで');

        $this->actingAs($user)->get('/tweet/update/'.$tweet->id)
            ->assertOk()
            ->assertSee('maxlength="5"', false)
            ->assertSee('5文字まで');
    }
}
