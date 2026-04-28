<?php

namespace App\Http\Controllers\Tweet\Update;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tweet;
use App\Services\TweetService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, TweetService $tweetService)
    {
        $tweetId = (int) $request->route('tweetId');
        if (!$tweetService->checkOwnTweet($request->user()->id, $tweetId)) {
            throw new AccessDeniedHttpException();
        }

        $tweet = Tweet::with('images')->where('id', $tweetId)->firstOrFail();
        $returnPage = max(1, (int) $request->query('page', 1));
        $returnUrl = $this->safeReturnUrl($request->query('return_url'));

        return view('tweet.update')
            ->with('tweet', $tweet)
            ->with('returnPage', $returnPage)
            ->with('returnUrl', $returnUrl);
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
