<?php

namespace App\Http\Controllers\Tweet;

use App\Http\Controllers\Controller;
use App\Services\TweetQueryService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return Response
     */
    public function __invoke(Request $request, TweetQueryService $tweetQueryService)
    {
        $tweets = $tweetQueryService->getTweets((int) $request->input('page', 1));

        return view('tweet.index')
            ->with('tweets', $tweets);
    }
}
