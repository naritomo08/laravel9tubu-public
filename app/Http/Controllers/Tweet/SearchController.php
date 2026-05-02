<?php

namespace App\Http\Controllers\Tweet;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TweetQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request, TweetQueryService $tweetQueryService)
    {
        $query = (string) $request->query('q', '');
        $userSearch = $request->boolean('user_search');
        $searchQuery = $userSearch ? '' : $query;
        $userId = $userSearch ? $this->nullablePositiveInt($request->query('user_id')) : null;
        $tweets = $this->searchTweetsOnAvailablePage(
            $tweetQueryService,
            $searchQuery,
            $userSearch,
            (int) $request->query('page', 1),
            $userId
        );
        $this->preparePaginator($tweets, $searchQuery, $userSearch, $userId);
        $returnUrl = route('tweet.search', array_filter([
            'q' => $searchQuery,
            'user_search' => $userSearch ? 1 : null,
            'user_id' => $userSearch ? $userId : null,
            'page' => $tweets->currentPage(),
        ], fn ($value) => $value !== null && $value !== ''));
        $users = $userSearch ? $this->searchableUsers() : collect();

        return view('tweet.search')
            ->with('query', $searchQuery)
            ->with('userSearch', $userSearch)
            ->with('selectedUserId', $userId)
            ->with('users', $users)
            ->with('tweets', $tweets)
            ->with('returnUrl', $returnUrl);
    }

    public function results(Request $request, TweetQueryService $tweetQueryService): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'user_search' => ['nullable', 'boolean'],
            'user_id' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = (string) ($validated['q'] ?? '');
        $userSearch = (bool) ($validated['user_search'] ?? false);
        $searchQuery = $userSearch ? '' : $query;
        $userId = $userSearch ? ($validated['user_id'] ?? null) : null;
        $tweets = $this->searchTweetsOnAvailablePage(
            $tweetQueryService,
            $searchQuery,
            $userSearch,
            (int) ($validated['page'] ?? 1),
            $userId
        );
        $this->preparePaginator($tweets, $searchQuery, $userSearch, $userId);
        $returnUrl = route('tweet.search', array_filter([
            'q' => $searchQuery,
            'user_search' => $userSearch ? 1 : null,
            'user_id' => $userSearch ? $userId : null,
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

    public function users(): JsonResponse
    {
        return response()->json([
            'users' => $this->searchableUsers()
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                ])
                ->values(),
        ]);
    }

    private function searchTweetsOnAvailablePage(
        TweetQueryService $tweetQueryService,
        string $query,
        bool $userSearch,
        int $page,
        ?int $userId
    ) {
        $tweets = $tweetQueryService->searchTweets($query, $userSearch, max(1, $page), $userId);

        if ($tweets->total() > 0 && $tweets->currentPage() > $tweets->lastPage()) {
            $tweets = $tweetQueryService->searchTweets($query, $userSearch, $tweets->lastPage(), $userId);
        }

        return $tweets;
    }

    private function preparePaginator($tweets, string $query, bool $userSearch, ?int $userId): void
    {
        $tweets->withPath(route('tweet.search'))
            ->appends([
                'q' => $query,
                'user_search' => $userSearch ? 1 : 0,
                'user_id' => $userSearch ? $userId : null,
            ]);
    }

    private function nullablePositiveInt($value): ?int
    {
        $intValue = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $intValue === false ? null : $intValue;
    }

    private function searchableUsers()
    {
        return User::where(function ($query) {
            $query->whereNotNull('email_verified_at')
                ->orWhereColumn('created_at', '<>', 'updated_at');
        })
            ->notPendingDeletion()
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name']);
    }
}
