@props([
    'tweets' => [],
    'currentPage' => null,
])

@foreach($tweets as $tweet)
    <x-tweet.item :tweet="$tweet" :currentPage="$currentPage"></x-tweet.item>
@endforeach
