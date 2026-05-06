<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ScheduledTweetService;
use App\Services\UserDeletionService;
use App\Services\UserStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(
        private readonly UserStatsService $userStatsService,
        private readonly ScheduledTweetService $scheduledTweetService,
    ) {}

    public function index()
    {
        $users = $this->userStatsService->getUsersWithStats();

        return view('admin.users.index', [
            'users' => $users,
            'stats' => $this->userStatsService->buildAdminStatsPayload($users),
            'scheduledTweets' => $this->scheduledTweetService->getUpcomingTweets(),
            'canManageScheduledTweets' => auth()->user()?->hasEnabledTwoFactorAuthentication() ?? false,
        ]);
    }

    public function stats(): JsonResponse
    {
        $users = $this->userStatsService->getUsersWithStats();

        return response()->json($this->userStatsService->buildAdminStatsPayload($users));
    }

    public function listUsers(): JsonResponse
    {
        return response()->json([
            'html' => view('admin.users._rows', [
                'users' => $this->userStatsService->getUsersWithStats(),
            ])->render(),
        ]);
    }

    public function listScheduledTweets(): JsonResponse
    {
        return response()->json([
            'html' => view('admin.users._scheduled_tweets', [
                'scheduledTweets' => $this->scheduledTweetService->getUpcomingTweets(),
                'canManageScheduledTweets' => auth()->user()?->hasEnabledTwoFactorAuthentication() ?? false,
            ])->render(),
        ]);
    }

    public function updateAdmin(Request $request, User $user)
    {
        if ($response = $this->rejectUnlessCurrentAdminHasTwoFactor()) {
            return $response;
        }

        $validated = $request->validate([
            'is_admin' => ['required', 'boolean'],
        ]);

        $makeAdmin = (bool) $validated['is_admin'];

        if (auth()->id() === $user->id) {
            return redirect()->route('admin.users.index')->with('error', '自分自身の管理者権限は変更できません');
        }

        if (!$makeAdmin && $user->is_seed_admin) {
            return redirect()->route('admin.users.index')->with('error', 'Seederで作成した管理者は外せません');
        }

        if ($user->is_admin === $makeAdmin) {
            return redirect()->route('admin.users.index')->with('success', '管理者権限は変更されていません');
        }

        $user->is_admin = $makeAdmin;
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', $makeAdmin ? '管理者にしました' : '管理者から外しました');
    }

    public function updateEmail(Request $request, User $user)
    {
        if ($response = $this->rejectUnlessCurrentAdminHasTwoFactor()) {
            return $response;
        }

        if (auth()->id() === $user->id) {
            return redirect()->route('admin.users.index')->with('error', '自分自身のメールアドレスは管理者画面から変更できません');
        }

        if ($user->is_seed_admin) {
            return redirect()->route('admin.users.index')->with('error', 'Seederで作成した管理者のメールアドレスは変更できません');
        }

        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ], [
            'email.required' => 'メールアドレスを入力してください。',
            'email.email' => 'メールアドレスの形式で入力してください。',
            'email.max' => 'メールアドレスは255文字以内で入力してください。',
            'email.unique' => 'このメールアドレスは既に使われています。',
        ]);

        if ($validated['email'] === $user->email) {
            return redirect()->route('admin.users.index')->with('success', 'メールアドレスは変更されていません');
        }

        $user->forceFill([
            'email' => $validated['email'],
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();

        return redirect()->route('admin.users.index')->with('success', 'メールアドレスを変更し、認証メールを送信しました');
    }

    public function resetTwoFactor(User $user)
    {
        if ($response = $this->rejectUnlessCurrentAdminHasTwoFactor()) {
            return $response;
        }

        if (auth()->id() === $user->id) {
            return redirect()->route('admin.users.index')->with('error', '自分自身の2段階認証は管理者画面からリセットできません');
        }

        if ($user->is_seed_admin) {
            return redirect()->route('admin.users.index')->with('error', 'Seederで作成した管理者の2段階認証はリセットできません');
        }

        if (! $user->hasEnabledTwoFactorAuthentication() && is_null($user->two_factor_secret)) {
            return redirect()->route('admin.users.index')->with('success', '2段階認証は設定されていません');
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return redirect()->route('admin.users.index')->with('success', '2段階認証をリセットしました');
    }

    public function destroy(User $user, UserDeletionService $userDeletionService)
    {
        if ($response = $this->rejectUnlessCurrentAdminHasTwoFactor()) {
            return $response;
        }

        // 管理者は削除不可
        if ($user->is_admin) {
            return redirect()->route('admin.users.index')->with('error', '管理者は削除できません');
        }
        // 自分自身も削除不可（任意: 必要な場合）
        if (auth()->id() === $user->id) {
            return redirect()->route('admin.users.index')->with('error', '自分自身は削除できません');
        }
        if (! $userDeletionService->requestDeletion($user)) {
            return redirect()->route('admin.users.index')->with('success', 'ユーザー削除は受付済みです');
        }

        return redirect()->route('admin.users.index')->with('success', 'ユーザー削除を受け付けました');
    }

    private function rejectUnlessCurrentAdminHasTwoFactor()
    {
        if (auth()->user()?->hasEnabledTwoFactorAuthentication()) {
            return null;
        }

        return redirect()
            ->route('admin.users.index')
            ->with('error', 'ユーザー関連操作を行うには、管理者自身の2段階認証を有効化してください');
    }
}
