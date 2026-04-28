<?php

namespace Tests;

use App\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('mail.default', 'array');
        Mail::fake();

        $this->withoutVite();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }
}
