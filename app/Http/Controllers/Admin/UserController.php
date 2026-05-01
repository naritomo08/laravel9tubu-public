<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ScheduledTweetService;
use App\Services\UserDeletionService;
use App\Services\UserStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            ])->render(),
        ]);
    }

    public function updateAdmin(Request $request, User $user)
    {
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

    public function destroy(User $user, UserDeletionService $userDeletionService)
    {
        // 管理者は削除不可
        if ($user->is_admin) {
            return redirect()->route('admin.users.index')->with('error', '管理者は削除できません');
        }
        // 自分自身も削除不可（任意: 必要な場合）
        if (auth()->id() === $user->id) {
            return redirect()->route('admin.users.index')->with('error', '自分自身は削除できません');
        }
        $userDeletionService->delete($user);

        return redirect()->route('admin.users.index')->with('success', 'ユーザーを削除しました');
    }
}
