<?php

namespace App\Http\Controllers\Tweet;

use App\Http\Controllers\Controller;
use App\Services\TweetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request, TweetService $tweetService)
    {
        $query = (string) $request->query('q', '');
        $userSearch = $request->boolean('user_search');
        $tweets = $this->searchTweetsOnAvailablePage(
            $tweetService,
            $query,
            $userSearch,
            (int) $request->query('page', 1)
        );
        $this->preparePaginator($tweets, $query, $userSearch);
        $returnUrl = route('tweet.search', array_filter([
            'q' => $query,
            'user_search' => $userSearch ? 1 : null,
            'page' => $tweets->currentPage(),
        ], fn ($value) => $value !== null && $value !== ''));

        return view('tweet.search')
            ->with('query', $query)
            ->with('userSearch', $userSearch)
            ->with('tweets', $tweets)
            ->with('returnUrl', $returnUrl);
    }

    public function results(Request $request, TweetService $tweetService): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'user_search' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = (string) ($validated['q'] ?? '');
        $userSearch = (bool) ($validated['user_search'] ?? false);
        $tweets = $this->searchTweetsOnAvailablePage(
            $tweetService,
            $query,
            $userSearch,
            (int) ($validated['page'] ?? 1)
        );
        $this->preparePaginator($tweets, $query, $userSearch);
        $returnUrl = route('tweet.search', array_filter([
            'q' => $query,
            'user_search' => $userSearch ? 1 : null,
            'page' => $tweets->currentPage(),
        ], fn ($value) => $value !== null && $value !== ''));

        return response()->json([
            'count' => $tweets->total(),
            'current_page' => $tweets->currentPage(),
            'last_page' => $tweets->lastPage(),
            'html' => view('components.tweet.search-results', [
                'tweets' => $tweets,
                'returnUrl' => $returnUrl,
            ])->toHtml(),
        ]);
    }

    private function searchTweetsOnAvailablePage(
        TweetService $tweetService,
        string $query,
        bool $userSearch,
        int $page
    ) {
        $tweets = $tweetService->searchTweets($query, $userSearch, max(1, $page));

        if ($tweets->total() > 0 && $tweets->currentPage() > $tweets->lastPage()) {
            $tweets = $tweetService->searchTweets($query, $userSearch, $tweets->lastPage());
        }

        return $tweets;
    }

    private function preparePaginator($tweets, string $query, bool $userSearch): void
    {
        $tweets->withPath(route('tweet.search'))
            ->appends([
                'q' => $query,
                'user_search' => $userSearch ? 1 : 0,
            ]);
    }
}
