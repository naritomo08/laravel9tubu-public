<?php

namespace App\Http\Controllers\Tweet;

use App\Http\Controllers\Controller;
use App\Services\TweetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LatestController extends Controller
{
    public function __invoke(Request $request, TweetService $tweetService): JsonResponse
    {
        $validated = $request->validate([
            'after_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $tweets = $tweetService->getTweetsNewerThan((int) ($validated['after_id'] ?? 0));

        return response()->json([
            'latest_id' => $tweets->max('id') ?? (int) ($validated['after_id'] ?? 0),
            'html' => view('components.tweet.items', [
                'tweets' => $tweets,
            ])->render(),
        ]);
    }
}
