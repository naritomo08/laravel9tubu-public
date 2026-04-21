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
            'tweet_versions' => ['nullable', 'string'],
        ]);

        $tweets = $tweetService->getTweetsNewerThan((int) ($validated['after_id'] ?? 0));
        $tweetVersions = $this->decodeTweetVersions($validated['tweet_versions'] ?? null);
        $changedTweets = $tweetService->getChangedTweets($tweetVersions);
        $firstPageTweets = $tweetService->getTweets(1);
        $firstPageTweets->withPath(route('tweet.index'));
        $fullListHtml = view('components.tweet.items', [
            'tweets' => $firstPageTweets,
            'currentPage' => 1,
        ])->render();
        $paginationHtml = $firstPageTweets->hasPages()
            ? $firstPageTweets->links('components.pagination.tweets')->toHtml()
            : '';
        $snapshotSignature = implode(',', $firstPageTweets->getCollection()->pluck('id')->all())
            . '|' . $firstPageTweets->total();

        return response()->json([
            'latest_id' => (int) ($firstPageTweets->getCollection()->max('id') ?? 0),
            'last_page' => $firstPageTweets->lastPage(),
            'html' => view('components.tweet.items', [
                'tweets' => $tweets,
                'currentPage' => 1,
            ])->render(),
            'updated_html' => $changedTweets->mapWithKeys(function ($tweet) {
                return [
                    (string) $tweet->id => view('components.tweet.item', [
                        'tweet' => $tweet,
                        'currentPage' => 1,
                    ])->render(),
                ];
            })->all(),
            'full_html' => $fullListHtml,
            'pagination_html' => $paginationHtml,
            'snapshot_signature' => $snapshotSignature,
        ]);
    }

    private function decodeTweetVersions(?string $tweetVersions): array
    {
        if (!$tweetVersions) {
            return [];
        }

        $decodedTweetVersions = json_decode($tweetVersions, true);

        if (!is_array($decodedTweetVersions)) {
            return [];
        }

        return collect($decodedTweetVersions)
            ->mapWithKeys(function ($updatedAt, $tweetId) {
                $tweetId = (int) $tweetId;

                if ($tweetId <= 0 || !is_string($updatedAt)) {
                    return [];
                }

                return [$tweetId => $updatedAt];
            })
            ->take(100)
            ->all();
    }
}
