<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $applicationUrlHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $requestHost = request()->getHost();

        if (
            ! $this->app->environment(['local', 'testing'])
            && parse_url((string) config('app.url'), PHP_URL_SCHEME) === 'https'
            && $applicationUrlHost !== null
            && $requestHost === $applicationUrlHost
        ) {
            URL::forceScheme('https');
        }

        VerifyEmail::createUrlUsing(function ($notifiable) {
            $path = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(config('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ],
                false
            );

            return rtrim((string) config('app.url'), '/').$path;
        });
    }
}
