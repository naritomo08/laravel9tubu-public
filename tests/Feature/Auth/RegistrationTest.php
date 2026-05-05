<?php

namespace Tests\Feature\Auth;

use App\Jobs\SendNewUserIntroductionJob;
use App\Mail\NewUserIntroduction;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
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
        Queue::fake();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
        Queue::assertPushed(SendNewUserIntroductionJob::class, function (SendNewUserIntroductionJob $job) {
            return $job->newUserId === User::where('email', 'test@example.com')->value('id');
        });
    }

    public function test_new_user_introduction_mail_job_queues_mail_only_to_verified_notification_recipients()
    {
        $newUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $verifiedUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $unverifiedUser = User::factory()->unverified()->create();
        $disabledUser = User::factory()->create([
            'email_verified_at' => now(),
            'receives_notification_mail' => false,
        ]);
        $pendingDeletionUser = User::factory()->create([
            'email_verified_at' => now(),
            'deletion_requested_at' => now(),
        ]);

        (new SendNewUserIntroductionJob($newUser->id))->handle(app(Mailer::class));

        Mail::assertQueued(NewUserIntroduction::class, 1);
        Mail::assertQueued(NewUserIntroduction::class, function (NewUserIntroduction $mail) use ($verifiedUser) {
            return $mail->toUser->is($verifiedUser);
        });
        Mail::assertNotQueued(NewUserIntroduction::class, function (NewUserIntroduction $mail) use ($newUser) {
            return $mail->toUser->is($newUser);
        });
        Mail::assertNotQueued(NewUserIntroduction::class, function (NewUserIntroduction $mail) use ($unverifiedUser) {
            return $mail->toUser->is($unverifiedUser);
        });
        Mail::assertNotQueued(NewUserIntroduction::class, function (NewUserIntroduction $mail) use ($disabledUser) {
            return $mail->toUser->is($disabledUser);
        });
        Mail::assertNotQueued(NewUserIntroduction::class, function (NewUserIntroduction $mail) use ($pendingDeletionUser) {
            return $mail->toUser->is($pendingDeletionUser);
        });
    }
}
