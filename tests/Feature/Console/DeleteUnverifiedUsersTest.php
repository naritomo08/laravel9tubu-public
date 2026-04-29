<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteUnverifiedUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_old_unverified_users_from_registration()
    {
        $registeredAt = now()->subHours(2);

        $user = User::factory()->unverified()->create([
            'created_at' => $registeredAt,
            'updated_at' => $registeredAt,
        ]);

        $this->artisan('users:delete-unverified')
            ->expectsOutput('Deleted 1 unverified users.')
            ->assertSuccessful();

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_does_not_delete_existing_user_after_email_is_changed()
    {
        $user = User::factory()->unverified()->create([
            'email' => 'changed@example.com',
            'created_at' => now()->subHours(2),
            'updated_at' => now(),
        ]);

        $this->artisan('users:delete-unverified')
            ->expectsOutput('Deleted 0 unverified users.')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'changed@example.com',
        ]);
    }
}
