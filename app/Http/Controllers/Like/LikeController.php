<?php

namespace App\Http\Controllers\Like;

use App\Http\Controllers\Controller;
use App\Services\LikeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function __invoke(Request $request, LikeService $likeService)
    {
        $request->validate([
            'tweet_id' => 'required|integer|exists:tweets,id',
        ]);

        $tweetId = $request->tweet_id;
        $isLiked = $likeService->toggleLike($tweetId);
        $likeCount = $likeService->getLikeCount($tweetId);

        return response()->json([
            'is_liked' => $isLiked,
            'like_count' => $likeCount,
        ]);
    }
}