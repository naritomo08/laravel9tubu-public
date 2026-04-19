@props([
    'tweets' => []
])
@php($latestTweetId = is_iterable($tweets) ? collect($tweets)->max('id') : 0)

<div
    class="bg-white rounded-md shadow-lg mt-5 mb-5"
    data-tweet-list
    data-latest-url="{{ route('tweet.latest') }}"
    data-latest-tweet-id="{{ $latestTweetId ?? 0 }}"
>
    <ul data-tweet-list-items>
        <x-tweet.items :tweets="$tweets"></x-tweet.items>
    </ul>
</div>
