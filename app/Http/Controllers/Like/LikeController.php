<?php

namespace App\Http\Controllers\Like;

use App\Http\Controllers\Controller;
use App\Models\Tweet;
use App\Services\LikeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LikeController extends Controller
{
    public function __invoke(Request $request, LikeService $likeService)
    {
        $request->validate([
            'tweet_id' => 'required|integer|exists:tweets,id',
        ]);

        $tweetId = $request->tweet_id;

        abort_unless(Tweet::visibleTo(Auth::user())->where('id', $tweetId)->exists(), 404);

        $isLiked = $likeService->toggleLike($tweetId);
        $likeCount = $likeService->getLikeCount($tweetId);

        return response()->json([
            'is_liked' => $isLiked,
            'like_count' => $likeCount,
        ]);
    }
}
