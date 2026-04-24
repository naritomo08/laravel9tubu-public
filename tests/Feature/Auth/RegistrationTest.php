<?php

namespace Tests\Feature\Auth;

use App\Mail\NewUserIntroduction;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_new_user_introduction_mail_is_sent_only_to_verified_users()
    {
        $verifiedUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $unverifiedUser = User::factory()->unverified()->create();

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(RouteServiceProvider::HOME);

        Mail::assertQueued(NewUserIntroduction::class, 1);
        Mail::assertQueued(NewUserIntroduction::class, function (NewUserIntroduction $mail) use ($verifiedUser) {
            return $mail->toUser->is($verifiedUser);
        });
        Mail::assertNotQueued(NewUserIntroduction::class, function (NewUserIntroduction $mail) use ($unverifiedUser) {
            return $mail->toUser->is($unverifiedUser);
        });
    }
}
