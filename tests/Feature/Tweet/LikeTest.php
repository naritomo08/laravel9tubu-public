<?php

namespace Tests\Feature\Tweet;

use App\Models\Like;
use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_concurrent_like_insert_race_is_treated_as_liked_response(): void
    {
        $user = User::factory()->create();
        $tweet = Tweet::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);
        $raceInserted = false;

        DB::listen(function ($query) use ($user, $tweet, &$raceInserted): void {
            if ($raceInserted) {
                return;
            }

            if (
                str_starts_with($query->sql, 'select * from `likes`')
                && $query->bindings[0] === $user->id
                && $query->bindings[1] === $tweet->id
            ) {
                $raceInserted = true;

                Like::create([
                    'user_id' => $user->id,
                    'tweet_id' => $tweet->id,
                ]);
            }
        });

        $this->actingAs($user)
            ->postJson('/like', ['tweet_id' => $tweet->id])
            ->assertOk()
            ->assertJson([
                'is_liked' => true,
                'like_count' => 1,
            ]);

        $this->assertTrue($raceInserted);
        $this->assertSame(1, Like::where('user_id', $user->id)->where('tweet_id', $tweet->id)->count());
    }
}
