<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactInquiry extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $name;
    public string $email;
    public string $body;

    public function __construct(string $name, string $email, string $body)
    {
        $this->name = $name;
        $this->email = $email;
        $this->body = $body;
    }

    public function build()
    {
        return $this->subject('問い合わせが届きました')
            ->replyTo($this->email, $this->name)
            ->markdown('email.contact_inquiry');
    }
}
