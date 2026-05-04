@props([
    'tweets' => []
])
@php($tweetItems = method_exists($tweets, 'getCollection') ? $tweets->getCollection() : collect($tweets))
@php($latestTweetId = $tweetItems->max('id') ?? 0)
@php($isFirstTweetPage = !method_exists($tweets, 'currentPage') || $tweets->currentPage() === 1)
@php($currentPage = method_exists($tweets, 'currentPage') ? $tweets->currentPage() : 1)

<div
    class="bg-white rounded-md shadow-lg mt-5 mb-5 dark:bg-gray-900 dark:shadow-gray-950/40"
    data-tweet-list
    data-current-page="{{ $currentPage }}"
    data-index-url="{{ route('tweet.index', [], false) }}"
    data-latest-url="{{ route('tweet.latest', [], false) }}"
    data-like-status-url="{{ route('like.status', [], false) }}"
    data-latest-tweet-id="{{ $latestTweetId ?? 0 }}"
    data-auto-refresh-enabled="{{ $isFirstTweetPage ? 'true' : 'false' }}"
>
    <div class="px-4 pt-4" data-tweet-pagination-top>
        @if(method_exists($tweets, 'links') && $tweets->hasPages())
            {{ $tweets->links('components.pagination.tweets') }}
        @endif
    </div>

    <ul data-tweet-list-items>
        <x-tweet.items :tweets="$tweets" :currentPage="$currentPage"></x-tweet.items>
    </ul>
</div>

<div class="mt-5 mb-5" data-tweet-pagination-bottom>
    @if(method_exists($tweets, 'links') && $tweets->hasPages())
        {{ $tweets->links('components.pagination.tweets') }}
    @endif
</div>

<x-tweet.image-modal />

@push('css')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush
