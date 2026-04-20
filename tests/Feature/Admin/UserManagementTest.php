<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_user_email()
    {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.email.update', $user), [
                'email' => 'new@example.com',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();

        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_admin_can_not_update_user_email_to_existing_email()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['email' => 'old@example.com']);
        $otherUser = User::factory()->create(['email' => 'existing@example.com']);

        $this->actingAs($admin)
            ->put(route('admin.users.email.update', $user), [
                'email' => $otherUser->email,
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame('old@example.com', $user->refresh()->email);
    }

    public function test_non_admin_can_not_update_user_email()
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create(['email' => 'old@example.com']);

        $this->actingAs($user)
            ->put(route('admin.users.email.update', $targetUser), [
                'email' => 'new@example.com',
            ])
            ->assertForbidden();

        $this->assertSame('old@example.com', $targetUser->refresh()->email);
    }
}
