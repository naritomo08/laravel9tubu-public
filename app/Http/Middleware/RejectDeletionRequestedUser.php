<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RejectDeletionRequestedUser
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (! $user?->isDeletionRequested()) {
            return $next($request);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            abort(403, 'このアカウントは削除受付済みです。');
        }

        return redirect('/tweet')
            ->with('feedback.success', 'アカウント削除を受け付けました。処理が完了するまでしばらくお待ちください。');
    }
}
