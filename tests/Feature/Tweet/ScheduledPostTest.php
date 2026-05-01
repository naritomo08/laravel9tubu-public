<?php

namespace Tests\Feature\Tweet;

use App\Models\Tweet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduledPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_can_schedule_tweet_for_future_publication()
    {
        Carbon::setTestNow('2026-05-01 10:00:00');

        $user = User::factory()->create();

        $this->actingAs($user)->post('/tweet/create', [
            'tweet' => 'scheduled tweet body',
            'scheduled_at' => '2026-05-01T11:30',
        ])->assertRedirect('/tweet?page=1')
            ->assertSessionHas('feedback.success', 'つぶやきを予約しました');

        $this->assertDatabaseHas('tweets', [
            'user_id' => $user->id,
            'content' => 'scheduled tweet body',
            'scheduled_at' => '2026-05-01 11:30:00',
        ]);

        $this->actingAs($user)->get('/tweet')
            ->assertOk()
            ->assertDontSee('scheduled tweet body');

        Carbon::setTestNow('2026-05-01 11:30:00');

        $this->actingAs($user)->get('/tweet')
            ->assertOk()
            ->assertSee('scheduled tweet body')
            ->assertSee('2026-05-01 11:30:00')
            ->assertDontSee('2026-05-01 10:00:00');
    }

    public function test_visible_scheduled_tweets_are_ordered_by_scheduled_time()
    {
        Carbon::setTestNow('2026-05-01 12:00:00');

        $user = User::factory()->create();
        Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'older immediate tweet',
            'created_at' => Carbon::parse('2026-05-01 11:45:00'),
            'scheduled_at' => null,
        ]);
        Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'newer scheduled tweet',
            'created_at' => Carbon::parse('2026-05-01 10:00:00'),
            'scheduled_at' => Carbon::parse('2026-05-01 11:50:00'),
        ]);

        $this->actingAs($user)->get('/tweet')
            ->assertOk()
            ->assertSeeInOrder([
                'newer scheduled tweet',
                '2026-05-01 11:50:00',
                'older immediate tweet',
                '2026-05-01 11:45:00',
            ]);
    }

    public function test_search_and_latest_hide_tweets_until_scheduled_time()
    {
        Carbon::setTestNow('2026-05-01 10:00:00');

        $user = User::factory()->create();
        $scheduledTweet = Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'hidden until scheduled keyword',
            'scheduled_at' => Carbon::parse('2026-05-01 11:30:00'),
        ]);

        $searchResponse = $this->actingAs($user)->getJson('/tweet/search/results?q=scheduled');
        $latestResponse = $this->actingAs($user)->getJson('/tweet/latest?' . http_build_query([
            'after_id' => 0,
            'tweet_versions' => json_encode([
                $scheduledTweet->id => $scheduledTweet->version(),
            ]),
        ]));

        $searchResponse->assertOk()
            ->assertJsonPath('count', 0);
        $this->assertStringNotContainsString('hidden until scheduled keyword', (string) $searchResponse->json('html'));

        $latestResponse->assertOk();
        $this->assertStringNotContainsString('hidden until scheduled keyword', (string) $latestResponse->json('html'));
        $this->assertSame([], $latestResponse->json('updated_html'));

        Carbon::setTestNow('2026-05-01 11:30:00');

        $this->actingAs($user)->getJson('/tweet/search/results?q=scheduled')
            ->assertOk()
            ->assertJsonPath('count', 1);

        $visibleLatestResponse = $this->actingAs($user)->getJson('/tweet/latest?after_id=0');

        $visibleLatestResponse->assertOk()
            ->assertJsonPath('latest_id', $scheduledTweet->id);
        $this->assertStringContainsString('hidden until scheduled keyword', (string) $visibleLatestResponse->json('html'));
    }
}
