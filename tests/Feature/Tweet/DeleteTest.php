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

        $response->assertRedirect('/tweet');
    }
}
