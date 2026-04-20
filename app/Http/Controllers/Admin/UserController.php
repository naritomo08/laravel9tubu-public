<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $admin = User::where('is_admin', true)->get();
        $users = User::where('is_admin', false)->orderBy('name')->get();
        $allUsers = $admin->concat($users);
        return view('admin.users.index', ['users' => $allUsers]);
    }

    public function updateEmail(Request $request, User $user)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ], [
            'email.required' => 'メールアドレスを入力してください。',
            'email.email' => 'メールアドレスの形式で入力してください。',
            'email.max' => 'メールアドレスは255文字以内で入力してください。',
            'email.unique' => 'このメールアドレスは既に使われています。',
        ]);

        if ($validated['email'] === $user->email) {
            return redirect()
                ->route('admin.users.index')
                ->with('success', 'メールアドレスは変更されていません。');
        }

        $user->email = $validated['email'];
        $user->email_verified_at = null;
        $user->save();
        $user->sendEmailVerificationNotification();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'メールアドレスを変更しました。新しいメールアドレスへ確認メールを送信しました。');
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
}
