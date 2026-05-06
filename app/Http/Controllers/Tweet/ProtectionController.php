<?php

namespace App\Http\Controllers\Tweet;

use App\Http\Controllers\Controller;
use App\Models\Tweet;
use Illuminate\Http\Request;

class ProtectionController extends Controller
{
    public function __invoke(Request $request, Tweet $tweet)
    {
        $user = $request->user();

        if (!$user->is_seed_admin) {
            abort(403, 'Seeder管理者のみ保護設定を変更できます');
        }

        if (! $user->hasEnabledTwoFactorAuthentication()) {
            return back()->with('feedback.error', '他ユーザーのつぶやきを保護するには、管理者自身の2段階認証を有効化してください');
        }

        if ($tweet->user()->where('is_seed_admin', true)->exists()) {
            return back()->with('feedback.error', 'Seeder管理者のつぶやきは保護設定の対象外です');
        }

        $validated = $request->validate([
            'is_protected' => ['required', 'boolean'],
        ]);

        $tweet->is_protected = (bool) $validated['is_protected'];
        $tweet->save();

        return back()->with(
            'feedback.success',
            $tweet->is_protected ? 'つぶやきを保護しました' : 'つぶやきの保護を解除しました'
        );
    }
}
