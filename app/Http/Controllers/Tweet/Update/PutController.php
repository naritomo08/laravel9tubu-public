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

        return redirect()
            ->route('tweet.index', ['page' => $request->page()])
            ->with('feedback.success', "つぶやきを編集しました");
    }
}
