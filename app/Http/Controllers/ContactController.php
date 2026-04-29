<?php

namespace App\Http\Controllers;

use App\Mail\ContactInquiry;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function create(): View
    {
        return view('contact.create');
    }

    public function store(Request $request, Mailer $mailer): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $user = $request->user();

        $mailer->to(config('mail.admin_address'))
            ->queue(new ContactInquiry(
                $user->name,
                $user->email,
                $validated['body'],
            ));

        return redirect()
            ->route('contact.create')
            ->with('feedback.success', 'お問い合わせを送信しました。');
    }
}
