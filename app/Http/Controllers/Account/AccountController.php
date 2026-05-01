<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Services\UserDeletionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class AccountController extends Controller
{
    public function index()
    {
        return view('account.index', [
            'stats' => $this->buildStatsPayload(Auth::user()),
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        return response()->json($this->buildStatsPayload($request->user()));
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('users', 'name')->ignore($user->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ], [
            'name.required' => 'ユーザー名を入力してください。',
            'name.max' => 'ユーザー名は255文字以内で入力してください。',
            'name.unique' => 'このユーザー名は既に使われています。',
            'email.required' => 'メールアドレスを入力してください。',
            'email.email' => 'メールアドレスの形式で入力してください。',
            'email.max' => 'メールアドレスは255文字以内で入力してください。',
            'email.unique' => 'このメールアドレスは既に使われています。',
        ]);

        $emailChanged = $validated['email'] !== $user->email;

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();

            return redirect()
                ->route('verification.notice')
                ->with('feedback.success', 'プロフィールを変更しました。新しいメールアドレスを確認してください。');
        }

        return back()->with('feedback.success', 'プロフィールを変更しました。');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('feedback.success', 'パスワードを変更しました。');
    }

    public function updateMailSettings(Request $request)
    {
        $validated = $request->validate([
            'receives_notification_mail' => ['nullable', 'boolean'],
        ]);

        $request->user()->update([
            'receives_notification_mail' => (bool) ($validated['receives_notification_mail'] ?? false),
        ]);

        return back()->with('feedback.success', 'メール通知設定を変更しました。');
    }

    public function disconnectGoogle(Request $request)
    {
        $request->user()->forceFill([
            'google_id' => null,
            'google_email' => null,
            'google_avatar' => null,
            'google_connected_at' => null,
        ])->save();

        return back()->with('feedback.success', 'Googleアカウントの連携を解除しました。');
    }

    public function destroy(Request $request, UserDeletionService $userDeletionService)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->is_admin) {
            return back()->withErrors([
                'current_password' => '管理者アカウントは削除できません。',
            ]);
        }

        Auth::guard('web')->logout();

        $userDeletionService->delete($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/tweet');
    }

    private function buildStatsPayload($user): array
    {
        $tweetCount = $user->tweets()->count();
        $likeCount = Like::query()
            ->join('tweets', 'likes.tweet_id', '=', 'tweets.id')
            ->where('tweets.user_id', $user->id)
            ->count();

        return [
            'label' => 'あなたの集計',
            'tweet_count' => $tweetCount,
            'like_count' => $likeCount,
        ];
    }
}
