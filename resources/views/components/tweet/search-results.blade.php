@props([
    'tweets' => [],
    'returnUrl' => null,
])

@if(count($tweets) > 0)
    <div class="px-4 pt-4" data-tweet-search-pagination-top>
        @if(method_exists($tweets, 'links') && $tweets->hasPages())
            {{ $tweets->links('components.pagination.tweets') }}
        @endif
    </div>

    <ul data-tweet-list-items>
        <x-tweet.items
            :tweets="$tweets"
            :currentPage="method_exists($tweets, 'currentPage') ? $tweets->currentPage() : null"
            :returnUrl="$returnUrl"
        ></x-tweet.items>
    </ul>

    <div class="px-4 py-4" data-tweet-search-pagination-bottom>
        @if(method_exists($tweets, 'links') && $tweets->hasPages())
            {{ $tweets->links('components.pagination.tweets') }}
        @endif
    </div>
@else
    <div class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400" data-tweet-search-empty>
        該当するつぶやきはありません。
    </div>
@endif
