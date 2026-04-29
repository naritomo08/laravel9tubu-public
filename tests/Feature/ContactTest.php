<?php

namespace Tests\Feature;

use App\Mail\ContactInquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_page_can_be_rendered()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/contact')
            ->assertOk()
            ->assertSee('お問い合わせ')
            ->assertSee('ユーザー名')
            ->assertSee('メールアドレス')
            ->assertSee('問い合わせ内容');
    }

    public function test_contact_page_requires_login()
    {
        $this->get('/contact')
            ->assertRedirect(route('login'));

        $this->post('/contact', [
            'body' => '問い合わせ本文です。',
        ])->assertRedirect(route('login'));

        Mail::assertNothingQueued();
    }

    public function test_contact_form_displays_authenticated_user_as_fixed_text()
    {
        $user = User::factory()->create([
            'name' => '山田太郎',
            'email' => 'taro@example.com',
        ]);

        $this->actingAs($user)
            ->get('/contact')
            ->assertOk()
            ->assertSee('山田太郎')
            ->assertSee('taro@example.com')
            ->assertDontSee('name="name"', false)
            ->assertDontSee('name="email"', false);
    }

    public function test_contact_inquiry_mail_is_queued_to_admin_address_with_authenticated_user()
    {
        Config::set('mail.admin_address', 'admin@example.com');

        $user = User::factory()->create([
            'name' => '山田太郎',
            'email' => 'taro@example.com',
        ]);

        $this->actingAs($user)
            ->post('/contact', [
                'body' => '問い合わせ本文です。',
            ])
            ->assertRedirect(route('contact.create'))
            ->assertSessionHas('feedback.success', 'お問い合わせを送信しました。');

        Mail::assertQueued(ContactInquiry::class, function (ContactInquiry $mail) {
            return $mail->hasTo('admin@example.com')
                && $mail->name === '山田太郎'
                && $mail->email === 'taro@example.com'
                && $mail->body === '問い合わせ本文です。';
        });
    }

    public function test_contact_inquiry_is_not_queued_when_validation_fails()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/contact', [
                'body' => '',
            ])->assertSessionHasErrors(['body']);

        Mail::assertNothingQueued();
    }
}
