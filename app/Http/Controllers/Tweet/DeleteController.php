<?php

namespace App\Http\Controllers\Tweet;

use App\Http\Controllers\Controller;
use App\Models\Tweet;
use App\Services\TweetQueryService;
use App\Services\TweetService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DeleteController extends Controller
{
    public function __invoke(Request $request, TweetService $tweetService)
    {
        $tweetId = (int) $request->route('tweetId');
        $user = $request->user();
        $tweet = Tweet::where('id', $tweetId)->firstOrFail();
        $canDeleteSeededTweet = $tweet->is_seeded
            && $user->is_seed_admin
            && $tweet->user_id === $user->id;

        if ($tweet->is_protected && ! $user->is_seed_admin) {
            return back()->with('feedback.error', '保護されたつぶやきは削除できません');
        }

        if ($tweet->is_seeded && ! $canDeleteSeededTweet) {
            return back()->with('feedback.error', 'Seederで作成したつぶやきは削除できません');
        }

        // 管理者は全ての投稿を削除可能
        if (! $user->is_admin && ! $tweetService->checkOwnTweet($user->id, $tweetId)) {
            throw new AccessDeniedHttpException;
        }
        $tweetService->deleteTweet($tweetId, $canDeleteSeededTweet);
        $lastPage = max(1, (int) ceil(Tweet::count() / TweetQueryService::TWEETS_PER_PAGE));
        $returnPage = min(max(1, (int) $request->input('page', 1)), $lastPage);
        $returnUrl = $this->safeReturnUrl($request->input('return_url'));

        return redirect($returnUrl ?: route('tweet.index', ['page' => $returnPage]))
            ->with('feedback.success', 'つぶやきを削除しました');
    }

    private function safeReturnUrl(?string $returnUrl): ?string
    {
        if (! $returnUrl) {
            return null;
        }

        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        $returnHost = parse_url($returnUrl, PHP_URL_HOST);

        if ($returnHost !== null && $returnHost !== $appHost) {
            return null;
        }

        $path = parse_url($returnUrl, PHP_URL_PATH) ?: '';

        if (! str_starts_with($path, '/tweet/search')) {
            return null;
        }

        return $returnUrl;
    }
}
