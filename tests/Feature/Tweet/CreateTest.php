<?php

namespace Tests\Feature\Tweet;

use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_admin_created_tweet_is_marked_seeded(): void
    {
        $seedAdmin = User::factory()->create([
            'is_admin' => true,
            'is_seed_admin' => true,
        ]);

        $this->actingAs($seedAdmin)->post('/tweet/create', [
            'tweet' => 'seed admin tweet',
        ])->assertRedirect('/tweet?page=1');

        $tweet = Tweet::where('content', 'seed admin tweet')->firstOrFail();

        $this->assertTrue($tweet->is_seeded);
    }

    public function test_non_seed_admin_created_tweet_is_not_marked_seeded(): void
    {
        $user = User::factory()->create([
            'is_admin' => true,
            'is_seed_admin' => false,
        ]);

        $this->actingAs($user)->post('/tweet/create', [
            'tweet' => 'normal admin tweet',
        ])->assertRedirect('/tweet?page=1');

        $tweet = Tweet::where('content', 'normal admin tweet')->firstOrFail();

        $this->assertFalse($tweet->is_seeded);
    }
}
