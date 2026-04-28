@props([
    'tweets' => [],
    'currentPage' => null,
    'returnUrl' => null,
])

@foreach($tweets as $tweet)
    <x-tweet.item :tweet="$tweet" :currentPage="$currentPage" :returnUrl="$returnUrl"></x-tweet.item>
@endforeach
