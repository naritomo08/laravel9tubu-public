<?php

namespace App\Http\Controllers\Tweet;

use App\Http\Controllers\Controller;
use App\Models\Tweet;
use App\Services\TweetService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DeleteController extends Controller
{
    public function __invoke(Request $request, TweetService $tweetService)
    {
        $tweetId = (int) $request->route('tweetId');
        $user = $request->user();
        // 管理者は全ての投稿を削除可能
        if (!$user->is_admin && !$tweetService->checkOwnTweet($user->id, $tweetId)) {
            throw new AccessDeniedHttpException();
        }
        $tweetService->deleteTweet($tweetId);
        $lastPage = max(1, (int) ceil(Tweet::count() / TweetService::TWEETS_PER_PAGE));
        $returnPage = min(max(1, (int) $request->input('page', 1)), $lastPage);
        $returnUrl = $this->safeReturnUrl($request->input('return_url'));

        return redirect($returnUrl ?: route('tweet.index', ['page' => $returnPage]))
            ->with('feedback.success', "つぶやきを削除しました");
    }

    private function safeReturnUrl(?string $returnUrl): ?string
    {
        if (!$returnUrl) {
            return null;
        }

        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        $returnHost = parse_url($returnUrl, PHP_URL_HOST);

        if ($returnHost !== null && $returnHost !== $appHost) {
            return null;
        }

        $path = parse_url($returnUrl, PHP_URL_PATH) ?: '';

        if (!str_starts_with($path, '/tweet/search')) {
            return null;
        }

        return $returnUrl;
    }
}
