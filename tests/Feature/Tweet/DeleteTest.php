<?php

namespace Tests\Feature\Tweet;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Tweet;

class DeleteTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_delete_successed()
    {
        $user = User::factory()->create();

        $tweet = Tweet::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->delete('/tweet/delete/' . $tweet->id);

        $response->assertRedirect('/tweet?page=1');
    }

    public function test_delete_redirects_back_to_search_page_with_feedback()
    {
        $user = User::factory()->create();
        $tweet = Tweet::factory()->create(['user_id' => $user->id]);
        $returnUrl = '/tweet/search?' . http_build_query([
            'q' => 'keyword',
            'user_search' => 1,
            'page' => 3,
        ]);

        $response = $this->actingAs($user)->delete('/tweet/delete/' . $tweet->id, [
            'page' => 3,
            'return_url' => $returnUrl,
        ]);

        $response->assertRedirect($returnUrl)
            ->assertSessionHas('feedback.success', 'つぶやきを削除しました');
    }
}
