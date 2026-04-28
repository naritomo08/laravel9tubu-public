<?php

namespace Tests\Feature\Tweet;

use App\Models\Tweet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_page_requires_login()
    {
        $this->get('/tweet/search')->assertRedirect('/login');
    }

    public function test_search_results_filter_by_content()
    {
        $user = User::factory()->create();
        Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'Laravel search tweet',
        ]);
        Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'plain message',
        ]);

        $response = $this->actingAs($user)->getJson('/tweet/search/results?q=Laravel');

        $html = (string) $response->json('html');

        $response->assertOk()
            ->assertJsonPath('count', 1);
        $this->assertStringContainsString('Laravel search tweet', $html);
        $this->assertStringNotContainsString('plain message', $html);
    }

    public function test_search_results_are_empty_without_query()
    {
        $user = User::factory()->create();
        Tweet::factory()->create([
            'user_id' => $user->id,
            'content' => 'visible only after searching',
        ]);

        $response = $this->actingAs($user)->getJson('/tweet/search/results');

        $html = (string) $response->json('html');

        $response->assertOk()
            ->assertJsonPath('count', 0);
        $this->assertStringContainsString('該当するつぶやきはありません。', $html);
        $this->assertStringNotContainsString('visible only after searching', $html);
    }

    public function test_search_results_are_paginated()
    {
        $user = User::factory()->create();

        Tweet::factory()->count(51)->create([
            'user_id' => $user->id,
            'content' => 'pagination target',
        ]);

        $response = $this->actingAs($user)->getJson('/tweet/search/results?' . http_build_query([
            'q' => 'pagination target',
        ]));

        $html = (string) $response->json('html');

        $response->assertOk()
            ->assertJsonPath('count', 51);
        $this->assertStringContainsString('2 ページ', $html);
        $this->assertStringContainsString('page=2', $html);
    }

    public function test_search_results_move_back_to_last_page_when_requested_page_is_empty()
    {
        $user = User::factory()->create();

        Tweet::factory()->count(51)->create([
            'user_id' => $user->id,
            'content' => 'available page target',
        ]);

        $response = $this->actingAs($user)->getJson('/tweet/search/results?' . http_build_query([
            'q' => 'available page target',
            'page' => 3,
        ]));

        $html = (string) $response->json('html');

        $response->assertOk()
            ->assertJsonPath('count', 51)
            ->assertJsonPath('current_page', 2)
            ->assertJsonPath('last_page', 2);
        $this->assertStringContainsString('2 / 2 ページ', $html);
        $this->assertStringNotContainsString('該当するつぶやきはありません。', $html);
    }

    public function test_search_results_filter_by_user_search_checkbox()
    {
        $viewer = User::factory()->create();
        $alice = User::factory()->create(['name' => 'alice']);
        $bob = User::factory()->create(['name' => 'bob']);

        Tweet::factory()->create([
            'user_id' => $alice->id,
            'content' => 'same keyword',
        ]);
        Tweet::factory()->create([
            'user_id' => $bob->id,
            'content' => 'same keyword',
        ]);

        $response = $this->actingAs($viewer)->getJson('/tweet/search/results?' . http_build_query([
            'q' => 'alice',
            'user_search' => '1',
        ]));

        $html = (string) $response->json('html');

        $response->assertOk()
            ->assertJsonPath('count', 1);
        $this->assertStringContainsString('alice', $html);
        $this->assertStringNotContainsString('bob', $html);
    }

    public function test_search_results_do_not_treat_user_directive_as_special_keyword()
    {
        $viewer = User::factory()->create();
        $alice = User::factory()->create(['name' => 'alice']);

        Tweet::factory()->create([
            'user_id' => $alice->id,
            'content' => 'same keyword',
        ]);

        $response = $this->actingAs($viewer)->getJson('/tweet/search/results?' . http_build_query([
            'q' => 'user:"alice"',
        ]));

        $html = (string) $response->json('html');

        $response->assertOk()
            ->assertJsonPath('count', 0);
        $this->assertStringNotContainsString('same keyword', $html);
    }
}
