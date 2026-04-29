<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Tweet;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = $this->getUsersWithStats();

        return view('admin.users.index', [
            'users' => $users,
            'stats' => $this->buildStatsPayload($users),
        ]);
    }

    public function stats(): JsonResponse
    {
        $users = $this->getUsersWithStats();

        return response()->json($this->buildStatsPayload($users));
    }

    public function listUsers(): JsonResponse
    {
        return response()->json([
            'html' => view('admin.users._rows', [
                'users' => $this->getUsersWithStats(),
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

    public function destroy(User $user)
    {
        // 管理者は削除不可
        if ($user->is_admin) {
            return redirect()->route('admin.users.index')->with('error', '管理者は削除できません');
        }
        // 自分自身も削除不可（任意: 必要な場合）
        if (auth()->id() === $user->id) {
            return redirect()->route('admin.users.index')->with('error', '自分自身は削除できません');
        }
        // いいねを先に削除（外部キー制約で自動削除されるが、明示的に削除）
        $user->likes()->delete();
        // 関連ツイートも削除
        $user->tweets()->delete();
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'ユーザーを削除しました');
    }

    private function getUsersWithStats(): Collection
    {
        return User::query()
            ->withCount('tweets')
            ->addSelect([
                'received_likes_count' => Like::query()
                    ->selectRaw('count(*)')
                    ->join('tweets', 'likes.tweet_id', '=', 'tweets.id')
                    ->whereColumn('tweets.user_id', 'users.id'),
            ])
            ->orderBy('id')
            ->get();
    }

    private function buildStatsPayload(Collection $users): array
    {
        return [
            'totals' => [
                'label' => 'トータル',
                'tweet_count' => Tweet::count(),
                'like_count' => Like::count(),
            ],
            'users' => $users->map(function (User $user): array {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'tweet_count' => (int) $user->tweets_count,
                    'like_count' => (int) ($user->received_likes_count ?? 0),
                ];
            })->values()->all(),
        ];
    }
}
