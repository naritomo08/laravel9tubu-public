<?php

namespace App\Http\Controllers\Tweet\Update;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tweet\UpdateRequest;
use App\Services\TweetService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PutController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(UpdateRequest $request, TweetService $tweetService)
    {
        if (!$tweetService->checkOwnTweet($request->user()->id, $request->id())) {
            throw new AccessDeniedHttpException();
        }
        $tweetService->updateTweet(
            $request->id(),
            $request->tweet(),
            $request->images(),
            $request->deleteImageIds()
        );
        $returnUrl = $this->safeReturnUrl($request->returnUrl());

        return redirect($returnUrl ?: route('tweet.index', ['page' => $request->page()]))
            ->with('feedback.success', "つぶやきを編集しました");
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
