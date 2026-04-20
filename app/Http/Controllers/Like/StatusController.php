<?php

namespace App\Http\Controllers\Like;

use App\Http\Controllers\Controller;
use App\Services\LikeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StatusController extends Controller
{
    public function __invoke(Request $request, LikeService $likeService): JsonResponse
    {
        $tweetIds = collect(explode(',', (string) $request->query('tweet_ids', '')))
            ->map(fn ($tweetId) => (int) trim($tweetId))
            ->filter(fn ($tweetId) => $tweetId > 0)
            ->unique()
            ->take(100)
            ->values()
            ->all();

        return response()->json([
            'likes' => $likeService->getStatuses($tweetIds, Auth::id()),
        ]);
    }
}
