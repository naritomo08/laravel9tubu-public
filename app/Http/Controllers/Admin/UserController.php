<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $admin = User::where('is_admin', true)->get();
        $users = User::where('is_admin', false)->orderBy('name')->get();
        $allUsers = $admin->concat($users);
        return view('admin.users.index', ['users' => $allUsers]);
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
        // 関連ツイートも削除
        $user->tweets()->delete();
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'ユーザーを削除しました');
    }
}
