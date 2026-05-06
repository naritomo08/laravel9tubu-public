<?php

namespace App\Http\Responses;

use App\Providers\RouteServiceProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        $intendedPath = ltrim(parse_url((string) $request->session()->get('url.intended'), PHP_URL_PATH) ?: '', '/');

        if (! $request->user()->is_admin && ($intendedPath === 'admin' || Str::startsWith($intendedPath, 'admin/'))) {
            $request->session()->forget('url.intended');

            return redirect(RouteServiceProvider::HOME);
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }
}
