<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class GoogleAuthController extends Controller
{
    private const SESSION_STATE_KEY = 'google_oauth_state';
    private const SESSION_ACTION_KEY = 'google_oauth_action';

    public function redirectForLogin(Request $request): RedirectResponse
    {
        try {
            return $this->redirectToGoogle($request, 'login');
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('login')
                ->withErrors(['google' => 'Google認証の設定が不足しているため、ログインを開始できませんでした。']);
        }
    }

    public function redirectForLink(Request $request): RedirectResponse
    {
        try {
            return $this->redirectToGoogle($request, 'link');
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('account.index')
                ->withErrors(['google' => 'Google認証の設定が不足しているため、連携を開始できませんでした。']);
        }
    }

    public function callback(Request $request): RedirectResponse
    {
        $expectedState = (string) $request->session()->pull(self::SESSION_STATE_KEY);
        $action = (string) $request->session()->pull(self::SESSION_ACTION_KEY);

        if ($expectedState === '' || $action === '' || ! hash_equals($expectedState, (string) $request->input('state'))) {
            abort(403);
        }

        if ($request->filled('error')) {
            return $this->redirectWithError($action, 'Google認証がキャンセルされました。');
        }

        try {
            $googleUser = $this->fetchGoogleUser((string) $request->input('code'));
        } catch (Throwable $e) {
            report($e);

            return $this->redirectWithError($action, 'Google認証との通信に失敗しました。時間をおいて再度お試しください。');
        }

        if (! ($googleUser['email_verified'] ?? false)) {
            return $this->redirectWithError($action, 'Googleアカウントのメール認証を完了してから連携してください。');
        }

        if ($action === 'link') {
            return $this->handleLink($request, $googleUser);
        }

        return $this->handleLogin($request, $googleUser);
    }

    private function redirectToGoogle(Request $request, string $action): RedirectResponse
    {
        $this->ensureGoogleConfigExists();

        $state = Str::random(40);

        $request->session()->put(self::SESSION_STATE_KEY, $state);
        $request->session()->put(self::SESSION_ACTION_KEY, $action);

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => $this->googleRedirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    private function handleLink(Request $request, array $googleUser): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $existingLinkedUser = User::query()
            ->where('google_id', $googleUser['sub'])
            ->where('id', '!=', $user->id)
            ->first();

        if ($existingLinkedUser) {
            return redirect()
                ->route('account.index')
                ->withErrors(['google' => 'このGoogleアカウントは別のユーザーに連携済みです。']);
        }

        $user->forceFill([
            'google_id' => $googleUser['sub'],
            'google_email' => $googleUser['email'] ?? null,
            'google_avatar' => $googleUser['picture'] ?? null,
            'google_connected_at' => now(),
        ])->save();

        return redirect()
            ->route('account.index')
            ->with('feedback.success', 'Googleアカウントを連携しました。次回からGoogle認証でもログインできます。');
    }

    private function handleLogin(Request $request, array $googleUser): RedirectResponse
    {
        $user = User::query()
            ->where('google_id', $googleUser['sub'])
            ->first();

        if (! $user && isset($googleUser['email'])) {
            $matchedEmailUser = User::query()
                ->where('email', $googleUser['email'])
                ->first();

            if ($matchedEmailUser) {
                return redirect()
                    ->route('login')
                    ->withErrors(['google' => 'このメールアドレスのアカウントは未連携です。通常ログイン後にアカウント設定からGoogle連携してください。']);
            }
        }

        if (! $user) {
            return redirect()
                ->route('register')
                ->withErrors(['google' => '連携済みのアカウントが見つかりません。先に通常登録してからGoogle連携してください。']);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    private function fetchGoogleUser(string $code): array
    {
        $this->ensureGoogleConfigExists();

        $tokenResponse = Http::asForm()
            ->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => $this->googleRedirectUri(),
                'grant_type' => 'authorization_code',
            ])
            ->throw()
            ->json();

        return Http::withToken($tokenResponse['access_token'])
            ->get('https://openidconnect.googleapis.com/v1/userinfo')
            ->throw()
            ->json();
    }

    private function redirectWithError(string $action, string $message): RedirectResponse
    {
        return redirect()
            ->route($action === 'link' ? 'account.index' : 'login')
            ->withErrors(['google' => $message]);
    }

    private function ensureGoogleConfigExists(): void
    {
        abort_unless(
            filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled($this->googleRedirectUri()),
            500,
            'Google OAuth is not configured.'
        );
    }

    private function googleRedirectUri(): string
    {
        return (string) (config('services.google.redirect_uri') ?: route('auth.google.callback'));
    }
}
